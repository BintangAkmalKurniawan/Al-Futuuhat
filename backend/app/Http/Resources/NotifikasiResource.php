<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Notifikasi dalam aplikasi (tabel `notifications` bawaan Laravel).
 */
class NotifikasiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'data' => $this->data,
            'dibaca' => $this->read_at !== null,
            'dibaca_pada' => $this->read_at?->toIso8601String(),
            'dibuat_pada' => $this->created_at?->toIso8601String(),
        ];
    }
}