<?php

namespace Database\Factories;

use App\Models\Komentar;
use App\Models\Materi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Komentar>
 */
class KomentarFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'materi_id' => Materi::factory(),
            'user_id' => User::factory(),
            'parent_id' => null, // null = komentar induk
            'isi' => fake()->sentence(),
            'gambar' => null,
        ];
    }

    /** Balasan untuk sebuah komentar induk (satu tingkat, KP-14.3). */
    public function balasan(Komentar $induk): static
    {
        return $this->state(fn (array $attributes) => [
            'materi_id' => $induk->materi_id,
            'parent_id' => $induk->id,
        ]);
    }
}
