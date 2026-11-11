<?php

namespace Database\Factories;

use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Program>
 */
class ProgramFactory extends Factory
{
    protected $model = Program::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nama = 'Program '.fake()->unique()->words(2, true);

        return [
            'nama' => $nama,
            'slug' => Str::slug($nama),
            'ringkasan' => fake()->sentence(12),
            'jadwal' => 'Setiap Jumat malam, ba\'da Isya',
            'sampul' => null,
            'deskripsi' => '<p>'.fake()->paragraph().'</p>',
            'status' => Program::STATUS_AKTIF,
        ];
    }

    public function selesai(): static
    {
        return $this->state(fn () => ['status' => Program::STATUS_SELESAI]);
    }
}
