<?php

namespace App\Models;

use Database\Factories\RevisiMateriFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satu versi isi materi. Versi `disetujui` dan `ditolak` bersifat tetap
 * (tidak pernah diubah) sebagai riwayat — PRD §8.2, KP-12.5.
 */
class RevisiMateri extends Model
{
    /** @use HasFactory<RevisiMateriFactory> */
    use HasFactory;

    public const STATUS_DRAF = 'draf';

    public const STATUS_DIAJUKAN = 'diajukan';

    public const STATUS_DISETUJUI = 'disetujui';

    public const STATUS_DITOLAK = 'ditolak';

    protected $table = 'revisi_materi';

    // Draf 2.3: judul, ringkasan, dan sampul ikut disimpan per versi (KP-12.4).
    protected $fillable = [
        'materi_id', 'nomor_versi', 'judul', 'ringkasan', 'sampul',
        'konten', 'konten_html', 'catatan_revisi', 'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'konten' => 'array', // Delta Quill (JSONB) — KP-08.4
        ];
    }

    public function materi(): BelongsTo
    {
        return $this->belongsTo(Materi::class);
    }

    public function verifikasi(): HasMany
    {
        return $this->hasMany(VerifikasiMateri::class, 'revisi_id');
    }

    /** Kategori versi ini, minimal satu (KP-19.3). */
    public function kategori(): BelongsToMany
    {
        return $this->belongsToMany(Kategori::class, 'kategori_revisi', 'revisi_id', 'kategori_id')->withTimestamps();
    }
}
