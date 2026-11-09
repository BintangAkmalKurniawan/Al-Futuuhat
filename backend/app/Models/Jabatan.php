<?php

namespace App\Models;

use Database\Factories\JabatanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Jabatan kepengurusan (Ketua, Koordinator, ...) — PRD §10.
 */
class Jabatan extends Model
{
    /** @use HasFactory<JabatanFactory> */
    use HasFactory;

    protected $table = 'jabatan';

    protected $fillable = ['nama_jabatan', 'urutan'];

    public function kepengurusan(): HasMany
    {
        return $this->hasMany(Kepengurusan::class, 'jabatan_id');
    }
}
