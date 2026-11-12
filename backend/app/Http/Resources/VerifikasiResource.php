<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Keputusan admin atas satu revisi
class VerifikasiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'keputusan' => $this->keputusan,
            'catatan' => $this->catatan,
            'admin' => $this->whenLoaded('admin', fn () => [
                'id' => $this->admin->id,
                'nama' => $this->admin->nama,
            ]),
            'dibuat_pada' => $this->created_at?->toIso8601String(),
        ];
    }
}