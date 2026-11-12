<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

// Program untuk daftar publik `/program`
class ProgramResource extends JsonResource
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
            'pic' => $this->pic->map(fn ($anggota) => ['nama' => $anggota->user?->nama])->values(),
            'jumlah_foto' => $this->whenHas('jumlah_foto'),
            'jumlah_video' => $this->whenHas('jumlah_video'),
        ];
    }
}
