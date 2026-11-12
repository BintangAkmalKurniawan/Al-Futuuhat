<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Isi materi untuk halaman baca publik
class MateriPublikDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'judul' => $this->judul,
            'slug' => $this->slug,
            'ringkasan' => $this->ringkasan,
            'penulis' => $this->whenLoaded('penulis', fn () => [
                'nama' => $this->penulis->nama,
            ]),
            'diterbitkan_pada' => $this->diterbitkan_pada?->toIso8601String(),
            'kategori' => KategoriResource::collection($this->whenLoaded('kategori')),
            'konten_html' => $this->whenLoaded(
                'versiTerbit',
                fn () => $this->versiTerbit?->konten_html
            ),
        ];
    }
}