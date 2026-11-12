<?php

namespace App\Models;

use Database\Factories\DivisiFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Divisi organisasi
class Divisi extends Model
{
    /** @use HasFactory<DivisiFactory> */
    use HasFactory;

    protected $table = 'divisi';

    protected $fillable = ['nama_divisi', 'deskripsi'];

    public function kepengurusan(): HasMany
    {
        return $this->hasMany(Kepengurusan::class, 'divisi_id');
    }
}
