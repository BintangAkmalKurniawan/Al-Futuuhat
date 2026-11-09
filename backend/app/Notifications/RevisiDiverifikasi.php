<?php

namespace App\Notifications;

use App\Models\Materi;
use App\Models\RevisiMateri;
use Illuminate\Notifications\Notification;

/**
 * Pemberitahuan ke penulis saat revisinya diverifikasi admin (KP-11.5).
 */
class RevisiDiverifikasi extends Notification
{
    public function __construct(
        private readonly Materi $materi,
        private readonly RevisiMateri $revisi,
        private readonly string $keputusan,
        private readonly ?string $catatan,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $pesan = 'Materi "'.$this->materi->judul.'" versi '.$this->revisi->nomor_versi
            .' '.$this->keputusan.' oleh admin.';

        if ($this->catatan !== null && $this->catatan !== '') {
            $pesan .= ' Catatan: '.$this->catatan;
        }

        return [
            'tipe' => 'revisi_diverifikasi',
            'judul' => $this->materi->judul,
            'keputusan' => $this->keputusan,
            'catatan' => $this->catatan,
            'nomor_versi' => $this->revisi->nomor_versi,
            'materi_id' => $this->materi->id,
            'revisi_id' => $this->revisi->id,
            'pesan' => $pesan,
        ];
    }
}