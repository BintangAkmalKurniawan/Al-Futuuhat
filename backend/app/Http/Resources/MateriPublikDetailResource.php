<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Isi materi untuk halaman baca publik (KP-17.2, KP-17.4).
 *
 * Hanya versi publik (`disetujui` dengan nomor terbesar) yang dikirim,
 * meskipun ada revisi lebih baru yang belum disetujui.
 */
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
            // Kategori artikel untuk chip di halaman baca publik (KF-19, KP-19.4).
            'kategori' => KategoriResource::collection($this->whenLoaded('kategori')),
            // Isi versi publik; null bila materi terbit tanpa versi disetujui.
            'konten_html' => $this->whenLoaded(
                'versiTerbit',
                fn () => $this->versiTerbit?->konten_html
            ),
        ];
    }
}