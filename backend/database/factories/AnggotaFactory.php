<?php

namespace Database\Factories;

use App\Models\Anggota;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Anggota>
 */
class AnggotaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'no_hp' => '08'.fake()->numerify('##########'),
            'alamat' => fake()->address(),
            'alasan_bergabung' => fake()->paragraph(),
            'foto' => null,
            'tanggal_bergabung' => now()->toDateString(),
            'catatan_verifikasi' => null,
            'diverifikasi_pada' => now(),
        ];
    }

    // Pendaftar menunggu
    public function menunggu(): static
    {
        return $this->state(fn (array $attributes) => [
            'tanggal_bergabung' => null,
            'diverifikasi_pada' => null,
        ]);
    }
}
