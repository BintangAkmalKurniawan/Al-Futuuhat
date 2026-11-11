<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Konten beranda (KF-18). Isinya sama untuk pengunjung dan admin — tidak ada
 * data internal — jadi satu Resource dipakai kedua endpoint.
 */
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
            // Halaman Tentang kami (KF-26): visi teks biasa, misi satu poin per baris.
            'visi' => $this->visi,
            'misi' => $this->misi,
            // Kontak organisasi (KF-22) — memang untuk umum, bukan data pribadi anggota.
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
