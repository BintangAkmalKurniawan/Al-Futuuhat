<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Ringkasan satu versi
class RevisiRingkasResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nomor_versi' => $this->nomor_versi,
            'status' => $this->status,
            'catatan_revisi' => $this->catatan_revisi,
            'dibuat_pada' => $this->created_at?->toIso8601String(),
            'verifikasi' => $this->whenLoaded('verifikasi', fn () => VerifikasiResource::collection($this->verifikasi)),
        ];
    }
}