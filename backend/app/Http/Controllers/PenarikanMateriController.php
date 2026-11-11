<?php

namespace App\Http\Controllers;

use App\Http\Requests\Materi\AjukanPenarikanRequest;
use App\Http\Resources\PenarikanResource;
use App\Models\Materi;
use App\Models\PenarikanMateri;
use App\Models\User;
use App\Notifications\PenarikanDiajukan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Penarikan materi dari publik oleh penulis (KF-20): ajukan dan batalkan.
 * Keputusan admin ada di Admin\PenarikanMateriController.
 */
class PenarikanMateriController extends Controller
{
    /**
     * POST /api/materi/{materi}/tarik — ajukan penarikan (KP-20.1, KP-20.2).
     * Hak penulis sudah diperiksa di AjukanPenarikanRequest (403).
     */
    public function store(AjukanPenarikanRequest $request, Materi $materi): JsonResponse
    {
        // Hanya materi yang sedang terbit yang dapat ditarik.
        if ($materi->status !== Materi::STATUS_TERBIT) {
            throw ValidationException::withMessages([
                'materi' => 'Hanya materi yang sedang tayang yang bisa diturunkan.',
            ]);
        }

        // PRD §8.3: paling banyak satu penarikan yang menunggu per materi.
        if ($materi->penarikanMenunggu()->exists()) {
            throw ValidationException::withMessages([
                'materi' => 'Permintaan turunmu masih dicek admin.',
            ]);
        }

        $penarikan = $materi->penarikan()->create([
            'alasan' => $request->validated('alasan'),
            'status' => PenarikanMateri::STATUS_DIAJUKAN,
        ]);

        // KP-20.1: semua admin aktif menerima notifikasi. Materi tetap terbit (KP-20.2).
        $admin = User::where('role', User::ROLE_ADMIN)
            ->where('status', User::STATUS_AKTIF)
            ->get();

        Notification::send($admin, new PenarikanDiajukan($materi, $penarikan, $request->user()));

        return response()->json([
            'message' => 'Permintaanmu sudah dikirim. Materi tetap tayang sampai admin menyetujuinya.',
            'data' => new PenarikanResource($penarikan->setRelation('materi', $materi)),
        ], 201);
    }

    /**
     * POST /api/materi/{materi}/batal-tarik — batalkan penarikan (KP-20.3).
     */
    public function batal(Request $request, Materi $materi): JsonResponse
    {
        $this->authorize('tarik', $materi);

        $penarikan = $materi->penarikanMenunggu()->first();

        if ($penarikan === null) {
            throw ValidationException::withMessages([
                'materi' => 'Tidak ada permintaan turun yang bisa dibatalkan.',
            ]);
        }

        $penarikan->update(['status' => PenarikanMateri::STATUS_DIBATALKAN]);

        return response()->json([
            'message' => 'Permintaan turun sudah dibatalkan.',
            'data' => new PenarikanResource($penarikan->setRelation('materi', $materi)),
        ]);
    }
}
