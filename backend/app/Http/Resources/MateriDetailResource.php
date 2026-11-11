<?php

namespace App\Http\Resources;

use App\Models\Materi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Detail materi untuk halaman baca anggota: isi revisi terakhir + riwayat versi
 * + versi publik + penarikan yang menunggu (KP-16.4, KF-20).
 */
class MateriDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Draf 2.3: judul, ringkasan, sampul, dan kategori diambil dari revisi
        // terakhir (KP-16.2), sama seperti isinya.
        $versi = $this->relationLoaded('revisiTerakhir') ? $this->revisiTerakhir : null;
        $sampul = $versi ? $versi->sampul : $this->sampul;

        return [
            'id' => $this->id,
            'judul' => $versi?->judul ?? $this->judul,
            'slug' => $this->slug,
            'ringkasan' => $versi ? $versi->ringkasan : $this->ringkasan,
            'sampul' => $sampul ? Storage::disk('public')->url($sampul) : null,
            'status' => $this->status,
            'diterbitkan_pada' => $this->diterbitkan_pada?->toIso8601String(),
            'penulis' => $this->whenLoaded('penulis', fn () => [
                'id' => $this->penulis->id,
                'nama' => $this->penulis->nama,
            ]),
            // KP-16.4: halaman baca anggota selalu menampilkan revisi terakhir.
            'revisi' => $this->whenLoaded('revisiTerakhir', fn () => $this->revisiTerakhir
                ? new RevisiResource($this->revisiTerakhir)
                : null),
            // Label "Versi publik: vN" hanya selama materi terbit (KP-16.4).
            'versi_publik' => $this->whenLoaded('versiTerbit', fn () => $this->status === Materi::STATUS_TERBIT && $this->versiTerbit
                ? ['id' => $this->versiTerbit->id, 'nomor_versi' => $this->versiTerbit->nomor_versi]
                : null),
            // Versi publik terakhir, tetap ada setelah ditarik (KP-10.7).
            'versi_publik_terakhir' => $this->whenLoaded('versiTerbit', fn () => $this->versiTerbit
                ? ['id' => $this->versiTerbit->id, 'nomor_versi' => $this->versiTerbit->nomor_versi]
                : null),
            // Pengajuan penarikan yang menunggu; alasan hanya untuk penulis & admin (KP-20.7).
            'penarikan' => $this->whenLoaded('penarikanMenunggu', fn () => $this->penarikanMenunggu
                ? new PenarikanResource($this->penarikanMenunggu->setRelation('materi', $this->resource))
                : null),
            // Kategori untuk chip di halaman baca: milik revisi terakhir (KP-19.3, KP-19.4).
            'kategori' => $versi && $versi->relationLoaded('kategori')
                ? KategoriResource::collection($versi->kategori)
                : KategoriResource::collection($this->whenLoaded('kategori')),
            'riwayat' => $this->whenLoaded('revisi', fn () => RevisiRingkasResource::collection($this->revisi)),
            'jumlah_komentar' => $this->whenCounted('komentar'),
        ];
    }
}
