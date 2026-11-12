<?php

namespace App\Models;

use Database\Factories\KomentarFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Komentar forum
class Komentar extends Model
{
    /** @use HasFactory<KomentarFactory> */
    use HasFactory;

    protected $table = 'komentar';

    protected $fillable = ['materi_id', 'user_id', 'parent_id', 'isi', 'gambar'];

    public function materi(): BelongsTo
    {
        return $this->belongsTo(Materi::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function balasan(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('created_at');
    }
}
