<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

// Materi untuk halaman publikasi
class MateriPublikResource extends JsonResource
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
            'sampul' => $this->sampul ? Storage::disk('public')->url($this->sampul) : null,
            'penulis' => $this->whenLoaded('penulis', fn () => [
                'nama' => $this->penulis->nama,
            ]),
            'diterbitkan_pada' => $this->diterbitkan_pada?->toIso8601String(),
            'kategori' => KategoriResource::collection($this->whenLoaded('kategori')),
            'menit_baca' => $this->when(
                $this->relationLoaded('versiTerbit') || $this->relationLoaded('revisiTerakhir'),
                fn () => $this->menitBaca()
            ),
        ];
    }
}