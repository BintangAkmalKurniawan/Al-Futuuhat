<?php

namespace App\Http\Resources;

use App\Models\Materi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

// Detail materi untuk halaman baca anggota
class MateriDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $versi = $this->relationLoaded('revisiTerakhir') ? $this->revisiTerakhir : null;
        $sampul = $versi ? $versi->sampul : $this->sampul;

        return [
            'id' => $this->id,
            'judul' => $versi?->judul ?? $this->judul,
            'slug' => $this->slug,
            'ringkasan' => $versi ? $versi->ringkasan : $this->ringkasan,
            'sampul' => $sampul ? Storage::disk('public')->url($sampul) : null,
            'status' => $this->status,
            'diterbitkan_pada' => $this->diterbitkan_pada?->toIso8601String(),
            'penulis' => $this->whenLoaded('penulis', fn () => [
                'id' => $this->penulis->id,
                'nama' => $this->penulis->nama,
            ]),
            'revisi' => $this->whenLoaded('revisiTerakhir', fn () => $this->revisiTerakhir
                ? new RevisiResource($this->revisiTerakhir)
                : null),
            'versi_publik' => $this->whenLoaded('versiTerbit', fn () => $this->status === Materi::STATUS_TERBIT && $this->versiTerbit
                ? ['id' => $this->versiTerbit->id, 'nomor_versi' => $this->versiTerbit->nomor_versi]
                : null),
            'versi_publik_terakhir' => $this->whenLoaded('versiTerbit', fn () => $this->versiTerbit
                ? ['id' => $this->versiTerbit->id, 'nomor_versi' => $this->versiTerbit->nomor_versi]
                : null),
            'penarikan' => $this->whenLoaded('penarikanMenunggu', fn () => $this->penarikanMenunggu
                ? new PenarikanResource($this->penarikanMenunggu->setRelation('materi', $this->resource))
                : null),
            'kategori' => $versi && $versi->relationLoaded('kategori')
                ? KategoriResource::collection($versi->kategori)
                : KategoriResource::collection($this->whenLoaded('kategori')),
            'riwayat' => $this->whenLoaded('revisi', fn () => RevisiRingkasResource::collection($this->revisi)),
            'jumlah_komentar' => $this->whenCounted('komentar'),
        ];
    }
}
