<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Kategori materi (KF-19). Dipakai daftar publik (penyaring), halaman admin,
 * dan chip kategori pada materi.
 */
class KategoriResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'slug' => $this->slug,
            // Jumlah materi pemakai; hanya ikut bila diminta withCount (halaman admin).
            'jumlah_materi' => $this->whenCounted('materi'),
        ];
    }
}
