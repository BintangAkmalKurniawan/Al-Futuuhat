<?php

namespace App\Policies;

use App\Models\Materi;
use App\Models\User;

/**
 * Aturan kepemilikan materi: hanya penulis yang boleh mengubah, menghapus,
 * mengajukan, dan membuat revisi (KP-09.3, KP-10.1, KP-12.1).
 * Pemeriksaan *status* dilakukan di controller dengan pesan 422 agar jelas
 * bedanya "bukan penulis" (403) dan "status tidak mengizinkan" (422).
 */
class MateriPolicy
{
    public function ubah(User $user, Materi $materi): bool
    {
        return $user->id === $materi->penulis_id;
    }

    public function hapus(User $user, Materi $materi): bool
    {
        return $user->id === $materi->penulis_id;
    }

    public function ajukan(User $user, Materi $materi): bool
    {
        return $user->id === $materi->penulis_id;
    }

    public function buatRevisi(User $user, Materi $materi): bool
    {
        return $user->id === $materi->penulis_id;
    }
}