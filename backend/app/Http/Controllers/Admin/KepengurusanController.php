<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\KepengurusanRequest;
use App\Http\Resources\KepengurusanResource;
use App\Models\Kepengurusan;
use Illuminate\Http\JsonResponse;

/**
 * Menyusun pengurus per periode (KF-07, KP-07.3, KP-07.4).
 */
class KepengurusanController extends Controller
{
    /**
     * POST /api/admin/kepengurusan.
     */
    public function store(KepengurusanRequest $request): JsonResponse
    {
        $kepengurusan = Kepengurusan::create($request->validated());

        return response()->json([
            'message' => 'Pengurus ditambahkan.',
            'data' => new KepengurusanResource(
                $kepengurusan->load('anggota.user', 'divisi', 'jabatan')
            ),
        ], 201);
    }

    /**
     * PUT /api/admin/kepengurusan/{kepengurusan}.
     */
    public function update(KepengurusanRequest $request, Kepengurusan $kepengurusan): JsonResponse
    {
        $kepengurusan->update($request->validated());

        return response()->json([
            'message' => 'Susunan pengurus diperbarui.',
            'data' => new KepengurusanResource(
                $kepengurusan->load('anggota.user', 'divisi', 'jabatan')
            ),
        ]);
    }

    /**
     * DELETE /api/admin/kepengurusan/{kepengurusan}.
     */
    public function destroy(Kepengurusan $kepengurusan): JsonResponse
    {
        $kepengurusan->delete();

        return response()->json(['message' => 'Pengurus dihapus dari susunan.']);
    }
}