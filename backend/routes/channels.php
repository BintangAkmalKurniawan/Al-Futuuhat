<?php

use App\Models\Materi;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
| Kanal siaran (KF-15, KP-15.3).
| Dipanggil lewat POST /api/broadcasting/auth dengan token Sanctum.
*/

// Kanal privat forum materi: hanya anggota aktif dan hanya untuk materi terbit.
Broadcast::channel('materi.{materiId}', function (User $user, int $materiId) {
    if (! $user->isAktif()) {
        return false;
    }

    return Materi::where('id', $materiId)
        ->where('status', Materi::STATUS_TERBIT)
        ->exists();
});

// Kanal pribadi pengguna (dipakai bila nanti ada notifikasi real-time).
Broadcast::channel('App.Models.User.{id}', function (User $user, int $id) {
    return $user->id === $id;
});
