<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VerifikasiPendaftaranRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Verifikasi pendaftaran anggota oleh admin (KF-05).
 */
class PendaftaranController extends Controller
{
    /**
     * GET /api/admin/pendaftaran?status=menunggu|ditolak (KP-05.1).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $status = $request->query('status', User::STATUS_MENUNGGU);

        // Hanya status yang relevan untuk halaman pendaftaran.
        if (! in_array($status, [User::STATUS_MENUNGGU, User::STATUS_DITOLAK], true)) {
            $status = User::STATUS_MENUNGGU;
        }

        $pendaftar = User::query()
            ->with('anggota') // KNF-09: hindari query N+1
            ->where('status', $status)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return UserResource::collection($pendaftar);
    }

    /**
     * GET /api/admin/pendaftaran/{user} — detail pendaftar (KP-05.2).
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'data' => new UserResource($user->loadMissing('anggota')),
        ]);
    }

    /**
     * PATCH /api/admin/pendaftaran/{user} — setujui atau tolak (KP-05.3–05.5).
     */
    public function update(VerifikasiPendaftaranRequest $request, User $user): JsonResponse
    {
        // KP-05.5: pendaftar yang sudah diputuskan tidak bisa diputuskan ulang.
        if ($user->status !== User::STATUS_MENUNGGU) {
            throw ValidationException::withMessages([
                'keputusan' => 'Pendaftar ini sudah diputuskan sebelumnya.',
            ]);
        }

        $data = $request->validated();

        DB::transaction(function () use ($user, $data) {
            if ($data['keputusan'] === 'disetujui') {
                $user->update(['status' => User::STATUS_AKTIF]);

                // KP-05.3: isi tanggal bergabung dan waktu verifikasi.
                $user->anggota()->update([
                    'tanggal_bergabung' => now()->toDateString(),
                    'diverifikasi_pada' => now(),
                    'catatan_verifikasi' => $data['catatan'] ?? null,
                ]);
            } else {
                // KP-05.4: catatan penolakan wajib (divalidasi di FormRequest).
                $user->update(['status' => User::STATUS_DITOLAK]);
                $user->anggota()->update(['catatan_verifikasi' => $data['catatan']]);
            }
        });

        return response()->json([
            'message' => $data['keputusan'] === 'disetujui'
                ? 'Pendaftar disetujui.'
                : 'Pendaftar ditolak.',
            'data' => new UserResource($user->load('anggota')),
        ]);
    }
}