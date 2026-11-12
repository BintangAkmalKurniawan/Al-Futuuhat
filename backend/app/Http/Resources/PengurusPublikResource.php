<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Satu baris pengurus pada struktur publik
class PengurusPublikResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'nama' => $this->anggota->user->nama,
            'jabatan' => $this->jabatan->nama_jabatan,
            'divisi' => $this->divisi?->nama_divisi,
        ];
    }
}