<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Konten beranda
class PengaturanBerandaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'cerita_judul' => $this->cerita_judul,
            'cerita_isi' => $this->cerita_isi,
            'visi' => $this->visi,
            'misi' => $this->misi,
            'kontak' => [
                'alamat' => $this->kontak_alamat,
                'whatsapp' => $this->kontak_whatsapp,
                'email' => $this->kontak_email,
                'instagram' => $this->kontak_instagram,
            ],
            'diperbarui_pada' => $this->updated_at?->toIso8601String(),
        ];
    }
}
