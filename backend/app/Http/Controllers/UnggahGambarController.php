<?php

namespace App\Http\Controllers;

use App\Http\Requests\Materi\UnggahGambarRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

// Unggahan gambar untuk editor Quill
class UnggahGambarController extends Controller
{
    public function store(UnggahGambarRequest $request): JsonResponse
    {
        $path = $request->file('gambar')->store('gambar-materi', 'public');

        return response()->json([
            'url' => Storage::disk('public')->url($path),
        ], 201);
    }
}