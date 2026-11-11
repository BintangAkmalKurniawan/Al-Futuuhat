<?php

namespace App\Http\Controllers;

use App\Http\Resources\MateriPublikDetailResource;
use App\Http\Resources\MateriPublikResource;
use App\Models\Materi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Materi untuk pengunjung tanpa login (KF-17).
 *
 * Hanya materi berstatus `terbit`, dan isinya selalu versi publik
 * (revisi `disetujui` terakhir), bukan revisi terbaru.
 */
class MateriPublikController extends Controller
{
    /**
     * GET /api/publik/materi?q=&page= — daftar materi terbit (KP-17.1).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $q = trim((string) $request->query('q', ''));
        $kategori = trim((string) $request->query('kategori', ''));

        $materi = Materi::query()
            ->where('status', Materi::STATUS_TERBIT)
            ->with(['penulis', 'versiTerbit', 'kategori'])
            // KP-19.4: penyaring kategori, digabung dengan pencarian kata kunci.
            ->when($kategori !== '', fn ($query) => $query->whereHas(
                'kategori',
                fn ($sub) => $sub->where('kategori.slug', $kategori)
            ))
            // Pencarian hanya atas judul dan isi versi publik (KP-17.5).
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->whereRaw(
                        "to_tsvector('simple', materi.judul) @@ plainto_tsquery('simple', ?)",
                        [$q]
                    )->orWhereHas('versiTerbit', fn ($versi) => $versi->whereRaw(
                        "to_tsvector('simple', regexp_replace(revisi_materi.konten_html, '<[^>]+>', ' ', 'g'))"
                        ." @@ plainto_tsquery('simple', ?)",
                        [$q]
                    ));
                });
            })
            ->orderByDesc('diterbitkan_pada')
            ->paginate(10)
            ->withQueryString();

        return MateriPublikResource::collection($materi);
    }

    /**
     * GET /api/publik/materi/{slug} — baca satu materi terbit (KP-17.2, KP-17.3).
     */
    public function show(Materi $materi): JsonResponse
    {
        // Materi yang belum terbit tidak boleh dibuka lewat URL publik.
        abort_unless($materi->status === Materi::STATUS_TERBIT, 404, 'Tulisannya tidak ketemu. Mungkin sudah dihapus.');

        $materi->catatDibaca(); // statistik admin (KF-25)

        $materi->load(['penulis', 'versiTerbit', 'kategori']);

        return response()->json(['data' => new MateriPublikDetailResource($materi)]);
    }
}