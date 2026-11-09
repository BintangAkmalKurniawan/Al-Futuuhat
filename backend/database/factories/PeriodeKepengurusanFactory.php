<?php

namespace Database\Factories;

use App\Models\PeriodeKepengurusan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PeriodeKepengurusan>
 */
class PeriodeKepengurusanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tahun = fake()->numberBetween(2018, 2026);

        return [
            'nama_periode' => $tahun.'/'.($tahun + 1),
            'tahun_mulai' => $tahun,
            'tahun_selesai' => $tahun + 1,
            'is_aktif' => false,
        ];
    }

    public function aktif(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_aktif' => true,
        ]);
    }
}
