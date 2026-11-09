<?php

namespace App\Http\Controllers;

use App\Models\Materi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ringkasan pribadi anggota (F10): jumlah materi per status + notifikasi belum dibaca.
 */
class DasborController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Satu query GROUP BY, bukan empat kali count.
        $jumlah = Materi::query()
            ->where('penulis_id', $user->id)
            ->selectRaw('status, count(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        return response()->json([
            'data' => [
                'materi' => [
                    'draf' => (int) $jumlah->get(Materi::STATUS_DRAF, 0),
                    'diajukan' => (int) $jumlah->get(Materi::STATUS_DIAJUKAN, 0),
                    'terbit' => (int) $jumlah->get(Materi::STATUS_TERBIT, 0),
                    'ditolak' => (int) $jumlah->get(Materi::STATUS_DITOLAK, 0),
                    'total' => (int) $jumlah->sum(),
                ],
                'notifikasi_belum_dibaca' => $user->notifications()->whereNull('read_at')->count(),
            ],
        ]);
    }
}