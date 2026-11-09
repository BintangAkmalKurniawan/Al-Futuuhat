<?php

namespace Database\Seeders;

use App\Models\Divisi;
use App\Models\Jabatan;
use Illuminate\Database\Seeder;

/**
 * Data awal struktur organisasi — PRD §10:
 * 6 divisi, jabatan Ketua (urutan 1) dan Koordinator (urutan 2).
 * Jabatan lain ditambahkan admin lewat menu Jabatan (PRD §16 no. 7).
 *
 * Memakai firstOrCreate supaya seeder boleh dijalankan ulang tanpa
 * menimpa perubahan yang sudah dilakukan admin.
 */
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
