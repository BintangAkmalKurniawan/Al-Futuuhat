<?php

namespace Database\Factories;

use App\Models\PengaturanBeranda;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PengaturanBeranda>
 */
class PengaturanBerandaFactory extends Factory
{
    protected $model = PengaturanBeranda::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cerita_judul' => 'Cerita Al-Futuuhat',
            'cerita_isi' => '<p>Berawal dari kajian kecil di serambi masjid.</p>',
        ];
    }
}
