<?php

namespace App\Http\Controllers;

use App\Http\Requests\Materi\UnggahSampulRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

/**
 * Unggahan gambar sampul materi (KF-18). Berkas disimpan di server dan yang
 * dikirim balik hanya path-nya; URL dibentuk di Resource agar tidak terikat
 * nilai APP_URL saat berkas diunggah.
 */
class UnggahSampulController extends Controller
{
    public function store(UnggahSampulRequest $request): JsonResponse
    {
        $path = $request->file('sampul')->store('sampul-materi', 'public');

        return response()->json([
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
        ], 201);
    }
}
