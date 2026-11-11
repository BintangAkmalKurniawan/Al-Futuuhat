<?php

namespace Database\Factories;

use App\Models\DokumentasiProgram;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DokumentasiProgram>
 */
class DokumentasiProgramFactory extends Factory
{
    protected $model = DokumentasiProgram::class;

    /**
     * Bawaan: tautan YouTube, agar tidak membutuhkan berkas sungguhan.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'jenis' => DokumentasiProgram::JENIS_YOUTUBE,
            'path' => null,
            'youtube_id' => 'dQw4w9WgXcQ',
            'keterangan' => fake()->sentence(6),
        ];
    }

    public function foto(): static
    {
        return $this->state(fn () => [
            'jenis' => DokumentasiProgram::JENIS_FOTO,
            'path' => 'program/dokumentasi/contoh.jpg',
            'youtube_id' => null,
        ]);
    }
}
