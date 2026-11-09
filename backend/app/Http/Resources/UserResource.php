<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Data akun. Dipakai untuk /me, login, dan daftar pendaftar/anggota di admin.
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'email' => $this->email,
            'role' => $this->role,
            'status' => $this->status,
            'terdaftar_pada' => $this->created_at?->toIso8601String(),
            // Data pribadi hanya ikut terkirim bila memang di-eager load oleh controller
            'anggota' => $this->whenLoaded('anggota', fn () => new AnggotaResource($this->anggota)),
        ];
    }
}