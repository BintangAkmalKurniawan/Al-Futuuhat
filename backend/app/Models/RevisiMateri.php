<?php

namespace App\Models;

use Database\Factories\RevisiMateriFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Satu versi isi materi
class RevisiMateri extends Model
{
    /** @use HasFactory<RevisiMateriFactory> */
    use HasFactory;

    public const STATUS_DRAF = 'draf';

    public const STATUS_DIAJUKAN = 'diajukan';

    public const STATUS_DISETUJUI = 'disetujui';

    public const STATUS_DITOLAK = 'ditolak';

    protected $table = 'revisi_materi';

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
            'konten' => 'array',
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

    // Kategori
    public function kategori(): BelongsToMany
    {
        return $this->belongsToMany(Kategori::class, 'kategori_revisi', 'revisi_id', 'kategori_id')->withTimestamps();
    }
}
