<?php

use App\Models\Materi;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// Kanal forum materi
Broadcast::channel('materi.{materiId}', function (User $user, int $materiId) {
    if (! $user->isAktif()) {
        return false;
    }

    return Materi::where('id', $materiId)->exists();
});

// Kanal pribadi pengguna
Broadcast::channel('App.Models.User.{id}', function (User $user, int $id) {
    return $user->id === $id;
});
