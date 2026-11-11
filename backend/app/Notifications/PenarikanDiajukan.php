<?php

namespace App\Notifications;

use App\Models\Materi;
use App\Models\PenarikanMateri;
use App\Models\User;
use Illuminate\Notifications\Notification;

/**
 * Untuk semua admin aktif: penulis mengajukan penarikan materi dari publik
 * (PRD §8.4, KP-20.1). Dikirim sinkron lewat kanal `database`.
 */
class PenarikanDiajukan extends Notification
{
    public function __construct(
        private readonly Materi $materi,
        private readonly PenarikanMateri $penarikan,
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
            'tipe' => 'penarikan_diajukan',
            'judul' => $this->materi->judul,
            'penulis' => $this->penulis->nama,
            'alasan' => $this->penarikan->alasan,
            'materi_id' => $this->materi->id,
            'penarikan_id' => $this->penarikan->id,
            'pesan' => $this->penulis->nama.' meminta materi "'.$this->materi->judul
                .'" diturunkan dari halaman umum. Alasan: '.$this->penarikan->alasan,
        ];
    }
}
