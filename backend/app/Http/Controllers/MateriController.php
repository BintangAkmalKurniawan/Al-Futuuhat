<?php

namespace App\Http\Controllers;

use App\Http\Requests\Materi\SimpanMateriRequest;
use App\Http\Resources\MateriDetailResource;
use App\Http\Resources\MateriRingkasResource;
use App\Http\Resources\RevisiResource;
use App\Http\Resources\RevisiRingkasResource;
use App\Models\Materi;
use App\Models\RevisiMateri;
use App\Models\User;
use App\Notifications\MateriDiajukan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Mews\Purifier\Facades\Purifier;

/**
 * Materi: tulis draf, ubah, hapus, ajukan, revisi, arsip, dan baca
 * (KF-08 s.d. KF-13, KF-16).
 *
 * Draf 2.3: judul, ringkasan, sampul, dan kategori disimpan per versi
 * (`revisi_materi` + `kategori_revisi`). Area anggota selalu menampilkan
 * revisi terakhir; kolom di tabel `materi` adalah data versi publik (PRD §8.2).
 */
class MateriController extends Controller
{
    /**
     * Relasi yang dibutuhkan Resource materi di area anggota (KNF-09: eager loading).
     */
    private const RELASI_INTERNAL = ['penulis', 'versiTerbit', 'revisiTerakhir.kategori', 'penarikanMenunggu'];

