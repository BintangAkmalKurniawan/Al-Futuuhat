<?php

namespace App\Policies;

use App\Models\Materi;
use App\Models\User;
use Illuminate\Auth\Access\Response;

// Hak akses materi
class MateriPolicy
{
    public const PESAN_ADMIN = 'Akun admin digunakan untuk mengelola sehingga tidak dapat menulis materi.';

    // Boleh membuat materi
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

    // Boleh mengajukan penarikan
    public function tarik(User $user, Materi $materi): Response
    {
        return $this->penulis($user, $materi);
    }

    // Penulis materi
    private function penulis(User $user, Materi $materi): Response
    {
        if ($user->isAdmin()) {
            return Response::deny(self::PESAN_ADMIN);
        }

        return $user->id === $materi->penulis_id ? Response::allow() : Response::deny();
    }
}
