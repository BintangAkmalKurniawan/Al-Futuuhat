<?php

namespace App\Models;

use Database\Factories\KepengurusanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Susunan pengurus
class Kepengurusan extends Model
{
    /** @use HasFactory<KepengurusanFactory> */
    use HasFactory;

    protected $table = 'kepengurusan';

    protected $fillable = ['anggota_id', 'periode_id', 'divisi_id', 'jabatan_id'];

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class);
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(PeriodeKepengurusan::class, 'periode_id');
    }

    // Divisi
    public function divisi(): BelongsTo
    {
        return $this->belongsTo(Divisi::class);
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class);
    }

    /**
     * Ringkasan posisi
     *
     * @return array{nama: string|null, jabatan: string|null, divisi: string|null}
     */
    public function ringkasan(): array
    {
        $this->loadMissing(['anggota.user', 'jabatan', 'divisi']);

        return [
            'nama' => $this->anggota?->user?->nama,
            'jabatan' => $this->jabatan?->nama_jabatan,
            'divisi' => $this->divisi?->nama_divisi,
        ];
    }

    /**
     * Teks posisi
     *
     * @param  array{jabatan?: string|null, divisi?: string|null}  $ringkasan
     */
    public static function teksPosisi(array $ringkasan): string
    {
        $jabatan = $ringkasan['jabatan'] ?? 'Pengurus';

        return ! empty($ringkasan['divisi'])
            ? "{$jabatan} Divisi {$ringkasan['divisi']}"
            : "{$jabatan} (pengurus inti)";
    }
}
