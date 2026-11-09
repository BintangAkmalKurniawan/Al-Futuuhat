<?php

namespace App\Models;

use Database\Factories\MateriFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Materi (induk tulisan). Isi setiap versi disimpan di `revisi_materi` — PRD §10.
 */
class Materi extends Model
{
    /** @use HasFactory<MateriFactory> */
    use HasFactory;

    // Status materi (PRD §8.2)
    public const STATUS_DRAF = 'draf';

    public const STATUS_DIAJUKAN = 'diajukan';

    public const STATUS_TERBIT = 'terbit';

    public const STATUS_DITOLAK = 'ditolak';

    protected $table = 'materi';

    protected $fillable = ['penulis_id', 'judul', 'slug', 'ringkasan', 'status', 'diterbitkan_pada'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'diterbitkan_pada' => 'datetime',
        ];
    }

    public function penulis(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penulis_id');
    }

    /** Semua versi, terbaru lebih dulu (halaman riwayat versi, KF-13). */
    public function revisi(): HasMany
    {
        return $this->hasMany(RevisiMateri::class)->orderByDesc('nomor_versi');
    }

    /** Versi dengan nomor terbesar, apa pun statusnya (dipakai saat mengubah/mengajukan). */
    public function revisiTerakhir(): HasOne
    {
        return $this->hasOne(RevisiMateri::class)->latestOfMany('nomor_versi');
    }

    /** Versi yang sedang tampil ke pembaca (PRD §8.2). */
    public function versiTerbit(): HasOne
    {
        // Batas "disetujui" harus ikut ke subquery MAX(nomor_versi); kalau hanya
        // dipasang di query luar, revisi draf/diajukan yang lebih baru membuat
        // hasilnya kosong walau versi terbit lama masih ada.
        return $this->hasOne(RevisiMateri::class)->ofMany(
            ['nomor_versi' => 'max'],
            fn (Builder $query) => $query->where('status', RevisiMateri::STATUS_DISETUJUI),
        );
    }

    public function komentar(): HasMany
    {
        return $this->hasMany(Komentar::class);
    }
}
