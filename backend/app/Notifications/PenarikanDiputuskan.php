<?php

namespace App\Notifications;

use App\Models\Materi;
use App\Models\PenarikanMateri;
use Illuminate\Notifications\Notification;

/**
 * Untuk penulis: keputusan admin atas pengajuan penarikan (PRD §8.4,
 * KP-20.5, KP-20.6).
 */
class PenarikanDiputuskan extends Notification
{
    public function __construct(
        private readonly Materi $materi,
        private readonly PenarikanMateri $penarikan,
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
        $disetujui = $this->penarikan->status === PenarikanMateri::STATUS_DISETUJUI;

        $pesan = $disetujui
            ? 'Materi "'.$this->materi->judul.'" sudah diturunkan dari halaman umum. Sekarang kamu bisa mengajukan versi terbarumu.'
            : 'Admin belum menyetujui penurunan materi "'.$this->materi->judul.'", jadi materinya masih tayang.';

        if ($this->penarikan->catatan !== null && $this->penarikan->catatan !== '') {
            $pesan .= ' Catatan dari admin: '.$this->penarikan->catatan;
        }

        return [
            'tipe' => 'penarikan_diputuskan',
            'judul' => $this->materi->judul,
            'keputusan' => $this->penarikan->status,
            'catatan' => $this->penarikan->catatan,
            'materi_id' => $this->materi->id,
            'penarikan_id' => $this->penarikan->id,
            'pesan' => $pesan,
        ];
    }
}
