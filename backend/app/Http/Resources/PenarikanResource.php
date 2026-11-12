<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Pengajuan penarikan materi dari publik
class PenarikanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $pengguna = $request->user();
        $bolehLihatRincian = $pengguna !== null && (
            $pengguna->isAdmin()
            || ($this->relationLoaded('materi') && $this->materi->penulis_id === $pengguna->id)
        );

        return [
            'id' => $this->id,
            'status' => $this->status,
            'alasan' => $this->when($bolehLihatRincian, $this->alasan),
            'catatan' => $this->when($bolehLihatRincian, $this->catatan),
            'diajukan_pada' => $this->created_at?->toIso8601String(),
            'diputuskan_pada' => $this->diputuskan_pada?->toIso8601String(),
            'materi' => $this->whenLoaded('materi', fn () => [
                'id' => $this->materi->id,
                'judul' => $this->materi->judul,
                'slug' => $this->materi->slug,
                'status' => $this->materi->status,
                'penulis' => $this->when(
                    $this->materi->relationLoaded('penulis'),
                    fn () => [
                        'id' => $this->materi->penulis->id,
                        'nama' => $this->materi->penulis->nama,
                    ]
                ),
            ]),
        ];
    }
}
