<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Baris susunan pengurus
class KepengurusanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'anggota_id' => $this->anggota_id,
            'periode_id' => $this->periode_id,
            'divisi_id' => $this->divisi_id,
            'jabatan_id' => $this->jabatan_id,
            'nama' => $this->whenLoaded('anggota', fn () => $this->anggota->user?->nama),
            'divisi' => $this->whenLoaded('divisi', fn () => $this->divisi ? new DivisiResource($this->divisi) : null),
            'jabatan' => $this->whenLoaded('jabatan', fn () => $this->jabatan ? new JabatanResource($this->jabatan) : null),
        ];
    }
}