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

// Pengelolaan data anggota oleh admin
class AnggotaController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status');

        $anggota = User::query()
            ->with('anggota')
            ->when($q !== '', function ($query) use ($q) {
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

    public function show(User $user): JsonResponse
    {
        return response()->json([
            'data' => new UserResource($user->loadMissing('anggota')),
        ]);
    }

    // Ubah data dan peran
    public function update(UpdateAnggotaRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();

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

    // Ubah status aktif
    public function updateStatus(UpdateStatusAnggotaRequest $request, User $user): JsonResponse
    {
        if ($user->is($request->user())) {
            throw ValidationException::withMessages([
                'status' => 'Anda tidak dapat mengubah status akun Anda sendiri.',
            ]);
        }

        $status = $request->validated()['status'];

        $user->update(['status' => $status]);

        if ($status === User::STATUS_NONAKTIF) {
            $user->tokens()->delete();
        }

        return response()->json([
            'message' => $status === User::STATUS_NONAKTIF
                ? 'Akun dinonaktifkan.'
                : 'Akun diaktifkan kembali.',
            'data' => new UserResource($user->loadMissing('anggota')),
        ]);
    }

    // Atur ulang kata sandi
    public function updatePassword(UpdatePasswordRequest $request, User $user): JsonResponse
    {
        $user->update(['password' => $request->validated()['password']]);

        $user->tokens()->delete();

        return response()->json(['message' => 'Kata sandi baru telah disimpan. Sampaikan kata sandi tersebut kepada anggota.']);
    }
}