<?php

namespace App\Http\Resources;

use App\Models\Materi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Materi untuk daftar (arsip / materi saya). Tanpa isi konten agar ringan.
 */
class MateriRingkasResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Draf 2.3 (KP-16.2): area anggota menampilkan judul, ringkasan, sampul,
        // dan kategori revisi terakhir. Kolom di tabel `materi` hanya dipakai
        // bila relasi revisi terakhir tidak dimuat.
        $versi = $this->relationLoaded('revisiTerakhir') ? $this->revisiTerakhir : null;
        $sampul = $versi ? $versi->sampul : $this->sampul;

        return [
            'id' => $this->id,
            'judul' => $versi?->judul ?? $this->judul,
            'slug' => $this->slug,
            'ringkasan' => $versi ? $versi->ringkasan : $this->ringkasan,
            // Kolom `sampul` menyimpan path, jadi URL-nya dibentuk di Resource (KF-18).
            'sampul' => $sampul ? Storage::disk('public')->url($sampul) : null,
            'status' => $this->status,
            'penulis' => $this->whenLoaded('penulis', fn () => [
                'id' => $this->penulis->id,
                'nama' => $this->penulis->nama,
            ]),
            'diterbitkan_pada' => $this->diterbitkan_pada?->toIso8601String(),
            // KP-16.2: nomor versi yang ditampilkan adalah revisi terakhir;
            // untuk materi terbit tanpa revisi baru, nilainya sama dengan versi publik.
            'nomor_versi' => $this->when(
                $this->relationLoaded('revisiTerakhir') || $this->relationLoaded('versiTerbit'),
                fn () => ($this->relationLoaded('revisiTerakhir')
                    ? $this->revisiTerakhir?->nomor_versi
                    : null) ?? $this->versiTerbit?->nomor_versi
            ),
            'revisi_terakhir' => $this->whenLoaded('revisiTerakhir', fn () => $this->revisiTerakhir ? [
                'id' => $this->revisiTerakhir->id,
                'nomor_versi' => $this->revisiTerakhir->nomor_versi,
                'status' => $this->revisiTerakhir->status,
            ] : null),
            // "Versi publik: vN" hanya selama materi terbit (KP-16.4).
            'versi_publik' => $this->whenLoaded('versiTerbit', fn () => $this->status === Materi::STATUS_TERBIT && $this->versiTerbit
                ? ['id' => $this->versiTerbit->id, 'nomor_versi' => $this->versiTerbit->nomor_versi]
                : null),
            // Versi publik terakhir, tetap ada setelah ditarik; untuk perbandingan (KP-10.7).
            'versi_publik_terakhir' => $this->whenLoaded('versiTerbit', fn () => $this->versiTerbit
                ? ['id' => $this->versiTerbit->id, 'nomor_versi' => $this->versiTerbit->nomor_versi]
                : null),
            // Pengajuan penarikan yang menunggu (KF-20); alasannya hanya untuk penulis & admin.
            'penarikan' => $this->whenLoaded('penarikanMenunggu', fn () => $this->penarikanMenunggu
                ? new PenarikanResource($this->penarikanMenunggu->setRelation('materi', $this->resource))
                : null),
            'jumlah_komentar' => $this->whenCounted('komentar'),
            // Kategori untuk chip di baris daftar (KF-19, KP-19.4): milik revisi terakhir (KP-19.3).
            'kategori' => $versi && $versi->relationLoaded('kategori')
                ? KategoriResource::collection($versi->kategori)
                : KategoriResource::collection($this->whenLoaded('kategori')),
            // Perkiraan waktu baca untuk baris daftar (DESIGN.md §9).
            'menit_baca' => $this->when(
                $this->relationLoaded('revisiTerakhir') || $this->relationLoaded('versiTerbit'),
                fn () => $this->menitBaca()
            ),
            'dibuat_pada' => $this->created_at?->toIso8601String(),
            'diperbarui_pada' => $this->updated_at?->toIso8601String(),
        ];
    }
}