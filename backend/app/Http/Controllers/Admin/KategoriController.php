<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kategori\SimpanKategoriRequest;
use App\Http\Resources\KategoriResource;
use App\Models\Kategori;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * CRUD kategori materi (KF-19, KP-19.1).
 */
class KategoriController extends Controller
{
    /**
     * GET /api/admin/kategori — daftar + jumlah materi pemakai.
     */
    public function index(): AnonymousResourceCollection
    {
        return KategoriResource::collection(
            Kategori::withCount('materi')->orderBy('nama')->get()
        );
    }

    /**
     * POST /api/admin/kategori.
     */
    public function store(SimpanKategoriRequest $request): JsonResponse
    {
        $data = $request->validated();

        $kategori = Kategori::create([
            'nama' => $data['nama'],
            'slug' => $this->slugUnik($data['nama']),
        ]);

        return response()->json([
            'message' => 'Kategori ditambahkan.',
            'data' => new KategoriResource($kategori),
        ], 201);
    }

    /**
     * PUT /api/admin/kategori/{kategori}.
     */
    public function update(SimpanKategoriRequest $request, Kategori $kategori): JsonResponse
    {
        $data = $request->validated();

        // Slug mengikuti nama baru agar tautan penyaring tetap terbaca.
        $kategori->update([
            'nama' => $data['nama'],
            'slug' => $this->slugUnik($data['nama'], $kategori->id),
        ]);

        return response()->json([
            'message' => 'Kategori diperbarui.',
            'data' => new KategoriResource($kategori),
        ]);
    }

    /**
     * DELETE /api/admin/kategori/{kategori} — ditolak bila masih dipakai (KP-19.1).
     */
    public function destroy(Kategori $kategori): JsonResponse
    {
        // KP-19.1: kategori yang dipakai versi publik atau versi materi mana pun
        // tidak boleh dihapus, supaya riwayat versi tetap utuh (Draf 2.3).
        if ($kategori->materi()->exists() || $kategori->revisi()->exists()) {
            throw ValidationException::withMessages([
                'kategori' => 'Kategori ini masih dipakai oleh materi sehingga belum dapat dihapus.',
            ]);
        }

        $kategori->delete();

        return response()->json(['message' => 'Kategori dihapus.']);
    }

    /**
     * Slug unik dari nama kategori (kolom `slug` bersifat unique).
     */
    private function slugUnik(string $nama, ?int $abaikanId = null): string
    {
        $dasar = Str::slug($nama) ?: 'kategori';
        $slug = $dasar;
        $nomor = 1;

        while (Kategori::where('slug', $slug)
            ->when($abaikanId !== null, fn ($query) => $query->where('id', '!=', $abaikanId))
            ->exists()
        ) {
            $slug = $dasar.'-'.(++$nomor);
        }

        return $slug;
    }
}
