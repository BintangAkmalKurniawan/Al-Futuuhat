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

// Materi anggota
class MateriController extends Controller
{
    private const RELASI_INTERNAL = ['penulis', 'versiTerbit', 'revisiTerakhir.kategori', 'penarikanMenunggu'];

    // Arsip materi anggota
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
            ->when($kategori !== '', fn ($query) => $query->whereHas(
                'revisiTerakhir.kategori',
                fn ($sub) => $sub->where('kategori.slug', $kategori)
            ))
            ->when($penulis !== '', fn ($query) => $query->whereHas(
                'penulis',
                fn ($sub) => $sub->where('nama', 'ilike', "%{$penulis}%")
            ))
            ->when($q !== '', fn ($query) => $this->saringKataKunci($query, $q))
            ->orderByDesc('materi.updated_at')
            ->paginate(10)
            ->withQueryString();

        return MateriRingkasResource::collection($materi);
    }

    // Ubah kata kunci menjadi tsquery
    private function tsqueryAwalan(string $q): string
    {
        $potongan = preg_split('/[^\p{L}\p{N}]+/u', $q, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return implode(' & ', array_map(fn (string $kata) => $kata.':*', $potongan));
    }

    // Saring berdasarkan kata kunci
    private function saringKataKunci(Builder $query, string $q, bool $cariPenulis = true): void
    {
        $tsquery = $this->tsqueryAwalan($q);

        if ($tsquery === '') {
            return;
        }

        $query->where(function (Builder $sub) use ($q, $tsquery, $cariPenulis) {
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

    // Daftar materi milik pengguna
    public function materiSaya(Request $request): AnonymousResourceCollection
    {
        $this->authorize('buat', Materi::class);

        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status');

        $materi = Materi::query()
            ->where('penulis_id', $request->user()->id)
            ->with(self::RELASI_INTERNAL)
            ->withCount('komentar')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($q !== '', fn ($query) => $this->saringKataKunci($query, $q, false))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return MateriRingkasResource::collection($materi);
    }

    // Simpan materi baru
    public function store(SimpanMateriRequest $request): JsonResponse
    {
        $data = $request->validated();

        $materi = DB::transaction(function () use ($request, $data) {
            $materi = Materi::create([
                'penulis_id' => $request->user()->id,
                'judul' => $data['judul'],
                'slug' => Materi::slugUnik($data['judul']),
                'ringkasan' => $data['ringkasan'] ?? null,
                'sampul' => $data['sampul'] ?? null,
                'status' => Materi::STATUS_DRAF,
            ]);

            $revisi = $materi->revisi()->create([
                'nomor_versi' => 1,
                ...$this->bagianMateri($data),
                'catatan_revisi' => $data['catatan_revisi'] ?? null,
                'status' => RevisiMateri::STATUS_DRAF,
            ]);

            $revisi->kategori()->sync($data['kategori']);
            $materi->kategori()->sync($data['kategori']);

            return $materi;
        });

        return response()->json([
            'message' => 'Materimu sudah disimpan. Semua anggota sudah bisa membacanya.',
            'data' => $this->dataEditor($materi),
        ], 201);
    }

    // Data materi untuk editor
    public function edit(Request $request, Materi $materi): JsonResponse
    {
        $this->authorize('ubah', $materi);

        return response()->json(['data' => $this->dataEditor($materi)]);
    }

    // Ubah draf
    public function update(SimpanMateriRequest $request, Materi $materi): JsonResponse
    {
        $this->authorize('ubah', $materi);

        $revisi = $materi->revisiTerakhir()->first();

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
                $materi->touch();
            } else {
                $materi->samakanDenganRevisi($revisi);
            }
        });

        if ($sampulLama !== null && $sampulLama !== $revisi->sampul && ! $this->sampulMasihDipakai($sampulLama)) {
            Storage::disk('public')->delete($sampulLama);
        }

        return response()->json([
            'message' => 'Perubahanmu sudah disimpan.',
            'data' => $this->dataEditor($materi->refresh()),
        ]);
    }

    // Hapus materi
    public function destroy(Request $request, Materi $materi): JsonResponse
    {
        $this->authorize('hapus', $materi);

        if ($materi->status !== Materi::STATUS_DRAF || $materi->diterbitkan_pada !== null) {
            throw ValidationException::withMessages([
                'materi' => 'Materi yang pernah tayang atau lagi dicek admin tidak bisa dihapus.',
            ]);
        }

        $materi->delete();

        return response()->json(['message' => 'Materinya sudah dihapus.']);
    }

    // Ajukan publikasi
    public function ajukan(Request $request, Materi $materi): JsonResponse
    {
        $this->authorize('ajukan', $materi);

        if ($materi->status === Materi::STATUS_TERBIT) {
            throw ValidationException::withMessages([
                'materi' => 'Materi ini masih tayang. Turunkan dulu dari halaman umum, baru ajukan versi barunya ya.',
            ]);
        }

        $revisi = $materi->revisiTerakhir()->first();

        if ($revisi === null || $revisi->status !== RevisiMateri::STATUS_DRAF) {
            throw ValidationException::withMessages([
                'materi' => 'Materi ini sudah diajukan atau sudah diputuskan admin.',
            ]);
        }

        DB::transaction(function () use ($materi, $revisi) {
            $revisi->update(['status' => RevisiMateri::STATUS_DIAJUKAN]);
            $materi->update(['status' => Materi::STATUS_DIAJUKAN]);
        });

        $admin = User::where('role', User::ROLE_ADMIN)
            ->where('status', User::STATUS_AKTIF)
            ->get();

        Notification::send($admin, new MateriDiajukan($materi, $revisi, $request->user()));

        return response()->json([
            'message' => 'Materimu sudah diajukan dan lagi dicek admin. Nanti kami kabari lewat notifikasi ya.',
            'data' => $this->dataEditor($materi->refresh()),
        ], 201);
    }

    // Batalkan pengajuan
    public function batalAjukan(Request $request, Materi $materi): JsonResponse
    {
        $this->authorize('ajukan', $materi);

        $revisi = $materi->revisiTerakhir()->first();

        if ($revisi === null || $revisi->status !== RevisiMateri::STATUS_DIAJUKAN) {
            throw ValidationException::withMessages([
                'materi' => 'Tidak ada pengajuan yang bisa dibatalkan.',
            ]);
        }

        DB::transaction(function () use ($materi, $revisi) {
            $revisi->update(['status' => RevisiMateri::STATUS_DRAF]);

            if ($materi->status === Materi::STATUS_DIAJUKAN) {
                $materi->update(['status' => Materi::STATUS_DRAF]);
            }
        });

        return response()->json([
            'message' => 'Pengajuannya sudah dibatalkan. Sekarang kamu bisa mengubah materinya lagi.',
            'data' => $this->dataEditor($materi->refresh()),
        ]);
    }

    // Buat revisi baru
    public function buatRevisi(Request $request, Materi $materi): JsonResponse
    {
        $this->authorize('buatRevisi', $materi);

        $terakhir = $materi->revisiTerakhir()->first();

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

    // Riwayat versi
    public function riwayat(Request $request, Materi $materi): JsonResponse
    {
        $bolehLihatKeputusan = $this->bolehLihatKeputusan($request->user(), $materi);

        $riwayat = $materi->revisi()
            ->when($bolehLihatKeputusan, fn ($query) => $query->with('verifikasi.admin'))
            ->get();

        return response()->json([
            'data' => RevisiRingkasResource::collection($riwayat),
        ]);
    }

    // Isi satu versi
    public function versi(Request $request, Materi $materi, RevisiMateri $revisi): JsonResponse
    {
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

    // Halaman baca anggota
    public function show(Request $request, Materi $materi): JsonResponse
    {
        $bolehLihatKeputusan = $this->bolehLihatKeputusan($request->user(), $materi);

        $materi->catatDibaca();

        $materi->load(self::RELASI_INTERNAL);
        $materi->loadCount('komentar');

        $materi->setRelation(
            'revisi',
            $materi->revisi()
                ->when($bolehLihatKeputusan, fn ($query) => $query->with('verifikasi.admin'))
                ->get()
        );

        return response()->json(['data' => new MateriDetailResource($materi)]);
    }

    // Cek hak melihat keputusan admin
    private function bolehLihatKeputusan(User $pengguna, Materi $materi): bool
    {
        return $pengguna->id === $materi->penulis_id || $pengguna->isAdmin();
    }

    /**
     * Bagian materi yang disimpan per versi
     *
     * @param  array<string, mixed>  $data  hasil validasi SimpanMateriRequest
     * @return array<string, mixed>
     */
    private function bagianMateri(array $data): array
    {
        return [
            'judul' => $data['judul'],
            'ringkasan' => $data['ringkasan'] ?? null,
            'sampul' => $data['sampul'] ?? null,
            'konten' => $data['konten'],
            'konten_html' => $this->bersihkan($data['konten_html']),
        ];
    }

    // Cek sampul masih dipakai
    private function sampulMasihDipakai(string $path): bool
    {
        return RevisiMateri::where('sampul', $path)->exists()
            || Materi::where('sampul', $path)->exists();
    }

    /**
     * Susunan data untuk editor
     *
     * @return array<string, mixed>
     */
    private function dataEditor(Materi $materi): array
    {
        $materi->load(self::RELASI_INTERNAL);
        $revisi = $materi->revisiTerakhir;

        return [
            'materi' => new MateriRingkasResource($materi),
            'sampul_path' => $revisi?->sampul,
            'kategori_ids' => $revisi ? $revisi->kategori->pluck('id')->all() : [],
            'revisi' => $revisi ? new RevisiResource($revisi) : null,
            'versi_publik_terakhir' => $materi->versiTerbit ? [
                'id' => $materi->versiTerbit->id,
                'nomor_versi' => $materi->versiTerbit->nomor_versi,
            ] : null,
        ];
    }

    // Sanitasi HTML
    private function bersihkan(string $html): string
    {
        return Purifier::clean($html, 'materi');
    }
}
