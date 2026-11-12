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

// CRUD kategori materi
class KategoriController extends Controller
{
    // Daftar kategori
    public function index(): AnonymousResourceCollection
    {
        return KategoriResource::collection(
            Kategori::withCount('materi')->orderBy('nama')->get()
        );
    }

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

    public function update(SimpanKategoriRequest $request, Kategori $kategori): JsonResponse
    {
        $data = $request->validated();

        $kategori->update([
            'nama' => $data['nama'],
            'slug' => $this->slugUnik($data['nama'], $kategori->id),
        ]);

        return response()->json([
            'message' => 'Kategori diperbarui.',
            'data' => new KategoriResource($kategori),
        ]);
    }

    // Hapus kategori
    public function destroy(Kategori $kategori): JsonResponse
    {
        if ($kategori->materi()->exists() || $kategori->revisi()->exists()) {
            throw ValidationException::withMessages([
                'kategori' => 'Kategori ini masih dipakai oleh materi sehingga belum dapat dihapus.',
            ]);
        }

        $kategori->delete();

        return response()->json(['message' => 'Kategori dihapus.']);
    }

    // Buat slug unik
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
