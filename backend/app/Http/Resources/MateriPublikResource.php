<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Materi untuk halaman publikasi: hanya data yang boleh dilihat pengunjung
 * (KP-17.1). Tidak ada riwayat, forum, maupun data pribadi penulis.
 */
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
            // Gambar sampul kartu artikel publik (KF-18).
            'sampul' => $this->sampul ? Storage::disk('public')->url($this->sampul) : null,
            'penulis' => $this->whenLoaded('penulis', fn () => [
                'nama' => $this->penulis->nama,
            ]),
            'diterbitkan_pada' => $this->diterbitkan_pada?->toIso8601String(),
            // Kategori artikel untuk chip di kartu `/publikasi` (KF-19, KP-19.4).
            'kategori' => KategoriResource::collection($this->whenLoaded('kategori')),
            // Perkiraan waktu baca untuk baris daftar publikasi (DESIGN.md §9).
            'menit_baca' => $this->when(
                $this->relationLoaded('versiTerbit') || $this->relationLoaded('revisiTerakhir'),
                fn () => $this->menitBaca()
            ),
        ];
    }
}