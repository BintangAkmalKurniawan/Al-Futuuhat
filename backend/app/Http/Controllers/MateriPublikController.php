<?php

namespace App\Http\Controllers;

use App\Http\Resources\MateriPublikDetailResource;
use App\Http\Resources\MateriPublikResource;
use App\Models\Materi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

// Materi untuk pengunjung tanpa login
class MateriPublikController extends Controller
{
    // Daftar materi terbit
    public function index(Request $request): AnonymousResourceCollection
    {
        $q = trim((string) $request->query('q', ''));
        $kategori = trim((string) $request->query('kategori', ''));

        $materi = Materi::query()
            ->where('status', Materi::STATUS_TERBIT)
            ->with(['penulis', 'versiTerbit', 'kategori'])
            ->when($kategori !== '', fn ($query) => $query->whereHas(
                'kategori',
                fn ($sub) => $sub->where('kategori.slug', $kategori)
            ))
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

    // Baca satu materi terbit
    public function show(Materi $materi): JsonResponse
    {
        abort_unless($materi->status === Materi::STATUS_TERBIT, 404, 'Tulisannya tidak ketemu. Mungkin sudah dihapus.');

        $materi->catatDibaca();

        $materi->load(['penulis', 'versiTerbit', 'kategori']);

        return response()->json(['data' => new MateriPublikDetailResource($materi)]);
    }
}