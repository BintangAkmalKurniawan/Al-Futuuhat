<?php

namespace Database\Seeders;

use App\Models\Anggota;
use App\Models\User;
use Illuminate\Database\Seeder;

// Akun admin
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@alfutuuhat.test')],
            [
                'nama' => env('ADMIN_NAMA', 'Administrator Al-Futuuhat'),
                'password' => env('ADMIN_PASSWORD', 'admin12345'),
                'role' => User::ROLE_ADMIN,
                'status' => User::STATUS_AKTIF,
            ]
        );

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

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
