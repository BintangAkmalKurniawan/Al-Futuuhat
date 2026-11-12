<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\PenarikanMateri;
use App\Models\RevisiMateri;
use App\Models\User;
use Illuminate\Http\JsonResponse;

// Ringkasan untuk halaman admin
class DasborController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'pendaftar_menunggu' => User::where('status', User::STATUS_MENUNGGU)->count(),
                'revisi_diajukan' => RevisiMateri::where('status', RevisiMateri::STATUS_DIAJUKAN)->count(),
                'penarikan_diajukan' => PenarikanMateri::where('status', PenarikanMateri::STATUS_DIAJUKAN)->count(),
                'anggota_aktif' => User::where('status', User::STATUS_AKTIF)->count(),
                'materi_terbit' => Materi::where('status', Materi::STATUS_TERBIT)->count(),
            ],
        ]);
    }
}