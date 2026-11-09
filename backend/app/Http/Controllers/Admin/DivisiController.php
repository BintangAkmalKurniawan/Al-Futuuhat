<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DivisiRequest;
use App\Http\Resources\DivisiResource;
use App\Models\Divisi;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * CRUD divisi (KF-07, KP-07.2, KP-07.5).
 * Daftar divisi dibaca dari endpoint publik GET /api/divisi.
 */
class DivisiController extends Controller
{
    /**
     * POST /api/admin/divisi.
     */
    public function store(DivisiRequest $request): JsonResponse
    {
        $divisi = Divisi::create($request->validated());

        return response()->json([
            'message' => 'Divisi ditambahkan.',
            'data' => new DivisiResource($divisi),
        ], 201);
    }

    /**
     * PUT /api/admin/divisi/{divisi}.
     */
    public function update(DivisiRequest $request, Divisi $divisi): JsonResponse
    {
        $divisi->update($request->validated());

        return response()->json([
            'message' => 'Divisi diperbarui.',
            'data' => new DivisiResource($divisi),
        ]);
    }

    /**
     * DELETE /api/admin/divisi/{divisi} — ditolak bila masih dipakai (KP-07.5).
     */
    public function destroy(Divisi $divisi): JsonResponse
    {
        if ($divisi->kepengurusan()->exists()) {
            throw ValidationException::withMessages([
                'divisi' => 'Divisi masih dipakai pada susunan pengurus.',
            ]);
        }

        $divisi->delete();

        return response()->json(['message' => 'Divisi dihapus.']);
    }
}