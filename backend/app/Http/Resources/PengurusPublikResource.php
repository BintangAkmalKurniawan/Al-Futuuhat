<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Satu baris pengurus pada struktur publik.
 * Sesuai KP-01.5 hanya nama, jabatan, dan divisi — tanpa data pribadi.
 */
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