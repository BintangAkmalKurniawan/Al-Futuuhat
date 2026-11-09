<?php

namespace App\Models;

use Database\Factories\AnggotaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Data keanggotaan (1:1 dengan users) — PRD §10.
 */
class Anggota extends Model
{
    /** @use HasFactory<AnggotaFactory> */
    use HasFactory;

    protected $table = 'anggota';

    protected $fillable = [
        'user_id',
        'no_hp',
        'alamat',
        'alasan_bergabung',
        'foto',
        'tanggal_bergabung',
        'catatan_verifikasi',
        'diverifikasi_pada',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_bergabung' => 'date',
            'diverifikasi_pada' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Riwayat jabatan anggota ini di setiap periode. */
    public function kepengurusan(): HasMany
    {
        return $this->hasMany(Kepengurusan::class);
    }
}
