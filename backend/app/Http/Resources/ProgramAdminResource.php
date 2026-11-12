<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

// Program untuk halaman admin
class ProgramAdminResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'slug' => $this->slug,
            'ringkasan' => $this->ringkasan,
            'jadwal' => $this->jadwal,
            'sampul' => $this->sampul ? Storage::disk('public')->url($this->sampul) : null,
            'status' => $this->status,
            'deskripsi' => $this->whenHas('deskripsi'),
            'pic' => $this->pic->map(fn ($anggota) => [
                'anggota_id' => $anggota->id,
                'nama' => $anggota->user?->nama,
            ])->values(),
            'jumlah_foto' => $this->whenHas('jumlah_foto'),
            'jumlah_video' => $this->whenHas('jumlah_video'),
            'diperbarui_pada' => $this->updated_at?->toIso8601String(),
        ];
    }
}
