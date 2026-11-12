<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\KirimUlangVerifikasiRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

// Verifikasi email
class VerifikasiEmailController extends Controller
{
    // Konfirmasi dari tautan email
    public function verifikasi(Request $request, int $id, string $hash): RedirectResponse
    {
        $user = User::find($id);

        if (
            $user === null
            || ! $request->hasValidSignature(false)
            || ! hash_equals(sha1($user->getEmailForVerification()), $hash)
        ) {
            return $this->keFrontend('gagal');
        }

        if ($user->hasVerifiedEmail()) {
            return $this->keFrontend('sudah');
        }

        $user->markEmailAsVerified();

        return $this->keFrontend('berhasil');
    }

    // Kirim ulang tautan
    public function kirimUlang(KirimUlangVerifikasiRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        if ($user !== null && ! $user->hasVerifiedEmail()) {
            try {
                $user->sendEmailVerificationNotification();
            } catch (Throwable $e) {
                report($e);
            }
        }

        return response()->json([
            'message' => 'Kalau emailnya terdaftar dan belum dikonfirmasi, tautannya sudah kami kirim. Cek kotak masuk atau folder spam ya.',
        ]);
    }

    // Alihkan ke halaman hasil
    private function keFrontend(string $status): RedirectResponse
    {
        return redirect()->away(config('app.frontend_url').'/verifikasi-email?status='.$status);
    }
}
