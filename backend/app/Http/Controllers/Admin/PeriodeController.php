<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PeriodeRequest;
use App\Http\Resources\PeriodeResource;
use App\Models\PeriodeKepengurusan;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CRUD periode kepengurusan (KF-07, KP-07.1, KP-07.5).
 */
class PeriodeController extends Controller
{
    /**
     * POST /api/admin/periode.
     */
    public function store(PeriodeRequest $request): JsonResponse
    {
        $data = $request->validated();

        $periode = DB::transaction(function () use ($data) {
            // KP-07.1: hanya satu periode aktif.
            if (! empty($data['is_aktif'])) {
                PeriodeKepengurusan::where('is_aktif', true)->update(['is_aktif' => false]);
            }

            return PeriodeKepengurusan::create([
                'nama_periode' => $data['nama_periode'],
                'tahun_mulai' => $data['tahun_mulai'],
                'tahun_selesai' => $data['tahun_selesai'],
                'is_aktif' => $data['is_aktif'] ?? false,
            ]);
        });

        return response()->json([
            'message' => 'Periode ditambahkan.',
            'data' => new PeriodeResource($periode),
        ], 201);
    }

    /**
     * PUT /api/admin/periode/{periode}.
     */
    public function update(PeriodeRequest $request, PeriodeKepengurusan $periode): JsonResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($periode, $data) {
            if (! empty($data['is_aktif'])) {
                PeriodeKepengurusan::where('id', '!=', $periode->id)
                    ->where('is_aktif', true)
                    ->update(['is_aktif' => false]);
            }

            $periode->update([
                'nama_periode' => $data['nama_periode'],
                'tahun_mulai' => $data['tahun_mulai'],
                'tahun_selesai' => $data['tahun_selesai'],
                'is_aktif' => $data['is_aktif'] ?? $periode->is_aktif,
            ]);
        });

        return response()->json([
            'message' => 'Periode diperbarui.',
            'data' => new PeriodeResource($periode),
        ]);
    }

    /**
     * DELETE /api/admin/periode/{periode} — ditolak bila masih dipakai (KP-07.5).
     */
    public function destroy(PeriodeKepengurusan $periode): JsonResponse
    {
        if ($periode->kepengurusan()->exists()) {
            throw ValidationException::withMessages([
                'periode' => 'Periode masih dipakai pada susunan pengurus.',
            ]);
        }

        $periode->delete();

        return response()->json(['message' => 'Periode dihapus.']);
    }

    /**
     * PATCH /api/admin/periode/{periode}/aktifkan (KP-07.1).
     */
    public function aktifkan(PeriodeKepengurusan $periode): JsonResponse
    {
        // Mengaktifkan satu periode otomatis menonaktifkan yang lain, dalam transaksi.
        DB::transaction(function () use ($periode) {
            PeriodeKepengurusan::where('id', '!=', $periode->id)->update(['is_aktif' => false]);
            $periode->update(['is_aktif' => true]);
        });

        return response()->json([
            'message' => 'Periode diaktifkan.',
            'data' => new PeriodeResource($periode),
        ]);
    }
}