<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Data akun
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
            'email_terkonfirmasi' => $this->email_verified_at !== null,
            'role' => $this->role,
            'status' => $this->status,
            'terdaftar_pada' => $this->created_at?->toIso8601String(),
            'anggota' => $this->whenLoaded('anggota', fn () => new AnggotaResource($this->anggota)),
        ];
    }
}