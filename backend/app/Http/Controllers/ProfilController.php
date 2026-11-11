<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profil\GantiKataSandiRequest;
use App\Http\Requests\Profil\UbahProfilRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Profil milik pengguna yang sedang masuk (KF-24): data diri dan kata sandi.
 * Email dan peran tidak dapat diubah di sini.
 */
class ProfilController extends Controller
{
    /** GET /api/profil */
    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => new UserResource($request->user()->load('anggota'))]);
    }

    /**
     * POST /api/profil — multipart karena dapat memuat foto
     * (POST, bukan PUT: PHP tidak membaca body multipart pada PUT).
     */
    public function update(UbahProfilRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user()->load('anggota');
        $anggota = $user->anggota;
        $fotoLama = $anggota?->foto;

        DB::transaction(function () use ($request, $data, $user, $anggota) {
            $user->update(['nama' => $data['nama']]);

            if ($anggota === null) {
                return; // akun tanpa data keanggotaan: cukup nama
            }

            $foto = $anggota->foto;

            if ($request->hasFile('foto')) {
                $foto = $request->file('foto')->store('foto-anggota', 'public');
            } elseif ($request->boolean('hapus_foto')) {
                $foto = null;
            }

            $anggota->update([
                'no_hp' => $data['no_hp'],
                'alamat' => $data['alamat'],
                'foto' => $foto,
            ]);
        });

        // Foto lama dihapus setelah datanya tersimpan.
        if ($fotoLama !== null && $fotoLama !== $anggota?->foto) {
            Storage::disk('public')->delete($fotoLama);
        }

        return response()->json([
            'message' => 'Profil sudah diperbarui.',
            'data' => new UserResource($user->refresh()->load('anggota')),
        ]);
    }

    /**
     * PUT /api/profil/password — token di perangkat lain dicabut, perangkat ini tetap masuk.
     */
    public function gantiKataSandi(GantiKataSandiRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->update(['password' => Hash::make($request->validated('password'))]);
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        return response()->json(['message' => 'Kata sandi sudah diganti. Perangkat lain perlu masuk lagi.']);
    }
}
