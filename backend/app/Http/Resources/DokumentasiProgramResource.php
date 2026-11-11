<?php

namespace App\Http\Resources;

use App\Models\DokumentasiProgram;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Satu butir dokumentasi program (KP-21.3, KP-21.6).
 */
class DokumentasiProgramResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $youtube = $this->jenis === DokumentasiProgram::JENIS_YOUTUBE;

        return [
            'id' => $this->id,
            'jenis' => $this->jenis,
            // Foto/video: URL berkas di server. YouTube: tautan menonton di YouTube.
            'url' => $youtube
                ? "https://www.youtube.com/watch?v={$this->youtube_id}"
                : Storage::disk('public')->url($this->path),
            // Alamat pemutar sematan; youtube-nocookie tidak memasang cookie pelacak
            // sebelum video diputar.
            'embed' => $youtube ? "https://www.youtube-nocookie.com/embed/{$this->youtube_id}" : null,
            // Gambar kecil untuk grid: foto memakai dirinya sendiri, YouTube memakai
            // gambar bawaan YouTube, video MP4 tidak punya (frontend memakai ikon).
            'thumbnail' => match ($this->jenis) {
                DokumentasiProgram::JENIS_FOTO => Storage::disk('public')->url($this->path),
                DokumentasiProgram::JENIS_YOUTUBE => "https://i.ytimg.com/vi/{$this->youtube_id}/hqdefault.jpg",
                default => null,
            },
            'keterangan' => $this->keterangan,
            'dibuat_pada' => $this->created_at?->toIso8601String(),
        ];
    }
}
