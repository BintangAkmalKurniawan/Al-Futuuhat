<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Pendaftaran, login, logout, dan data pengguna yang sedang login
 * (KF-02, KF-03, KF-04).
 */
class AuthController extends Controller
{
    /**
     * POST /api/register — pengunjung mendaftar (KF-02).
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Foto disimpan lebih dulu, di luar transaksi basis data.
        $foto = $request->hasFile('foto')
            ? $request->file('foto')->store('foto-anggota', 'public')
            : null;

        // KP-03.1: baris users + anggota dibuat dalam satu transaksi.
        $user = DB::transaction(function () use ($data, $foto) {
            $user = User::create([
                'nama' => $data['nama'],
                'email' => $data['email'],
                'password' => $data['password'], // otomatis di-hash oleh cast "hashed"
                'role' => User::ROLE_ANGGOTA,
                'status' => User::STATUS_MENUNGGU,
            ]);

            $user->anggota()->create([
                'no_hp' => $data['no_hp'],
                'alamat' => $data['alamat'],
                'alasan_bergabung' => $data['alasan_bergabung'],
                'foto' => $foto,
            ]);

            return $user;
        });

        return response()->json([
            'message' => 'Pendaftaran terkirim. Akun Anda menunggu verifikasi admin.',
            'data' => new UserResource($user->load('anggota')),
        ], 201);
    }

    /**
     * POST /api/login (KF-04).
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::with('anggota')->where('email', $data['email'])->first();

        // KP-04.1: satu pesan yang sama, agar tidak membocorkan email mana yang terdaftar.
        if ($user === null || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi tidak sesuai.',
            ]);
        }

        // KP-03.3, KP-04.2, KP-04.3: hanya akun aktif yang boleh mendapat token.
        if (! $user->isAktif()) {
            return response()->json([
                'message' => $this->pesanStatusTidakAktif($user),
                'status_akun' => $user->status,
                'catatan' => $user->anggota?->catatan_verifikasi,
            ], 403);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    /**
     * POST /api/logout — menghapus token yang sedang dipakai (KP-04.6).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Anda telah keluar.']);
    }

    /**
     * GET /api/me — pengguna yang sedang login.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => new UserResource($request->user()->loadMissing('anggota')),
        ]);
    }

    /**
     * Pesan penolakan login sesuai status akun (PRD §8.1).
     */
    private function pesanStatusTidakAktif(User $user): string
    {
        return match ($user->status) {
            User::STATUS_MENUNGGU => 'Pendaftaran Anda masih menunggu verifikasi admin.',
            User::STATUS_DITOLAK => 'Pendaftaran Anda ditolak. Alasan: '
                .($user->anggota?->catatan_verifikasi ?: '-'),
            User::STATUS_NONAKTIF => 'Akun Anda dinonaktifkan. Hubungi pengurus.',
            default => 'Akun Anda tidak aktif. Hubungi pengurus.',
        };
    }
}