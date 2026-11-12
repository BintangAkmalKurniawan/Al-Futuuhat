<?php

namespace Database\Seeders;

use App\Models\Divisi;
use App\Models\Jabatan;
use Illuminate\Database\Seeder;

// Struktur awal
class StrukturAwalSeeder extends Seeder
{
    public function run(): void
    {
        $divisi = ['Advokasi', 'Bidang Sosial', 'Finance', 'Edukasi', 'Dewan Fatwa', 'Media'];

        foreach ($divisi as $nama) {
            Divisi::firstOrCreate(['nama_divisi' => $nama]);
        }

        Jabatan::firstOrCreate(['nama_jabatan' => 'Ketua'], ['urutan' => 1]);
        Jabatan::firstOrCreate(['nama_jabatan' => 'Koordinator'], ['urutan' => 2]);
    }
}
