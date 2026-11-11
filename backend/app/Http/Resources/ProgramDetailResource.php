<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * Halaman baca satu program (KP-21.5): data daftar + penjelasan rinci.
 */
class ProgramDetailResource extends ProgramResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            'deskripsi' => $this->deskripsi,
            'diperbarui_pada' => $this->updated_at?->toIso8601String(),
        ];
    }
}
