<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

// Satu versi materi
class RevisiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'materi_id' => $this->materi_id,
            'nomor_versi' => $this->nomor_versi,
            'status' => $this->status,
            'judul' => $this->judul,
            'ringkasan' => $this->ringkasan,
            'sampul' => $this->sampul ? Storage::disk('public')->url($this->sampul) : null,
            'kategori' => KategoriResource::collection($this->whenLoaded('kategori')),
            'konten' => $this->konten,
            'konten_html' => $this->konten_html,
            'catatan_revisi' => $this->catatan_revisi,
            'dibuat_pada' => $this->created_at?->toIso8601String(),
            'materi' => $this->whenLoaded('materi', fn () => [
                'id' => $this->materi->id,
                'judul' => $this->materi->judul,
                'slug' => $this->materi->slug,
                'status' => $this->materi->status,
                'diterbitkan_pada' => $this->materi->diterbitkan_pada?->toIso8601String(),
                'penulis' => $this->when(
                    $this->materi->relationLoaded('penulis'),
                    fn () => [
                        'id' => $this->materi->penulis->id,
                        'nama' => $this->materi->penulis->nama,
                    ]
                ),
            ]),
            'verifikasi' => $this->whenLoaded('verifikasi', fn () => VerifikasiResource::collection($this->verifikasi)),
        ];
    }
}