<?php

namespace App\Policies;

use App\Models\Materi;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Aturan kepemilikan materi: hanya penulis yang boleh mengubah, menghapus,
 * mengajukan, dan membuat revisi (KP-09.3, KP-10.1, KP-12.1).
 * Pemeriksaan *status* dilakukan di controller dengan pesan 422 agar jelas
 * bedanya "bukan penulis" (403) dan "status tidak mengizinkan" (422).
 *
 * Draf 2.6 (KP-08.6): admin hanya mengelola, tidak menulis materi. Semua aksi
 * penulis ditolak untuk admin — termasuk materi yang ia tulis sebelum diangkat
 * menjadi admin; materi itu kembali dapat dikelola bila perannya dikembalikan.
 */
class MateriPolicy
{
    public const PESAN_ADMIN = 'Akun admin digunakan untuk mengelola sehingga tidak dapat menulis materi.';

    /** Membuat materi baru, membuka "Materi saya", dan mengunggah sampul. */
    public function buat(User $user): Response
    {
        return $user->isAdmin() ? Response::deny(self::PESAN_ADMIN) : Response::allow();
    }

    public function ubah(User $user, Materi $materi): Response
    {
        return $this->penulis($user, $materi);
    }

    public function hapus(User $user, Materi $materi): Response
    {
        return $this->penulis($user, $materi);
    }

    public function ajukan(User $user, Materi $materi): Response
    {
        return $this->penulis($user, $materi);
    }

    public function buatRevisi(User $user, Materi $materi): Response
    {
        return $this->penulis($user, $materi);
    }

    /** Mengajukan atau membatalkan penarikan dari publik (KP-20.1, KP-20.3). */
    public function tarik(User $user, Materi $materi): Response
    {
        return $this->penulis($user, $materi);
    }

    /** Penulis materi ini dan bukan admin. */
    private function penulis(User $user, Materi $materi): Response
    {
        if ($user->isAdmin()) {
            return Response::deny(self::PESAN_ADMIN);
        }

        return $user->id === $materi->penulis_id ? Response::allow() : Response::deny();
    }
}
