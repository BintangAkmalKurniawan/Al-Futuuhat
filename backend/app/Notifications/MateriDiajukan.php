<?php

namespace App\Notifications;

use App\Models\Materi;
use App\Models\RevisiMateri;
use App\Models\User;
use Illuminate\Notifications\Notification;

/**
 * Pemberitahuan ke semua admin aktif saat ada materi diajukan (KP-10.3).
 * Kanal `database` = notifikasi dalam aplikasi (PRD §8.3).
 */
class MateriDiajukan extends Notification
{
    public function __construct(
        private readonly Materi $materi,
        private readonly RevisiMateri $revisi,
        private readonly User $penulis,
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
        return [
            'tipe' => 'materi_diajukan',
            'judul' => $this->materi->judul,
            'penulis' => $this->penulis->nama,
            'nomor_versi' => $this->revisi->nomor_versi,
            'materi_id' => $this->materi->id,
            'revisi_id' => $this->revisi->id,
            'pesan' => 'Materi "'.$this->materi->judul.'" diajukan oleh '.$this->penulis->nama
                .' untuk ditayangkan kepada umum. Mohon ditinjau.',
        ];
    }
}