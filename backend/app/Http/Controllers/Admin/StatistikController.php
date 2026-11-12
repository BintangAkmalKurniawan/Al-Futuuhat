<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\User;
use Illuminate\Http\JsonResponse;

// Statistik untuk dasbor admin
class StatistikController extends Controller
{
    private const JUMLAH = 5;

    public function index(): JsonResponse
    {
        $terpopuler = Materi::query()
            ->with('penulis')
            ->where('jumlah_dibaca', '>', 0)
            ->orderByDesc('jumlah_dibaca')
            ->limit(self::JUMLAH)
            ->get()
            ->map(fn (Materi $materi) => [
                'id' => $materi->id,
                'judul' => $materi->judul,
                'slug' => $materi->slug,
                'status' => $materi->status,
                'penulis' => $materi->penulis?->nama,
                'jumlah_dibaca' => $materi->jumlah_dibaca,
            ]);

        $sejak = now()->subDays(30);

        $teraktif = User::query()
            ->where('role', User::ROLE_ANGGOTA)
            ->where('status', User::STATUS_AKTIF)
            ->withCount([
                'materi as jumlah_materi' => fn ($query) => $query->where('created_at', '>=', $sejak),
                'komentar as jumlah_komentar' => fn ($query) => $query->where('created_at', '>=', $sejak),
            ])
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'nama' => $user->nama,
                'jumlah_materi' => $user->jumlah_materi,
                'jumlah_komentar' => $user->jumlah_komentar,
                'total' => $user->jumlah_materi + $user->jumlah_komentar,
            ])
            ->where('total', '>', 0)
            ->sortByDesc('total')
            ->take(self::JUMLAH)
            ->values();

        return response()->json([
            'data' => [
                'materi_terpopuler' => $terpopuler,
                'anggota_teraktif' => $teraktif,
            ],
        ]);
    }
}
