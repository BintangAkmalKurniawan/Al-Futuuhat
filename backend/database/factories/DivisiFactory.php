<?php

namespace Database\Factories;

use App\Models\Divisi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Divisi>
 */
class DivisiFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama_divisi' => 'Divisi '.fake()->unique()->word(),
            'deskripsi' => fake()->sentence(),
        ];
    }
}
