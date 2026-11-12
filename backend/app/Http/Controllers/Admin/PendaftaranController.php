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

// Verifikasi pendaftaran
class PendaftaranController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $status = $request->query('status', User::STATUS_MENUNGGU);

        if (! in_array($status, [User::STATUS_MENUNGGU, User::STATUS_DITOLAK], true)) {
            $status = User::STATUS_MENUNGGU;
        }

        $pendaftar = User::query()
            ->with('anggota')
            ->where('status', $status)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return UserResource::collection($pendaftar);
    }

    // Detail pendaftar
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'data' => new UserResource($user->loadMissing('anggota')),
        ]);
    }

    // Setujui atau tolak pendaftaran
    public function update(VerifikasiPendaftaranRequest $request, User $user): JsonResponse
    {
        if ($user->status !== User::STATUS_MENUNGGU) {
            throw ValidationException::withMessages([
                'keputusan' => 'Pendaftar ini sudah diputuskan sebelumnya.',
            ]);
        }

        $data = $request->validated();

        if ($data['keputusan'] === 'disetujui' && ! $user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'keputusan' => 'Email pendaftar belum dikonfirmasi. Pendaftaran dapat disetujui setelah pendaftar mengonfirmasi emailnya.',
            ]);
        }

        DB::transaction(function () use ($user, $data) {
            if ($data['keputusan'] === 'disetujui') {
                $user->update(['status' => User::STATUS_AKTIF]);

                $user->anggota()->update([
                    'tanggal_bergabung' => now()->toDateString(),
                    'diverifikasi_pada' => now(),
                    'catatan_verifikasi' => $data['catatan'] ?? null,
                ]);
            } else {
                $user->update(['status' => User::STATUS_DITOLAK]);
                $user->anggota()->update(['catatan_verifikasi' => $data['catatan']]);
            }
        });

        return response()->json([
            'message' => $data['keputusan'] === 'disetujui'
                ? 'Pendaftaran disetujui. Anggota kini dapat masuk.'
                : 'Pendaftaran ditolak. Alasan penolakan akan terlihat saat pendaftar mencoba masuk.',
            'data' => new UserResource($user->load('anggota')),
        ]);
    }
}