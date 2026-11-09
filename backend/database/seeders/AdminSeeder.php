<?php

namespace Database\Seeders;

use App\Models\Anggota;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Akun admin (ketua) yang dibuat dari variabel .env — PRD §10.
 * Admin juga anggota, jadi tetap punya satu baris data keanggotaan (PRD §5).
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // env() aman dipakai di seeder karena seeder hanya dijalankan dari terminal
        $user = User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@alfutuuhat.test')],
            [
                'nama' => env('ADMIN_NAMA', 'Administrator Al-Futuuhat'),
                'password' => env('ADMIN_PASSWORD', 'admin12345'), // otomatis di-hash oleh cast 'hashed'
                'role' => User::ROLE_ADMIN,
                'status' => User::STATUS_AKTIF,
            ]
        );

        Anggota::updateOrCreate(
            ['user_id' => $user->id],
            [
                'no_hp' => '081200000000',
                'alamat' => 'Sekretariat Al-Futuuhat',
                'alasan_bergabung' => 'Pengurus inti Al-Futuuhat.',
                'tanggal_bergabung' => now()->toDateString(),
                'diverifikasi_pada' => now(),
            ]
        );
    }
}
