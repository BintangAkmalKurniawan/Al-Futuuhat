<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Urutan penting: akun admin dan struktur dasar dulu,
     * data contoh (khusus lokal) terakhir karena bergantung pada keduanya.
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            StrukturAwalSeeder::class,
            KategoriSeeder::class,
            ContohDataSeeder::class,
        ]);
    }
}
