<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotifikasiResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

// Notifikasi dalam aplikasi
class NotifikasiController extends Controller
{
    // Notifikasi pengguna, terbaru lebih dulu
    public function index(Request $request): AnonymousResourceCollection
    {
        $notifikasi = $request->user()->notifications()->paginate(15);

        return NotifikasiResource::collection($notifikasi);
    }

    // Tandai satu notifikasi sudah dibaca
    public function baca(Request $request, string $id): JsonResponse
    {
        $notifikasi = $request->user()->notifications()->find($id);

        abort_if($notifikasi === null, 404, 'Notifikasinya tidak ketemu.');

        if ($notifikasi->read_at === null) {
            $notifikasi->markAsRead();
        }

        return response()->json([
            'message' => 'Notifikasi sudah ditandai dibaca.',
            'data' => new NotifikasiResource($notifikasi),
        ]);
    }

    // Tandai semua notifikasi sudah dibaca
    public function bacaSemua(Request $request): JsonResponse
    {
        $jumlah = $request->user()->notifications()->whereNull('read_at')->update([
            'read_at' => now(),
        ]);

        return response()->json([
            'message' => 'Semua notifikasi sudah ditandai dibaca.',
            'jumlah' => $jumlah,
        ]);
    }
}