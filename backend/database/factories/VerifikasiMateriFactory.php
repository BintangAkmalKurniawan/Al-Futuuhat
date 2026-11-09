<?php

namespace Database\Factories;

use App\Models\RevisiMateri;
use App\Models\User;
use App\Models\VerifikasiMateri;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VerifikasiMateri>
 */
class VerifikasiMateriFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'revisi_id' => RevisiMateri::factory(),
            'admin_id' => User::factory()->admin(),
            'keputusan' => VerifikasiMateri::KEPUTUSAN_DISETUJUI,
            'catatan' => null,
        ];
    }

    public function ditolak(): static
    {
        return $this->state(fn (array $attributes) => [
            'keputusan' => VerifikasiMateri::KEPUTUSAN_DITOLAK,
            'catatan' => fake()->sentence(),
        ]);
    }
}
