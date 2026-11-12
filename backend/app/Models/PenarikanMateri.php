<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Pengajuan penarikan materi dari publik oleh penulis
class PenarikanMateri extends Model
{
    public const STATUS_DIAJUKAN = 'diajukan';

    public const STATUS_DISETUJUI = 'disetujui';

    public const STATUS_DITOLAK = 'ditolak';

    public const STATUS_DIBATALKAN = 'dibatalkan';

    protected $table = 'penarikan_materi';

    protected $fillable = ['materi_id', 'alasan', 'status', 'admin_id', 'catatan', 'diputuskan_pada'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'diputuskan_pada' => 'datetime',
        ];
    }

    public function materi(): BelongsTo
    {
        return $this->belongsTo(Materi::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
