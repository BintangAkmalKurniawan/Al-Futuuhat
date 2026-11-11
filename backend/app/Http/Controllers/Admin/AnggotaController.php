<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAnggotaRequest;
use App\Http\Requests\Admin\UpdatePasswordRequest;
use App\Http\Requests\Admin\UpdateStatusAnggotaRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

/**
 * Pengelolaan data anggota oleh admin (KF-06).
 */
class AnggotaController extends Controller
{
    /**
     * GET /api/admin/anggota?q=&status=&page= (KP-06.1).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status');

        $anggota = User::query()
            ->with('anggota') // KNF-09: eager loading
            ->when($q !== '', function ($query) use ($q) {
                // ilike = pencarian tanpa membedakan huruf besar/kecil (PostgreSQL)
                $query->where(function ($sub) use ($q) {
                    $sub->where('nama', 'ilike', "%{$q}%")
                        ->orWhere('email', 'ilike', "%{$q}%");
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return UserResource::collection($anggota);
    }

    /**
     * GET /api/admin/anggota/{user}.
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'data' => new UserResource($user->loadMissing('anggota')),
        ]);
    }

    /**
     * PUT /api/admin/anggota/{user} — ubah data dan peran (KP-06.2, KP-06.5).
     */
    public function update(UpdateAnggotaRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();

        // KP-06.5: admin tidak boleh menurunkan peran dirinya sendiri.
        if ($user->is($request->user()) && $data['role'] !== User::ROLE_ADMIN) {
            throw ValidationException::withMessages([
                'role' => 'Anda tidak dapat menurunkan peran Anda sendiri.',
            ]);
        }

        $user->update([
            'nama' => $data['nama'],
            'role' => $data['role'],
        ]);

        $user->anggota()->update([
            'no_hp' => $data['no_hp'],
            'alamat' => $data['alamat'],
        ]);

        return response()->json([
            'message' => 'Data anggota diperbarui.',
            'data' => new UserResource($user->load('anggota')),
        ]);
    }

    /**
     * PATCH /api/admin/anggota/{user}/status — aktif/nonaktif (KP-06.3, KP-06.5).
     */
    public function updateStatus(UpdateStatusAnggotaRequest $request, User $user): JsonResponse
    {
        // KP-06.5: admin tidak boleh menonaktifkan dirinya sendiri.
        if ($user->is($request->user())) {
            throw ValidationException::withMessages([
                'status' => 'Anda tidak dapat mengubah status akun Anda sendiri.',
            ]);
        }

        $status = $request->validated()['status'];

        $user->update(['status' => $status]);

        if ($status === User::STATUS_NONAKTIF) {
            // KP-06.3: token lama dihapus agar akun langsung tidak bisa memakai API.
            $user->tokens()->delete();
        }

        return response()->json([
            'message' => $status === User::STATUS_NONAKTIF
                ? 'Akun dinonaktifkan.'
                : 'Akun diaktifkan kembali.',
            'data' => new UserResource($user->loadMissing('anggota')),
        ]);
    }

    /**
     * PATCH /api/admin/anggota/{user}/password — atur ulang kata sandi (KP-06.4).
     */
    public function updatePassword(UpdatePasswordRequest $request, User $user): JsonResponse
    {
        $user->update(['password' => $request->validated()['password']]);

        // Keamanan: paksa login ulang dengan kata sandi baru.
        $user->tokens()->delete();

        return response()->json(['message' => 'Kata sandi baru telah disimpan. Sampaikan kata sandi tersebut kepada anggota.']);
    }
}