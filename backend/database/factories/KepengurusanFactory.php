<?php

namespace Database\Factories;

use App\Models\Anggota;
use App\Models\Divisi;
use App\Models\Jabatan;
use App\Models\Kepengurusan;
use App\Models\PeriodeKepengurusan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kepengurusan>
 */
class KepengurusanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'anggota_id' => Anggota::factory(),
            'periode_id' => PeriodeKepengurusan::factory(),
            'divisi_id' => Divisi::factory(),
            'jabatan_id' => Jabatan::factory(),
        ];
    }

    // Pengurus inti
    public function inti(): static
    {
        return $this->state(fn (array $attributes) => [
            'divisi_id' => null,
        ]);
    }
}
