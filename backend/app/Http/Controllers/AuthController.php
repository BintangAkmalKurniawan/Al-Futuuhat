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
use Throwable;

// Pendaftaran, login, logout, dan data pengguna yang sedang login
class AuthController extends Controller
{
    // Pengunjung mendaftar
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $foto = $request->hasFile('foto')
            ? $request->file('foto')->store('foto-anggota', 'public')
            : null;

        $user = DB::transaction(function () use ($data, $foto) {
            $user = User::create([
                'nama' => $data['nama'],
                'email' => $data['email'],
                'password' => $data['password'],
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

        try {
            $user->sendEmailVerificationNotification();
        } catch (Throwable $e) {
            report($e);
        }

        return response()->json([
            'message' => 'Pendaftaranmu sudah terkirim. Cek emailmu untuk konfirmasi ya.',
            'data' => new UserResource($user->load('anggota')),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::with('anggota')->where('email', $data['email'])->first();

        if ($user === null || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandinya belum cocok. Coba cek lagi ya.',
            ]);
        }

        if (! $user->isAktif()) {
            return response()->json([
                'message' => $this->pesanStatusTidakAktif($user),
                'status_akun' => $user->status,
                'email_terkonfirmasi' => $user->hasVerifiedEmail(),
                'catatan' => $user->anggota?->catatan_verifikasi,
            ], 403);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    // Keluar
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Kamu sudah keluar. Sampai jumpa lagi!']);
    }

    // Data pengguna yang sedang login
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => new UserResource($request->user()->loadMissing('anggota')),
        ]);
    }

    // Pesan penolakan login sesuai status akun
    private function pesanStatusTidakAktif(User $user): string
    {
        if ($user->status === User::STATUS_MENUNGGU && ! $user->hasVerifiedEmail()) {
            return 'Konfirmasi emailmu dulu ya. Cek kotak masuk atau folder spam, lalu klik tautannya.';
        }

        return match ($user->status) {
            User::STATUS_MENUNGGU => 'Kamu belum bisa masuk dulu. Tunggu kabar dari admin ya, biasanya tidak lama.',
            User::STATUS_DITOLAK => 'Admin belum menyetujui pendaftaranmu. Kalau ada pertanyaan, hubungi pengurus ya.',
            User::STATUS_NONAKTIF => 'Kamu belum bisa masuk dulu. Hubungi pengurus untuk mengaktifkannya lagi ya.',
            default => 'Kamu belum bisa masuk dulu. Hubungi pengurus untuk mengaktifkannya lagi ya.',
        };
    }
}