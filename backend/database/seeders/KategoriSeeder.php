<?php

namespace Database\Seeders;

use App\Models\Kategori;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Kategori materi awal (KF-19) — PRD §10. Kategori lain ditambah admin
 * lewat halaman `/admin/kategori`.
 */
class KategoriSeeder extends Seeder
{
    /**
     * Sembilan kategori contoh: cukup banyak agar tombol "Selengkapnya" dan
     * kotak cari kategori pada penyaring ikut terlihat saat demo.
     */
    private const CONTOH = [
        'Fiqih',
        'Akhlak',
        'Politik Islam',
        'Sejarah Islam',
        'Tasawuf',
        'Tafsir',
        'Hadis',
        'Ushul Fiqih',
        'Sirah Nabawiyah',
    ];

    public function run(): void
    {
        foreach (self::CONTOH as $nama) {
            // firstOrCreate supaya seeder aman dijalankan berulang kali.
            Kategori::firstOrCreate(
                ['nama' => $nama],
                ['slug' => Str::slug($nama)]
            );
        }
    }
}
