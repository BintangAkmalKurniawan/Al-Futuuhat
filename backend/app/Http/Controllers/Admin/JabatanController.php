<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\JabatanRequest;
use App\Http\Resources\JabatanResource;
use App\Models\Jabatan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

// CRUD jabatan
class JabatanController extends Controller
{
    // Daftar jabatan
    public function index(): AnonymousResourceCollection
    {
        $jabatan = Jabatan::orderBy('urutan')->orderBy('nama_jabatan')->get();

        return JabatanResource::collection($jabatan);
    }

    public function store(JabatanRequest $request): JsonResponse
    {
        $jabatan = Jabatan::create($request->validated());

        return response()->json([
            'message' => 'Jabatan ditambahkan.',
            'data' => new JabatanResource($jabatan),
        ], 201);
    }

    public function update(JabatanRequest $request, Jabatan $jabatan): JsonResponse
    {
        $jabatan->update($request->validated());

        return response()->json([
            'message' => 'Jabatan diperbarui.',
            'data' => new JabatanResource($jabatan),
        ]);
    }

    // Hapus jabatan
    public function destroy(Jabatan $jabatan): JsonResponse
    {
        if ($jabatan->kepengurusan()->exists()) {
            throw ValidationException::withMessages([
                'jabatan' => 'Jabatan ini masih dipakai pada susunan pengurus sehingga belum dapat dihapus.',
            ]);
        }

        $jabatan->delete();

        return response()->json(['message' => 'Jabatan dihapus.']);
    }
}