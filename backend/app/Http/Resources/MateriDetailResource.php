<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Detail materi untuk halaman baca: isi versi terbit + riwayat versi (KP-16.4).
 */
class MateriDetailResource extends JsonResource
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
            'status' => $this->status,
            'diterbitkan_pada' => $this->diterbitkan_pada?->toIso8601String(),
            'penulis' => $this->whenLoaded('penulis', fn () => [
                'id' => $this->penulis->id,
                'nama' => $this->penulis->nama,
            ]),
            // Isi yang sedang tampil ke pembaca (versi disetujui terbesar)
            'revisi' => $this->whenLoaded('versiTerbit', fn () => $this->versiTerbit
                ? new RevisiResource($this->versiTerbit)
                : null),
            'riwayat' => $this->whenLoaded('revisi', fn () => RevisiRingkasResource::collection($this->revisi)),
            'jumlah_komentar' => $this->whenCounted('komentar'),
        ];
    }
}