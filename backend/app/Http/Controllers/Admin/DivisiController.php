<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DivisiRequest;
use App\Http\Resources\DivisiResource;
use App\Models\Divisi;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

// CRUD divisi
class DivisiController extends Controller
{
    public function store(DivisiRequest $request): JsonResponse
    {
        $divisi = Divisi::create($request->validated());

        return response()->json([
            'message' => 'Divisi ditambahkan.',
            'data' => new DivisiResource($divisi),
        ], 201);
    }

    public function update(DivisiRequest $request, Divisi $divisi): JsonResponse
    {
        $divisi->update($request->validated());

        return response()->json([
            'message' => 'Divisi diperbarui.',
            'data' => new DivisiResource($divisi),
        ]);
    }

    // Hapus divisi
    public function destroy(Divisi $divisi): JsonResponse
    {
        if ($divisi->kepengurusan()->exists()) {
            throw ValidationException::withMessages([
                'divisi' => 'Divisi ini masih dipakai pada susunan pengurus sehingga belum dapat dihapus.',
            ]);
        }

        $divisi->delete();

        return response()->json(['message' => 'Divisi dihapus.']);
    }
}