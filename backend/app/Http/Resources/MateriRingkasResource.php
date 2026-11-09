<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Materi untuk daftar (arsip / materi saya). Tanpa isi konten agar ringan.
 */
class MateriRingkasResource extends JsonResource
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
            'penulis' => $this->whenLoaded('penulis', fn () => [
                'id' => $this->penulis->id,
                'nama' => $this->penulis->nama,
            ]),
            'diterbitkan_pada' => $this->diterbitkan_pada?->toIso8601String(),
            'nomor_versi' => $this->whenLoaded('versiTerbit', fn () => $this->versiTerbit?->nomor_versi),
            'revisi_terakhir' => $this->whenLoaded('revisiTerakhir', fn () => $this->revisiTerakhir ? [
                'id' => $this->revisiTerakhir->id,
                'nomor_versi' => $this->revisiTerakhir->nomor_versi,
                'status' => $this->revisiTerakhir->status,
            ] : null),
            'jumlah_komentar' => $this->whenCounted('komentar'),
            'dibuat_pada' => $this->created_at?->toIso8601String(),
            'diperbarui_pada' => $this->updated_at?->toIso8601String(),
        ];
    }
}