<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Data keanggotaan (privat). Hanya dikirim ke pemilik akun dan admin (KNF-02).
 */
class AnggotaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'no_hp' => $this->no_hp,
            'alamat' => $this->alamat,
            'alasan_bergabung' => $this->alasan_bergabung,
            'foto' => $this->foto ? Storage::url($this->foto) : null,
            'tanggal_bergabung' => $this->tanggal_bergabung?->toDateString(),
            'catatan_verifikasi' => $this->catatan_verifikasi,
            'diverifikasi_pada' => $this->diverifikasi_pada?->toIso8601String(),
        ];
    }
}