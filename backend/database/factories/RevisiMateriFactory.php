<?php

namespace Database\Factories;

use App\Models\Materi;
use App\Models\RevisiMateri;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RevisiMateri>
 */
class RevisiMateriFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $paragraf = fake()->paragraph();

        return [
            'materi_id' => Materi::factory(),
            'nomor_versi' => 1,
            'konten' => ['ops' => [['insert' => $paragraf."\n"]]], // bentuk Delta Quill sederhana
            'konten_html' => '<p>'.$paragraf.'</p>',
            'catatan_revisi' => null,
            'status' => RevisiMateri::STATUS_DRAF,
        ];
    }

    public function diajukan(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RevisiMateri::STATUS_DIAJUKAN,
        ]);
    }

    public function disetujui(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RevisiMateri::STATUS_DISETUJUI,
        ]);
    }

    public function ditolak(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RevisiMateri::STATUS_DITOLAK,
        ]);
    }
}