    /**
     * GET /api/materi?q=&penulis=&status=&kategori=&page= — arsip seluruh materi
     * anggota (KF-16). Anggota aktif melihat semua status, termasuk draf.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $q = trim((string) $request->query('q', ''));
        $penulis = trim((string) $request->query('penulis', ''));
        $status = trim((string) $request->query('status', ''));
        $kategori = trim((string) $request->query('kategori', ''));

        $materi = Materi::query()
            ->with(self::RELASI_INTERNAL)
            ->withCount('komentar')
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            // KP-19.3 & KP-19.4: penyaring memakai kategori revisi terakhir (slug).
            ->when($kategori !== '', fn ($query) => $query->whereHas(
                'revisiTerakhir.kategori',
                fn ($sub) => $sub->where('kategori.slug', $kategori)
            ))
            ->when($penulis !== '', fn ($query) => $query->whereHas(
                'penulis',
                fn ($sub) => $sub->where('nama', 'ilike', "%{$penulis}%")
            ))
            ->when($q !== '', fn ($query) => $this->saringKataKunci($query, $q))
            // KP-16.2: "tanggal diperbarui" — urutkan menurut perubahan terakhir.
            ->orderByDesc('materi.updated_at')
            ->paginate(10)
            ->withQueryString();

        return MateriRingkasResource::collection($materi);
    }

    /**
     * Ubah kata kunci menjadi tsquery berawalan: tiap potongan huruf/angka
     * dicari sebagai awalan kata ("bisk" menemukan "Biskirintil"), sehingga
     * pencarian realtime sudah memberi hasil sebelum kata diketik lengkap.
     * Aman dari sintaks tsquery karena semua tanda baca dibuang lebih dulu.
     */
    private function tsqueryAwalan(string $q): string
    {
        $potongan = preg_split('/[^\p{L}\p{N}]+/u', $q, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return implode(' & ', array_map(fn (string $kata) => $kata.':*', $potongan));
    }

    /**
     * Satu gerbang pencarian (KP-16.1): judul dan isi revisi terakhir, serta
     * nama penulis. `$cariPenulis` dimatikan pada daftar materi sendiri karena
     * penulisnya selalu pengguna yang sedang login.
     */
    private function saringKataKunci(Builder $query, string $q, bool $cariPenulis = true): void
    {
        $tsquery = $this->tsqueryAwalan($q);

        // Kata kunci yang hanya berisi tanda baca tidak menyaring apa pun.
        if ($tsquery === '') {
            return;
        }

        $query->where(function (Builder $sub) use ($q, $tsquery, $cariPenulis) {
            // Judul ikut dicari dari revisi terakhir, karena judul kini disimpan per versi.
            $sub->whereHas('revisiTerakhir', fn (Builder $versi) => $versi->where(
                fn (Builder $kolom) => $kolom
                    ->whereRaw("to_tsvector('simple', revisi_materi.judul) @@ to_tsquery('simple', ?)", [$tsquery])
                    ->orWhereRaw(
                        "to_tsvector('simple', regexp_replace(revisi_materi.konten_html, '<[^>]+>', ' ', 'g'))"
                        ." @@ to_tsquery('simple', ?)",
                        [$tsquery]
                    )
            ));

            if ($cariPenulis) {
                $sub->orWhereHas(
                    'penulis',
                    fn (Builder $penulis) => $penulis->where('nama', 'ilike', "%{$q}%")
                );
            }
        });
    }

    /**
     * GET /api/materi-saya?q=&status= — semua materi milik pengguna.
     */
    public function materiSaya(Request $request): AnonymousResourceCollection
    {
        $this->authorize('buat', Materi::class); // KP-08.6: admin tidak punya "Materi saya"

        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status');

        $materi = Materi::query()
            ->where('penulis_id', $request->user()->id)
            ->with(self::RELASI_INTERNAL)
            ->withCount('komentar')
            ->when($status, fn ($query) => $query->where('status', $status))
            // Penulis selalu pengguna sendiri, jadi cukup judul dan isi.
            ->when($q !== '', fn ($query) => $this->saringKataKunci($query, $q, false))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return MateriRingkasResource::collection($materi);
    }

    /**
     * POST /api/materi — materi baru + revisi versi 1 (KF-08).
     */
    public function store(SimpanMateriRequest $request): JsonResponse
    {
        $data = $request->validated();

        // KP-08.3: materi dan revisi v1 dibuat dalam satu transaksi.
        $materi = DB::transaction(function () use ($request, $data) {
            $materi = Materi::create([
                'penulis_id' => $request->user()->id,
                'judul' => $data['judul'],
                'slug' => Materi::slugUnik($data['judul']),
                'ringkasan' => $data['ringkasan'] ?? null,
                'sampul' => $data['sampul'] ?? null, // KF-18
                'status' => Materi::STATUS_DRAF,
            ]);

            $revisi = $materi->revisi()->create([
                'nomor_versi' => 1,
                ...$this->bagianMateri($data),
                'catatan_revisi' => $data['catatan_revisi'] ?? null,
                'status' => RevisiMateri::STATUS_DRAF,
            ]);

            // KP-19.2: kategori wajib diisi, minimal satu — disimpan di versi dan di materi.
            $revisi->kategori()->sync($data['kategori']);
            $materi->kategori()->sync($data['kategori']);

            return $materi;
        });

        return response()->json([
            'message' => 'Materimu sudah disimpan. Semua anggota sudah bisa membacanya.',
            'data' => $this->dataEditor($materi),
        ], 201);
    }

    /**
     * GET /api/materi/{materi}/edit — revisi terakhir untuk editor (penulis saja).
     */
    public function edit(Request $request, Materi $materi): JsonResponse
    {
        $this->authorize('ubah', $materi);

        return response()->json(['data' => $this->dataEditor($materi)]);
    }

    /**
     * PUT /api/materi/{materi} — ubah draf (KF-09, KP-12.4).
     *
     * Semua bagian (judul, ringkasan, sampul, kategori, isi) disimpan ke revisi
     * draf. Data versi publik di tabel `materi` hanya ikut berubah bila materi
     * tidak sedang terbit, sehingga pengunjung tetap melihat versi lama.
     */
    public function update(SimpanMateriRequest $request, Materi $materi): JsonResponse
    {
        $this->authorize('ubah', $materi);

        $revisi = $materi->revisiTerakhir()->first();

        // KP-09.1 & KP-10.4: hanya revisi terakhir yang masih draf boleh diubah.
        if ($revisi === null || $revisi->status !== RevisiMateri::STATUS_DRAF) {
            throw ValidationException::withMessages([
                'judul' => 'Materi ini belum bisa diubah karena lagi dicek admin.',
            ]);
        }

        $data = $request->validated();
        $sampulLama = $revisi->sampul;

        DB::transaction(function () use ($materi, $revisi, $data) {
            $revisi->update([
                ...$this->bagianMateri($data),
                'catatan_revisi' => $data['catatan_revisi'] ?? $revisi->catatan_revisi,
            ]);

            $revisi->kategori()->sync($data['kategori']);

            if ($materi->status === Materi::STATUS_TERBIT) {
                // Versi publik tidak berubah; cukup tandai materi baru diperbarui (KP-16.2).
                $materi->touch();
            } else {
                $materi->samakanDenganRevisi($revisi);
            }
        });

        // Berkas sampul yang tergantikan dibuang, kecuali masih dipakai versi
        // lain atau versi publik (KP-18.4), supaya riwayat tetap utuh.
        if ($sampulLama !== null && $sampulLama !== $revisi->sampul && ! $this->sampulMasihDipakai($sampulLama)) {
            Storage::disk('public')->delete($sampulLama);
        }

        return response()->json([
            'message' => 'Perubahanmu sudah disimpan.',
            'data' => $this->dataEditor($materi->refresh()),
        ]);
    }

    /**
     * DELETE /api/materi/{materi} — hanya draf yang belum pernah terbit (KP-09.2).
     */
    public function destroy(Request $request, Materi $materi): JsonResponse
    {
        $this->authorize('hapus', $materi);

        // `diterbitkan_pada` tetap terisi setelah materi ditarik, sehingga
        // materi yang pernah terbit tetap tidak dapat dihapus (KP-09.2).
        if ($materi->status !== Materi::STATUS_DRAF || $materi->diterbitkan_pada !== null) {
            throw ValidationException::withMessages([
                'materi' => 'Materi yang pernah tayang atau lagi dicek admin tidak bisa dihapus.',
            ]);
        }

        // Revisi dan komentar ikut terhapus lewat ON DELETE CASCADE (KNF-06).
        $materi->delete();

        return response()->json(['message' => 'Materinya sudah dihapus.']);
    }

    /**
     * POST /api/materi/{materi}/ajukan — ajukan publikasi (KF-10).
     */
    public function ajukan(Request $request, Materi $materi): JsonResponse
    {
        $this->authorize('ajukan', $materi);

        // KP-10.1: materi yang sedang terbit harus ditarik dari publik dulu (KF-20).
        if ($materi->status === Materi::STATUS_TERBIT) {
            throw ValidationException::withMessages([
                'materi' => 'Materi ini masih tayang. Turunkan dulu dari halaman umum, baru ajukan versi barunya ya.',
            ]);
        }

        $revisi = $materi->revisiTerakhir()->first();

        // KP-10.1: hanya revisi draf yang bisa diajukan.
        if ($revisi === null || $revisi->status !== RevisiMateri::STATUS_DRAF) {
            throw ValidationException::withMessages([
                'materi' => 'Materi ini sudah diajukan atau sudah diputuskan admin.',
            ]);
        }

        // KP-10.2: revisi dan materi sama-sama berstatus diajukan.
        DB::transaction(function () use ($materi, $revisi) {
            $revisi->update(['status' => RevisiMateri::STATUS_DIAJUKAN]);
            $materi->update(['status' => Materi::STATUS_DIAJUKAN]);
        });

        // KP-10.3: semua admin aktif menerima notifikasi dalam aplikasi.
        $admin = User::where('role', User::ROLE_ADMIN)
            ->where('status', User::STATUS_AKTIF)
            ->get();

        Notification::send($admin, new MateriDiajukan($materi, $revisi, $request->user()));

        return response()->json([
            'message' => 'Materimu sudah diajukan dan lagi dicek admin. Nanti kami kabari lewat notifikasi ya.',
            'data' => $this->dataEditor($materi->refresh()),
        ], 201);
    }

    /**
     * POST /api/materi/{materi}/batal-ajukan — batalkan pengajuan (KP-10.6).
     */
    public function batalAjukan(Request $request, Materi $materi): JsonResponse
    {
        $this->authorize('ajukan', $materi);

        $revisi = $materi->revisiTerakhir()->first();

        // Hanya pengajuan yang belum diputuskan admin yang dapat dibatalkan.
        if ($revisi === null || $revisi->status !== RevisiMateri::STATUS_DIAJUKAN) {
            throw ValidationException::withMessages([
                'materi' => 'Tidak ada pengajuan yang bisa dibatalkan.',
            ]);
        }

        DB::transaction(function () use ($materi, $revisi) {
            $revisi->update(['status' => RevisiMateri::STATUS_DRAF]);

            // Materi berstatus diajukan kembali ke draf. Pemeriksaan ini juga
            // menjaga data lama (sebelum Draf 2.3) yang materinya masih terbit.
            if ($materi->status === Materi::STATUS_DIAJUKAN) {
                $materi->update(['status' => Materi::STATUS_DRAF]);
            }
        });

        return response()->json([
            'message' => 'Pengajuannya sudah dibatalkan. Sekarang kamu bisa mengubah materinya lagi.',
            'data' => $this->dataEditor($materi->refresh()),
        ]);
    }

    /**
     * POST /api/materi/{materi}/revisi — revisi baru (KF-12).
     */
    public function buatRevisi(Request $request, Materi $materi): JsonResponse
    {
        $this->authorize('buatRevisi', $materi);

        $terakhir = $materi->revisiTerakhir()->first();

        // KP-12.2: tidak boleh ada draf/pengajuan yang menggantung.
        if ($terakhir !== null && in_array(
            $terakhir->status,
            [RevisiMateri::STATUS_DRAF, RevisiMateri::STATUS_DIAJUKAN],
            true
        )) {
            throw ValidationException::withMessages([
                'materi' => 'Masih ada perubahan yang belum selesai. Selesaikan dulu yang itu ya.',
            ]);
        }

        $revisi = DB::transaction(function () use ($materi, $terakhir) {
            // KP-12.3: nomor versi terakhir + 1; semua bagian disalin dari revisi terakhir.
            $baru = $materi->revisi()->create([
                'nomor_versi' => ($terakhir?->nomor_versi ?? 0) + 1,
                'judul' => $terakhir?->judul ?? $materi->judul,
                'ringkasan' => $terakhir?->ringkasan,
                'sampul' => $terakhir?->sampul,
                'konten' => $terakhir?->konten ?? ['ops' => []],
                'konten_html' => $terakhir?->konten_html ?? '',
                'status' => RevisiMateri::STATUS_DRAF,
            ]);

            $baru->kategori()->sync(
                $terakhir ? $terakhir->kategori()->pluck('kategori.id')->all() : []
            );

            // PRD §8.3: materi yang ditolak kembali ke draf saat revisi baru dibuat.
            if ($materi->status === Materi::STATUS_DITOLAK) {
                $materi->update(['status' => Materi::STATUS_DRAF]);
            }

            return $baru;
        });

        return response()->json([
            'message' => 'Sekarang kamu bisa mengubah materinya. Pengunjung tetap melihat versi yang sedang tayang.',
            'data' => $this->dataEditor($materi->refresh()),
        ], 201);
    }

    /**
     * GET /api/materi/{materi}/riwayat — riwayat versi (KF-13).
     *
     * Semua anggota melihat seluruh versi. Keputusan admin (beserta catatannya)
     * hanya dikirim ke penulis dan admin (KP-13.3).
     */
    public function riwayat(Request $request, Materi $materi): JsonResponse
    {
        $bolehLihatKeputusan = $this->bolehLihatKeputusan($request->user(), $materi);

        $riwayat = $materi->revisi() // urut nomor versi terbesar
            ->when($bolehLihatKeputusan, fn ($query) => $query->with('verifikasi.admin'))
            ->get();

        return response()->json([
            'data' => RevisiRingkasResource::collection($riwayat),
        ]);
    }

    /**
     * GET /api/materi/{materi}/riwayat/{revisi} — isi satu versi (KP-13.4).
     *
     * Isi lengkap versi itu (judul, ringkasan, sampul, kategori, isi) dikirim
     * satu per satu saat dibuka; dipakai juga untuk perbandingan KP-10.7.
     * Catatan keputusan admin tetap hanya untuk penulis dan admin (KP-13.3).
     */
    public function versi(Request $request, Materi $materi, RevisiMateri $revisi): JsonResponse
    {
        // Rute bersarang: versi yang diminta harus milik materi pada URL.
        abort_if(
            $revisi->materi_id !== $materi->id,
            404,
            'Versi ini tidak ketemu.'
        );

        $revisi->load('kategori');

        if ($this->bolehLihatKeputusan($request->user(), $materi)) {
            $revisi->load('verifikasi.admin');
        }

        return response()->json(['data' => new RevisiResource($revisi)]);
    }

    /**
     * GET /api/materi/{materi:slug} — halaman baca anggota (KP-16.4).
     *
     * Isi yang tampil adalah revisi terakhir. Versi publik (terakhir) ikut
     * dikirim agar frontend dapat menampilkan "Versi publik: vN".
     */
    public function show(Request $request, Materi $materi): JsonResponse
    {
        $bolehLihatKeputusan = $this->bolehLihatKeputusan($request->user(), $materi);

        $materi->catatDibaca(); // statistik admin (KF-25)

        $materi->load(self::RELASI_INTERNAL);
        $materi->loadCount('komentar');

        // KP-13.3: anggota lain tetap melihat semua versi, tetapi tanpa
        // keputusan admin. Relasi hanya dimuat untuk penulis/admin.
        $materi->setRelation(
            'revisi',
            $materi->revisi()
                ->when($bolehLihatKeputusan, fn ($query) => $query->with('verifikasi.admin'))
                ->get()
        );

        return response()->json(['data' => new MateriDetailResource($materi)]);
    }

    /**
     * Keputusan admin boleh dilihat penulis materi dan admin (KP-13.2).
     */
    private function bolehLihatKeputusan(User $pengguna, Materi $materi): bool
    {
        return $pengguna->id === $materi->penulis_id || $pengguna->isAdmin();
    }

    /**
     * Bagian materi yang disimpan per versi (Draf 2.3, KP-12.4).
     *
     * @param  array<string, mixed>  $data  hasil validasi SimpanMateriRequest
     * @return array<string, mixed>
     */
    private function bagianMateri(array $data): array
    {
        return [
            'judul' => $data['judul'],
            'ringkasan' => $data['ringkasan'] ?? null,
            'sampul' => $data['sampul'] ?? null, // KF-18
            'konten' => $data['konten'],
            'konten_html' => $this->bersihkan($data['konten_html']), // KP-08.4
        ];
    }

    /**
     * Apakah berkas sampul masih dipakai oleh versi mana pun atau data publik materi (KP-18.4).
     */
    private function sampulMasihDipakai(string $path): bool
    {
        return RevisiMateri::where('sampul', $path)->exists()
            || Materi::where('sampul', $path)->exists();
    }

    /**
     * Susunan data untuk editor: materi + revisi terakhir.
     *
     * @return array<string, mixed>
     */
    private function dataEditor(Materi $materi): array
    {
        $materi->load(self::RELASI_INTERNAL);
        $revisi = $materi->revisiTerakhir;

        return [
            'materi' => new MateriRingkasResource($materi),
            // Path asli sampul revisi terakhir (bukan URL seperti di Resource):
            // formulir editor mengirim balik path ini apa adanya saat menyimpan.
            'sampul_path' => $revisi?->sampul,
            // KP-19.2: id kategori revisi terakhir, untuk mencentang pilihan di editor.
            'kategori_ids' => $revisi ? $revisi->kategori->pluck('id')->all() : [],
            'revisi' => $revisi ? new RevisiResource($revisi) : null,
            // Versi publik terakhir untuk perbandingan sebelum mengajukan (KP-10.7).
            'versi_publik_terakhir' => $materi->versiTerbit ? [
                'id' => $materi->versiTerbit->id,
                'nomor_versi' => $materi->versiTerbit->nomor_versi,
            ] : null,
        ];
    }

    /**
     * Sanitasi HTML dari editor sebelum disimpan (KP-08.4) — membuang <script>, onerror, dsb.
     */
    private function bersihkan(string $html): string
    {
        return Purifier::clean($html, 'materi');
    }
}
