<?php

namespace App\Models;

use Database\Factories\DokumentasiProgramFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Satu butir dokumentasi program
class DokumentasiProgram extends Model
{
    /** @use HasFactory<DokumentasiProgramFactory> */
    use HasFactory;

    public const JENIS_FOTO = 'foto';

    public const JENIS_VIDEO = 'video';

    public const JENIS_YOUTUBE = 'youtube';

    protected $table = 'dokumentasi_program';

    protected $fillable = [
        'program_id',
        'jenis',
        'path',
        'youtube_id',
        'keterangan',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    // Ambil ID video YouTube
    public static function ambilIdYoutube(string $tautan): ?string
    {
        $pola = '~^(?:https?://)?(?:www\.|m\.)?'
            .'(?:youtube\.com/(?:watch\?(?:.*&)?v=|shorts/|embed/|live/)|youtu\.be/)'
            .'([A-Za-z0-9_-]{11})(?:[?&#/].*)?$~';

        return preg_match($pola, trim($tautan), $hasil) === 1 ? $hasil[1] : null;
    }
}
