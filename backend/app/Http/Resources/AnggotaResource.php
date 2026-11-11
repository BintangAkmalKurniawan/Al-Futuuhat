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
            // id baris anggota dipakai formulir susunan pengurus (F4 frontend).
            'id' => $this->id,
            'no_hp' => $this->no_hp,
            'alamat' => $this->alamat,
            'alasan_bergabung' => $this->alasan_bergabung,
            // Foto disimpan di disk `public`. URL-nya harus lengkap (http://host:8000/storage/...)
            // karena frontend berjalan di asal (port) yang berbeda; Storage::url() tanpa
            // disk memakai disk bawaan `local` dan hanya menghasilkan "/storage/...".
            'foto' => $this->foto ? Storage::disk('public')->url($this->foto) : null,
            'tanggal_bergabung' => $this->tanggal_bergabung?->toDateString(),
            'catatan_verifikasi' => $this->catatan_verifikasi,
            'diverifikasi_pada' => $this->diverifikasi_pada?->toIso8601String(),
        ];
    }
}