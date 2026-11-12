<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\AturUlangKataSandiRequest;
use App\Http\Requests\Auth\LupaKataSandiRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

// Lupa kata sandi
class KataSandiController extends Controller
{
    // Kirim tautan atur ulang
    public function kirimTautan(LupaKataSandiRequest $request): JsonResponse
    {
        Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => 'Kalau emailnya terdaftar, tautannya sudah kami kirim. Cek kotak masuk atau folder spam ya.',
        ]);
    }

    // Simpan kata sandi baru
    public function aturUlang(AturUlangKataSandiRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->update(['password' => $password]);
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'token' => 'Tautannya sudah tidak berlaku. Minta tautan baru ya.',
            ]);
        }

        return response()->json([
            'message' => 'Kata sandimu sudah diganti. Silakan masuk pakai kata sandi baru.',
        ]);
    }
}
