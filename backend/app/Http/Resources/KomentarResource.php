<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Komentar forum. Dipakai juga sebagai data event real-time (KF-15).
 */
class KomentarResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'materi_id' => $this->materi_id,
            'parent_id' => $this->parent_id,
            'isi' => $this->isi,
            'gambar' => $this->gambar ? Storage::url($this->gambar) : null,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'nama' => $this->user->nama,
            ]),
            'balasan' => $this->whenLoaded('balasan', fn () => self::collection($this->balasan)),
            'dibuat_pada' => $this->created_at?->toIso8601String(),
        ];
    }
}