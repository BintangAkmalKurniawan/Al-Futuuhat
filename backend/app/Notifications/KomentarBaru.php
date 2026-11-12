<?php

namespace App\Notifications;

use App\Models\Komentar;
use App\Models\Materi;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

// Notifikasi komentar baru
class KomentarBaru extends Notification
{
    public const JENIS_KOMENTAR = 'komentar';

    public const JENIS_BALASAN = 'balasan';

    public function __construct(
        private readonly Materi $materi,
        private readonly Komentar $komentar,
        private readonly string $pengirim,
        private readonly string $jenis,
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
        $teks = trim(html_entity_decode(strip_tags((string) $this->komentar->isi)));
        $cuplikan = $teks !== '' ? Str::limit($teks, 100) : '(gambar)';

        return [
            'tipe' => 'komentar_baru',
            'jenis' => $this->jenis,
            'judul' => $this->materi->judul,
            'materi_id' => $this->materi->id,
            'materi_slug' => $this->materi->slug,
            'komentar_id' => $this->komentar->id,
            'pengirim' => $this->pengirim,
            'cuplikan' => $cuplikan,
            'pesan' => $this->jenis === self::JENIS_BALASAN
                ? $this->pengirim.' membalas komentar di materi "'.$this->materi->judul.'".'
                : $this->pengirim.' mengomentari materi "'.$this->materi->judul.'".',
        ];
    }
}
