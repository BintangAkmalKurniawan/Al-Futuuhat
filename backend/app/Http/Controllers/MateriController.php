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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Mews\Purifier\Facades\Purifier;

/**
 * Materi: tulis draf, ubah, hapus, ajukan, revisi, arsip, dan baca
 * (KF-08 s.d. KF-13, KF-16).
 */
class MateriController extends Controller
{
    /**
     * GET /api/materi?q=&penulis=&page= — arsip materi terbit (KF-16).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $q = trim((string) $request->query('q', ''));
        $penulis = trim((string) $request->query('penulis', ''));

        $materi = Materi::query()
            ->where('status', Materi::STATUS_TERBIT) // KP-16.1: hanya materi terbit
            ->with(['penulis', 'versiTerbit'])      // KNF-09: eager loading
            ->withCount('komentar')
            ->when($penulis !== '', fn ($query) => $query->whereHas(
                'penulis',
                fn ($sub) => $sub->where('nama', 'ilike', "%{$penulis}%")
            ))
            // Pencarian penuh PostgreSQL atas judul dan isi versi terbit.
            ->when($q !== '', fn ($query) => $query->where(function ($sub) use ($q) {
                $sub->whereRaw(
                    "to_tsvector('simple', materi.judul) @@ plainto_tsquery('simple', ?)",
                    [$q]
                )->orWhereHas('versiTerbit', fn ($versi) => $versi->whereRaw(
                    "to_tsvector('simple', regexp_replace(revisi_materi.konten_html, '<[^>]+>', ' ', 'g'))"
                    ." @@ plainto_tsquery('simple', ?)",
                    [$q]
                ));
            }))
            ->latest('diterbitkan_pada')
            ->paginate(10)
            ->withQueryString();

        return MateriRingkasResource::collection($materi);
    }

    /**
     * GET /api/materi-saya?status= — semua materi milik pengguna.
     */
    public function materiSaya(Request $request): AnonymousResourceCollection
    {
        $status = $request->query('status');

        $materi = Materi::query()
            ->where('penulis_id', $request->user()->id)
            ->with(['penulis', 'versiTerbit', 'revisiTerakhir'])
            ->withCount('komentar')
            ->when($status, fn ($query) => $query->where('status', $status))
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
                'slug' => $this->slugUnik($data['judul']),
                'ringkasan' => $data['ringkasan'] ?? null,
                'status' => Materi::STATUS_DRAF,
            ]);

            $materi->revisi()->create([
                'nomor_versi' => 1,
                'konten' => $data['konten'],
                'konten_html' => $this->bersihkan($data['konten_html']), // KP-08.4
                'catatan_revisi' => $data['catatan_revisi'] ?? null,
                'status' => RevisiMateri::STATUS_DRAF,
            ]);

            return $materi;
        });

        return response()->json([
            'message' => 'Materi disimpan sebagai draf.',
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
     */
    public function update(SimpanMateriRequest $request, Materi $materi): JsonResponse
    {
        $this->authorize('ubah', $materi);

        $revisi = $materi->revisiTerakhir()->first();

        // KP-09.1 & KP-10.4: hanya revisi terakhir yang masih draf boleh diubah.
        if ($revisi === null || $revisi->status !== RevisiMateri::STATUS_DRAF) {
            throw ValidationException::withMessages([
                'judul' => 'Materi hanya dapat diubah saat revisi terakhir masih berstatus draf.',
            ]);
        }

        $data = $request->validated();
        $pernahTerbit = $materi->diterbitkan_pada !== null;

        DB::transaction(function () use ($materi, $revisi, $data, $pernahTerbit) {
            // KP-12.4: materi yang sudah pernah terbit hanya boleh diubah isinya.
            if (! $pernahTerbit) {
                $materi->update([
                    'judul' => $data['judul'],
                    'slug' => $this->slugUnik($data['judul'], $materi->id),
                    'ringkasan' => $data['ringkasan'] ?? null,
                ]);
            }

            $revisi->update([
                'konten' => $data['konten'],
                'konten_html' => $this->bersihkan($data['konten_html']),
                'catatan_revisi' => $data['catatan_revisi'] ?? $revisi->catatan_revisi,
            ]);
        });

        return response()->json([
            'message' => 'Draf materi diperbarui.',
            'data' => $this->dataEditor($materi->refresh()),
        ]);
    }

    /**
     * DELETE /api/materi/{materi} — hanya draf yang belum pernah terbit (KP-09.2).
     */
    public function destroy(Request $request, Materi $materi): JsonResponse
    {
        $this->authorize('hapus', $materi);

        if ($materi->status !== Materi::STATUS_DRAF || $materi->diterbitkan_pada !== null) {
            throw ValidationException::withMessages([
                'materi' => 'Materi hanya dapat dihapus bila masih draf dan belum pernah terbit.',
            ]);
        }

        // Revisi dan komentar ikut terhapus lewat ON DELETE CASCADE (KNF-06).
        $materi->delete();

        return response()->json(['message' => 'Materi dihapus.']);
    }

    /**
     * POST /api/materi/{materi}/ajukan — ajukan publikasi (KF-10).
     */
    public function ajukan(Request $request, Materi $materi): JsonResponse
    {
        $this->authorize('ajukan', $materi);

        $revisi = $materi->revisiTerakhir()->first();

        // KP-10.1: hanya revisi draf yang bisa diajukan.
        if ($revisi === null || $revisi->status !== RevisiMateri::STATUS_DRAF) {
            throw ValidationException::withMessages([
                'materi' => 'Hanya revisi berstatus draf yang dapat diajukan.',
            ]);
        }

        DB::transaction(function () use ($materi, $revisi) {
            $revisi->update(['status' => RevisiMateri::STATUS_DIAJUKAN]);

            // KP-10.2: materi yang belum pernah terbit ikut berstatus diajukan.
            if ($materi->diterbitkan_pada === null) {
                $materi->update(['status' => Materi::STATUS_DIAJUKAN]);
            }
        });

        // KP-10.3: semua admin aktif menerima notifikasi dalam aplikasi.
        $admin = User::where('role', User::ROLE_ADMIN)
            ->where('status', User::STATUS_AKTIF)
            ->get();

        Notification::send($admin, new MateriDiajukan($materi, $revisi, $request->user()));

        return response()->json([
            'message' => 'Materi diajukan. Menunggu verifikasi admin.',
            'data' => $this->dataEditor($materi->refresh()),
        ], 201);
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
                'materi' => 'Masih ada revisi draf atau pengajuan yang belum selesai.',
            ]);
        }

        $revisi = DB::transaction(function () use ($materi, $terakhir) {
            // KP-12.3: nomor versi terakhir + 1, isi disalin dari revisi terakhir.
            $baru = $materi->revisi()->create([
                'nomor_versi' => ($terakhir?->nomor_versi ?? 0) + 1,
                'konten' => $terakhir?->konten ?? ['ops' => []],
                'konten_html' => $terakhir?->konten_html ?? '',
                'status' => RevisiMateri::STATUS_DRAF,
            ]);

            // PRD §8.2: materi yang ditolak kembali ke draf saat revisi baru dibuat.
            if ($materi->status === Materi::STATUS_DITOLAK) {
                $materi->update(['status' => Materi::STATUS_DRAF]);
            }

            return $baru;
        });

        return response()->json([
            'message' => 'Revisi versi '.$revisi->nomor_versi.' dibuat.',
            'data' => $this->dataEditor($materi->refresh()),
        ], 201);
    }

    /**
     * GET /api/materi/{materi}/riwayat — riwayat versi & keputusan admin (KF-13).
     */
    public function riwayat(Request $request, Materi $materi): JsonResponse
    {
        $user = $request->user();
        $bolehSemuaVersi = $user->isAdmin() || $user->id === $materi->penulis_id;

        // Anggota umum tidak boleh mengintip materi yang belum terbit.
        if (! $bolehSemuaVersi && $materi->status !== Materi::STATUS_TERBIT) {
            abort(404, 'Materi tidak ditemukan.');
        }

        $riwayat = $materi->revisi() // urut nomor versi terbesar
            ->with('verifikasi.admin')
            ->when(! $bolehSemuaVersi, fn ($query) => $query->where(
                'status',
                RevisiMateri::STATUS_DISETUJUI // KP-13.3
            ))
            ->get();

        return response()->json([
            'data' => RevisiRingkasResource::collection($riwayat),
        ]);
    }

    /**
     * GET /api/materi/{materi:slug} — halaman baca materi terbit (KP-16.4).
     */
    public function show(Request $request, Materi $materi): JsonResponse
    {
        // Hanya materi terbit yang bisa dibaca lewat endpoint ini.
        abort_unless($materi->status === Materi::STATUS_TERBIT, 404, 'Materi tidak ditemukan.');

        $user = $request->user();
        $bolehSemuaVersi = $user->isAdmin() || $user->id === $materi->penulis_id;

        $materi->load(['penulis', 'versiTerbit.verifikasi.admin']);
        $materi->loadCount('komentar');

        // Riwayat versi: penulis & admin melihat semua, anggota lain hanya yang disetujui.
        $riwayat = $materi->revisi()
            ->with('verifikasi.admin')
            ->when(! $bolehSemuaVersi, fn ($query) => $query->where(
                'status',
                RevisiMateri::STATUS_DISETUJUI
            ))
            ->get();

        $materi->setRelation('revisi', $riwayat);

        return response()->json(['data' => new MateriDetailResource($materi)]);
    }

    /**
     * Susunan data untuk editor: materi + revisi terakhir.
     *
     * @return array<string, mixed>
     */
    private function dataEditor(Materi $materi): array
    {
        $materi->load(['penulis', 'versiTerbit', 'revisiTerakhir']);

        return [
            'materi' => new MateriRingkasResource($materi),
            'revisi' => $materi->revisiTerakhir ? new RevisiResource($materi->revisiTerakhir) : null,
            // KP-12.4: judul & ringkasan terkunci bila materi sudah pernah terbit
            'bisa_ubah_judul' => $materi->diterbitkan_pada === null,
        ];
    }

    /**
     * Slug unik dari judul (kolom `slug` bersifat unik).
     */
    private function slugUnik(string $judul, ?int $abaikanId = null): string
    {
        $dasar = Str::slug($judul) ?: 'materi';
        $slug = $dasar;
        $nomor = 1;

        while (Materi::where('slug', $slug)
            ->when($abaikanId !== null, fn ($query) => $query->where('id', '!=', $abaikanId))
            ->exists()
        ) {
            $slug = $dasar.'-'.(++$nomor);
        }

        return $slug;
    }

    /**
     * Sanitasi HTML dari editor sebelum disimpan (KP-08.4) — membuang <script>, onerror, dsb.
     */
    private function bersihkan(string $html): string
    {
        return Purifier::clean($html, 'materi');
    }
}