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

// Profil pengguna
class ProfilController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => new UserResource($request->user()->load('anggota'))]);
    }

    // Ubah profil
    public function update(UbahProfilRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user()->load('anggota');
        $anggota = $user->anggota;
        $fotoLama = $anggota?->foto;

        DB::transaction(function () use ($request, $data, $user, $anggota) {
            $user->update(['nama' => $data['nama']]);

            if ($anggota === null) {
                return;
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

        if ($fotoLama !== null && $fotoLama !== $anggota?->foto) {
            Storage::disk('public')->delete($fotoLama);
        }

        return response()->json([
            'message' => 'Profil sudah diperbarui.',
            'data' => new UserResource($user->refresh()->load('anggota')),
        ]);
    }

    // Ganti kata sandi
    public function gantiKataSandi(GantiKataSandiRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->update(['password' => Hash::make($request->validated('password'))]);
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        return response()->json(['message' => 'Kata sandi sudah diganti. Perangkat lain perlu masuk lagi.']);
    }
}
