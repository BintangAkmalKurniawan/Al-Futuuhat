<?php

namespace Database\Seeders;

use App\Models\Kategori;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

// Kategori materi awal
class KategoriSeeder extends Seeder
{
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
            Kategori::firstOrCreate(
                ['nama' => $nama],
                ['slug' => Str::slug($nama)]
            );
        }
    }
}
