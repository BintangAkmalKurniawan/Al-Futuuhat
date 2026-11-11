<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Program untuk daftar publik `/program` (KF-21, KP-21.5).
 * Endpoint publik: PIC hanya dikirim namanya (KNF-02).
 */
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
            // Diisi lewat withCount() di controller (jumlah per tab Dokumentasi).
            'jumlah_foto' => $this->whenHas('jumlah_foto'),
            'jumlah_video' => $this->whenHas('jumlah_video'),
        ];
    }
}
