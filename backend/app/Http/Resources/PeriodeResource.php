<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Periode kepengurusan. Aman dipakai di endpoint publik.
 */
class PeriodeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama_periode' => $this->nama_periode,
            'tahun_mulai' => $this->tahun_mulai,
            'tahun_selesai' => $this->tahun_selesai,
            'is_aktif' => (bool) $this->is_aktif,
            // Draf 2.5: true bila periode aktif dari tahun lalu, sehingga perubahan
            // susunan berikutnya otomatis memulai periode tahun berjalan (KP-07.1).
            'akan_berganti' => $this->resource->akanBerganti(),
        ];
    }
}