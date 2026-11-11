<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Kategori materi, mis. Fiqih, Akhlak (KF-19) — PRD §10.
 * Daftar kategori diatur admin di halaman `/admin/kategori`.
 */
class Kategori extends Model
{
    protected $table = 'kategori';

    protected $fillable = ['nama', 'slug'];

    /** Materi yang memakai kategori ini pada versi publiknya (KP-19.1, KP-19.3). */
    public function materi(): BelongsToMany
    {
        return $this->belongsToMany(Materi::class, 'kategori_materi')->withTimestamps();
    }

    /** Versi materi yang memakai kategori ini (Draf 2.3, KP-19.3). */
    public function revisi(): BelongsToMany
    {
        return $this->belongsToMany(RevisiMateri::class, 'kategori_revisi', 'kategori_id', 'revisi_id')->withTimestamps();
    }
}
