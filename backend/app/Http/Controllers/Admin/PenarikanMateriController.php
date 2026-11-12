<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VerifikasiPenarikanRequest;
use App\Http\Resources\PenarikanResource;
use App\Models\Materi;
use App\Models\PenarikanMateri;
use App\Notifications\PenarikanDiputuskan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// Keputusan penarikan materi
class PenarikanMateriController extends Controller
{
    // Antrean penarikan
    public function index(): AnonymousResourceCollection
    {
        $penarikan = PenarikanMateri::query()
            ->where('status', PenarikanMateri::STATUS_DIAJUKAN)
            ->with('materi.penulis')
            ->oldest()
            ->paginate(10);

        return PenarikanResource::collection($penarikan);
    }

    // Setujui atau tolak penarikan
    public function verifikasi(VerifikasiPenarikanRequest $request, PenarikanMateri $penarikan): JsonResponse
    {
        if ($penarikan->status !== PenarikanMateri::STATUS_DIAJUKAN) {
            throw ValidationException::withMessages([
                'keputusan' => 'Permintaan ini sudah diputuskan sebelumnya.',
            ]);
        }

        $data = $request->validated();
        $materi = $penarikan->materi()->first();

        DB::transaction(function () use ($request, $penarikan, $materi, $data) {
            $penarikan->update([
                'status' => $data['keputusan'],
                'admin_id' => $request->user()->id,
                'catatan' => $data['catatan'] ?? null,
                'diputuskan_pada' => now(),
            ]);

            if ($data['keputusan'] !== PenarikanMateri::STATUS_DISETUJUI) {
                return;
            }

            $materi->update(['status' => Materi::STATUS_DRAF]);

            $revisiTerakhir = $materi->revisiTerakhir()->first();

            if ($revisiTerakhir !== null) {
                $materi->samakanDenganRevisi($revisiTerakhir);
            }
        });

        $materi->penulis()->first()?->notify(new PenarikanDiputuskan($materi, $penarikan));

        return response()->json([
            'message' => $data['keputusan'] === PenarikanMateri::STATUS_DISETUJUI
                ? 'Materi telah diturunkan dari halaman umum.'
                : 'Permintaan penurunan ditolak. Materi tetap tayang untuk umum.',
            'data' => new PenarikanResource($penarikan->setRelation('materi', $materi)),
        ]);
    }
}
