<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotifikasiResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Notifikasi dalam aplikasi (PRD §8.3, F10).
 */
class NotifikasiController extends Controller
{
    /**
     * GET /api/notifikasi — notifikasi pengguna, terbaru lebih dulu.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $notifikasi = $request->user()->notifications()->paginate(15);

        return NotifikasiResource::collection($notifikasi);
    }

    /**
     * PATCH /api/notifikasi/{id}/baca — tandai satu notifikasi sudah dibaca.
     */
    public function baca(Request $request, string $id): JsonResponse
    {
        // Hanya boleh menandai notifikasi milik sendiri.
        $notifikasi = $request->user()->notifications()->find($id);

        abort_if($notifikasi === null, 404, 'Notifikasi tidak ditemukan.');

        if ($notifikasi->read_at === null) {
            $notifikasi->markAsRead();
        }

        return response()->json([
            'message' => 'Notifikasi ditandai sudah dibaca.',
            'data' => new NotifikasiResource($notifikasi),
        ]);
    }

    /**
     * PATCH /api/notifikasi/baca-semua — tandai semua notifikasi sudah dibaca.
     */
    public function bacaSemua(Request $request): JsonResponse
    {
        // Satu query UPDATE untuk seluruh notifikasi yang belum dibaca.
        $jumlah = $request->user()->notifications()->whereNull('read_at')->update([
            'read_at' => now(),
        ]);

        return response()->json([
            'message' => 'Semua notifikasi ditandai sudah dibaca.',
            'jumlah' => $jumlah,
        ]);
    }
}