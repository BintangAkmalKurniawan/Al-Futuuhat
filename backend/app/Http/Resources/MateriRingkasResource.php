<?php

namespace App\Http\Resources;

use App\Models\Materi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

// Materi untuk daftar (arsip / materi saya)
class MateriRingkasResource extends JsonResource
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
            'penulis' => $this->whenLoaded('penulis', fn () => [
                'id' => $this->penulis->id,
                'nama' => $this->penulis->nama,
            ]),
            'diterbitkan_pada' => $this->diterbitkan_pada?->toIso8601String(),
            'nomor_versi' => $this->when(
                $this->relationLoaded('revisiTerakhir') || $this->relationLoaded('versiTerbit'),
                fn () => ($this->relationLoaded('revisiTerakhir')
                    ? $this->revisiTerakhir?->nomor_versi
                    : null) ?? $this->versiTerbit?->nomor_versi
            ),
            'revisi_terakhir' => $this->whenLoaded('revisiTerakhir', fn () => $this->revisiTerakhir ? [
                'id' => $this->revisiTerakhir->id,
                'nomor_versi' => $this->revisiTerakhir->nomor_versi,
                'status' => $this->revisiTerakhir->status,
            ] : null),
            'versi_publik' => $this->whenLoaded('versiTerbit', fn () => $this->status === Materi::STATUS_TERBIT && $this->versiTerbit
                ? ['id' => $this->versiTerbit->id, 'nomor_versi' => $this->versiTerbit->nomor_versi]
                : null),
            'versi_publik_terakhir' => $this->whenLoaded('versiTerbit', fn () => $this->versiTerbit
                ? ['id' => $this->versiTerbit->id, 'nomor_versi' => $this->versiTerbit->nomor_versi]
                : null),
            'penarikan' => $this->whenLoaded('penarikanMenunggu', fn () => $this->penarikanMenunggu
                ? new PenarikanResource($this->penarikanMenunggu->setRelation('materi', $this->resource))
                : null),
            'jumlah_komentar' => $this->whenCounted('komentar'),
            'kategori' => $versi && $versi->relationLoaded('kategori')
                ? KategoriResource::collection($versi->kategori)
                : KategoriResource::collection($this->whenLoaded('kategori')),
            'menit_baca' => $this->when(
                $this->relationLoaded('revisiTerakhir') || $this->relationLoaded('versiTerbit'),
                fn () => $this->menitBaca()
            ),
            'dibuat_pada' => $this->created_at?->toIso8601String(),
            'diperbarui_pada' => $this->updated_at?->toIso8601String(),
        ];
    }
}