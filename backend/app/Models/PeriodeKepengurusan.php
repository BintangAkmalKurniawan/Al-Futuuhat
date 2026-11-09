<?php

namespace App\Models;

use Database\Factories\PeriodeKepengurusanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Periode kepengurusan, mis. "2026/2027" — PRD §10.
 */
class PeriodeKepengurusan extends Model
{
    /** @use HasFactory<PeriodeKepengurusanFactory> */
    use HasFactory;

    protected $table = 'periode_kepengurusan';

    protected $fillable = ['nama_periode', 'tahun_mulai', 'tahun_selesai', 'is_aktif'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_aktif' => 'boolean',
        ];
    }

    public function kepengurusan(): HasMany
    {
        return $this->hasMany(Kepengurusan::class, 'periode_id');
    }
}
