<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

// Kategori materi
class Kategori extends Model
{
    protected $table = 'kategori';

    protected $fillable = ['nama', 'slug'];

    // Materi pada versi publik
    public function materi(): BelongsToMany
    {
        return $this->belongsToMany(Materi::class, 'kategori_materi')->withTimestamps();
    }

    // Versi materi
    public function revisi(): BelongsToMany
    {
        return $this->belongsToMany(RevisiMateri::class, 'kategori_revisi', 'kategori_id', 'revisi_id')->withTimestamps();
    }
}
