<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Satu catatan log perubahan struktur
class RiwayatStrukturResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'aksi' => $this->aksi,
            'keterangan' => $this->keterangan,
            'admin' => $this->admin ? ['nama' => $this->admin->nama] : null,
            'data_sebelum' => $this->data_sebelum,
            'data_sesudah' => $this->data_sesudah,
            'dibuat_pada' => $this->created_at?->toIso8601String(),
        ];
    }
}
