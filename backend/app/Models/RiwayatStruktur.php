<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu catatan perubahan struktur organisasi (KP-07.7) — PRD §10.
 */
class RiwayatStruktur extends Model
{
    public const AKSI_TAMBAH = 'tambah';

    public const AKSI_UBAH = 'ubah';

    public const AKSI_HAPUS = 'hapus';

    public const AKSI_PERIODE_BARU = 'periode_baru';

    protected $table = 'riwayat_struktur';

    protected $fillable = [
        'periode_id',
        'admin_id',
        'aksi',
        'keterangan',
        'data_sebelum',
        'data_sesudah',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data_sebelum' => 'array',
            'data_sesudah' => 'array',
        ];
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(PeriodeKepengurusan::class, 'periode_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
