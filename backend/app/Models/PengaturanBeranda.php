<?php

namespace App\Models;

use Database\Factories\PengaturanBerandaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Konten beranda
class PengaturanBeranda extends Model
{
    /** @use HasFactory<PengaturanBerandaFactory> */
    use HasFactory;

    protected $table = 'pengaturan_beranda';

    protected $fillable = [
        'cerita_judul',
        'cerita_isi',
        'visi',
        'misi',
        'kontak_alamat',
        'kontak_whatsapp',
        'kontak_email',
        'kontak_instagram',
    ];

    // Ambil pengaturan beranda
    public static function pengaturan(): ?self
    {
        return static::query()->oldest('id')->first();
    }

    // Ambil atau buat pengaturan beranda
    public static function pengaturanAtauBuat(): self
    {
        return static::pengaturan() ?? static::query()->create([]);
    }
}
