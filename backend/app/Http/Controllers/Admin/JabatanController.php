<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\JabatanRequest;
use App\Http\Resources\JabatanResource;
use App\Models\Jabatan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

/**
 * CRUD jabatan (KF-07, KP-07.2, KP-07.5).
 */
class JabatanController extends Controller
{
    /**
     * GET /api/admin/jabatan — diurutkan sesuai kolom `urutan`.
     */
    public function index(): AnonymousResourceCollection
    {
        $jabatan = Jabatan::orderBy('urutan')->orderBy('nama_jabatan')->get();

        return JabatanResource::collection($jabatan);
    }

    /**
     * POST /api/admin/jabatan.
     */
    public function store(JabatanRequest $request): JsonResponse
    {
        $jabatan = Jabatan::create($request->validated());

        return response()->json([
            'message' => 'Jabatan ditambahkan.',
            'data' => new JabatanResource($jabatan),
        ], 201);
    }

    /**
     * PUT /api/admin/jabatan/{jabatan}.
     */
    public function update(JabatanRequest $request, Jabatan $jabatan): JsonResponse
    {
        $jabatan->update($request->validated());

        return response()->json([
            'message' => 'Jabatan diperbarui.',
            'data' => new JabatanResource($jabatan),
        ]);
    }

    /**
     * DELETE /api/admin/jabatan/{jabatan} — ditolak bila masih dipakai (KP-07.5).
     */
    public function destroy(Jabatan $jabatan): JsonResponse
    {
        if ($jabatan->kepengurusan()->exists()) {
            throw ValidationException::withMessages([
                'jabatan' => 'Jabatan masih dipakai pada susunan pengurus.',
            ]);
        }

        $jabatan->delete();

        return response()->json(['message' => 'Jabatan dihapus.']);
    }
}