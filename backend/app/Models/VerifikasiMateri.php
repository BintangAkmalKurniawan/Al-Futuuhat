<?php

namespace App\Models;

use Database\Factories\VerifikasiMateriFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Catatan keputusan admin atas sebuah revisi
class VerifikasiMateri extends Model
{
    /** @use HasFactory<VerifikasiMateriFactory> */
    use HasFactory;

    public const KEPUTUSAN_DISETUJUI = 'disetujui';

    public const KEPUTUSAN_DITOLAK = 'ditolak';

    protected $table = 'verifikasi_materi';

    protected $fillable = ['revisi_id', 'admin_id', 'keputusan', 'catatan'];

    public function revisi(): BelongsTo
    {
        return $this->belongsTo(RevisiMateri::class, 'revisi_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
