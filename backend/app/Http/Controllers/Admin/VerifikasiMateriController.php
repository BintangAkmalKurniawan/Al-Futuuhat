<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VerifikasiRevisiRequest;
use App\Http\Resources\RevisiResource;
use App\Models\Materi;
use App\Models\RevisiMateri;
use App\Models\VerifikasiMateri;
use App\Notifications\RevisiDiverifikasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// Verifikasi publikasi materi oleh admin
class VerifikasiMateriController extends Controller
{
    // Antrean pengajuan
    public function index(): AnonymousResourceCollection
    {
        $revisi = RevisiMateri::query()
            ->where('status', RevisiMateri::STATUS_DIAJUKAN)
            ->with(['materi.penulis'])
            ->oldest()
            ->paginate(10);

        return RevisiResource::collection($revisi);
    }

    // Detail pengajuan
    public function show(RevisiMateri $revisi): JsonResponse
    {
        $revisi->load(['materi.penulis', 'materi.versiTerbit.kategori', 'verifikasi.admin', 'kategori']);

        return response()->json([
            'data' => [
                'revisi' => new RevisiResource($revisi),
                'versi_terbit' => $revisi->materi->versiTerbit
                    ? new RevisiResource($revisi->materi->versiTerbit)
                    : null,
            ],
        ]);
    }

    // Setujui atau tolak pengajuan
    public function verifikasi(VerifikasiRevisiRequest $request, RevisiMateri $revisi): JsonResponse
    {
        if ($revisi->status !== RevisiMateri::STATUS_DIAJUKAN) {
            throw ValidationException::withMessages([
                'keputusan' => 'Pengajuan ini sudah diputuskan sebelumnya.',
            ]);
        }

        $data = $request->validated();
        $materi = $revisi->materi()->first();

        DB::transaction(function () use ($request, $revisi, $materi, $data) {
            $revisi->verifikasi()->create([
                'admin_id' => $request->user()->id,
                'keputusan' => $data['keputusan'],
                'catatan' => $data['catatan'] ?? null,
            ]);

            if ($data['keputusan'] === VerifikasiMateri::KEPUTUSAN_DISETUJUI) {
                $revisi->update(['status' => RevisiMateri::STATUS_DISETUJUI]);

                $materi->update([
                    'status' => Materi::STATUS_TERBIT,
                    'diterbitkan_pada' => $materi->diterbitkan_pada ?? now(),
                ]);

                $materi->samakanDenganRevisi($revisi);

                return;
            }

            $revisi->update(['status' => RevisiMateri::STATUS_DITOLAK]);

            if ($materi->status !== Materi::STATUS_TERBIT) {
                $materi->update(['status' => Materi::STATUS_DITOLAK]);
            }
        });

        $materi->penulis()->first()?->notify(
            new RevisiDiverifikasi($materi, $revisi, $data['keputusan'], $data['catatan'] ?? null)
        );

        return response()->json([
            'message' => $data['keputusan'] === VerifikasiMateri::KEPUTUSAN_DISETUJUI
                ? 'Materi disetujui dan kini tayang untuk umum.'
                : 'Pengajuan ditolak. Catatan Anda telah dikirim kepada penulis.',
            'data' => new RevisiResource($revisi->load('verifikasi.admin')),
        ]);
    }
}