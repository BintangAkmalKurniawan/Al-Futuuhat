<?php

namespace Database\Factories;

use App\Models\Materi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Materi>
 */
class MateriFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $judul = fake()->unique()->sentence(4);

        return [
            'penulis_id' => User::factory(),
            'judul' => $judul,
            // Akhiran acak supaya slug tetap unik meski judulnya mirip
            'slug' => Str::slug($judul).'-'.Str::lower(Str::random(5)),
            'ringkasan' => fake()->paragraph(),
            'status' => Materi::STATUS_DRAF,
            'diterbitkan_pada' => null,
        ];
    }

    public function terbit(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Materi::STATUS_TERBIT,
            'diterbitkan_pada' => now(),
        ]);
    }

    public function diajukan(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Materi::STATUS_DIAJUKAN,
        ]);
    }
}
