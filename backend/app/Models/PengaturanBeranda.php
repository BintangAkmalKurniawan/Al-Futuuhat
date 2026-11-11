<?php

namespace App\Models;

use Database\Factories\PengaturanBerandaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Konten beranda: cerita Al-Futuuhat (KF-18) dan kontak organisasi (KF-22).
 * Tabelnya hanya berisi satu baris, jadi selalu diambil lewat `pengaturan()`.
 */
class PengaturanBeranda extends Model
{
    /** @use HasFactory<PengaturanBerandaFactory> */
    use HasFactory;

    protected $table = 'pengaturan_beranda';

    protected $fillable = [
        'cerita_judul',
        'cerita_isi',
        // Halaman Tentang kami (KF-26).
        'visi',
        'misi',
        // Kontak organisasi di kaki halaman publik (KF-22).
        'kontak_alamat',
        'kontak_whatsapp',
        'kontak_email',
        'kontak_instagram',
    ];

    /**
     * Baris pengaturan beranda, atau null bila admin belum pernah mengaturnya.
     * Tidak membuat baris baru, supaya endpoint baca tidak mengubah data.
     */
    public static function pengaturan(): ?self
    {
        return static::query()->oldest('id')->first();
    }

    /** Baris pengaturan untuk diperbarui; dibuatkan bila belum ada. */
    public static function pengaturanAtauBuat(): self
    {
        return static::pengaturan() ?? static::query()->create([]);
    }
}
