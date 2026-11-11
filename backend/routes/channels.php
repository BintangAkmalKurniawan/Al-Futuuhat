<?php

use App\Models\Materi;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
| Kanal siaran (KF-15, KP-15.3).
| Dipanggil lewat POST /api/broadcasting/auth dengan token Sanctum.
*/

// Kanal privat forum materi: hanya anggota aktif, untuk materi berstatus apa pun
// karena diskusi berjalan sejak draf (KP-14.1 revisi).
Broadcast::channel('materi.{materiId}', function (User $user, int $materiId) {
    if (! $user->isAktif()) {
        return false;
    }

    return Materi::where('id', $materiId)->exists();
});

// Kanal pribadi pengguna (dipakai bila nanti ada notifikasi real-time).
Broadcast::channel('App.Models.User.{id}', function (User $user, int $id) {
    return $user->id === $id;
});
