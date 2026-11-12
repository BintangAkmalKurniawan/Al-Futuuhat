<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Program\UnggahDokumentasiRequest;
use App\Http\Resources\DokumentasiProgramResource;
use App\Models\DokumentasiProgram;
use App\Models\Program;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

// Unggah dan hapus dokumentasi program oleh admin
class DokumentasiProgramController extends Controller
{
    // Unggah dokumentasi
    public function store(UnggahDokumentasiRequest $request, Program $program): JsonResponse
    {
        $data = $request->validated();
        $youtube = $data['jenis'] === DokumentasiProgram::JENIS_YOUTUBE;

        $dokumentasi = $program->dokumentasi()->create([
            'jenis' => $data['jenis'],
            'path' => $youtube ? null : $request->file('berkas')->store('program/dokumentasi', 'public'),
            'youtube_id' => $youtube ? DokumentasiProgram::ambilIdYoutube($data['tautan']) : null,
            'keterangan' => $data['keterangan'] ?? null,
        ]);

        return response()->json([
            'message' => 'Dokumentasi ditambahkan.',
            'data' => new DokumentasiProgramResource($dokumentasi),
        ], 201);
    }

    // Hapus dokumentasi
    public function destroy(DokumentasiProgram $dokumentasi): JsonResponse
    {
        $path = $dokumentasi->path;

        $dokumentasi->delete();

        if ($path !== null) {
            Storage::disk('public')->delete($path);
        }

        return response()->json(['message' => 'Dokumentasi dihapus.']);
    }
}
