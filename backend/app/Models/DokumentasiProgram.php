<?php

namespace App\Models;

use Database\Factories\DokumentasiProgramFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu butir dokumentasi program: foto, video MP4, atau tautan YouTube (KP-21.3).
 */
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

    /**
     * Ambil ID video (11 karakter) dari tautan YouTube, atau null bila tautan
     * bukan video YouTube. Bentuk yang diterima:
     * youtube.com/watch?v=ID, youtu.be/ID, youtube.com/shorts/ID, youtube.com/embed/ID.
     */
    public static function ambilIdYoutube(string $tautan): ?string
    {
        $pola = '~^(?:https?://)?(?:www\.|m\.)?'
            .'(?:youtube\.com/(?:watch\?(?:.*&)?v=|shorts/|embed/|live/)|youtu\.be/)'
            .'([A-Za-z0-9_-]{11})(?:[?&#/].*)?$~';

        return preg_match($pola, trim($tautan), $hasil) === 1 ? $hasil[1] : null;
    }
}
