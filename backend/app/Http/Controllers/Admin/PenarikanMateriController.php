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

/**
 * Keputusan admin atas pengajuan penarikan materi dari publik (KF-20).
 */
class PenarikanMateriController extends Controller
{
    /**
     * GET /api/admin/penarikan-materi — antrean penarikan yang menunggu (KP-20.4).
     */
    public function index(): AnonymousResourceCollection
    {
        $penarikan = PenarikanMateri::query()
            ->where('status', PenarikanMateri::STATUS_DIAJUKAN)
            ->with('materi.penulis') // KNF-09: eager loading
            ->oldest()                // terlama di atas
            ->paginate(10);

        return PenarikanResource::collection($penarikan);
    }

    /**
     * POST /api/admin/penarikan/{penarikan}/verifikasi — setujui/tolak (KP-20.5–20.7).
     */
    public function verifikasi(VerifikasiPenarikanRequest $request, PenarikanMateri $penarikan): JsonResponse
    {
        // KP-20.7: hanya penarikan yang masih menunggu yang dapat diputuskan.
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

            // KP-20.6: bila ditolak, materi tetap terbit dengan versi publik lama.
            if ($data['keputusan'] !== PenarikanMateri::STATUS_DISETUJUI) {
                return;
            }

            // KP-20.5: materi kembali draf sehingga hilang dari halaman publik (404).
            // `diterbitkan_pada` sengaja tidak dikosongkan (tanda pernah terbit).
            $materi->update(['status' => Materi::STATUS_DRAF]);

            // Data di tabel `materi` disamakan dengan revisi terakhir, karena
            // tidak ada lagi versi publik yang perlu dijaga (PRD §8.2).
            $revisiTerakhir = $materi->revisiTerakhir()->first();

            if ($revisiTerakhir !== null) {
                $materi->samakanDenganRevisi($revisiTerakhir);
            }
        });

        // KP-20.5 & KP-20.6: penulis menerima notifikasi berisi keputusan dan catatan.
        $materi->penulis()->first()?->notify(new PenarikanDiputuskan($materi, $penarikan));

        return response()->json([
            'message' => $data['keputusan'] === PenarikanMateri::STATUS_DISETUJUI
                ? 'Materi telah diturunkan dari halaman umum.'
                : 'Permintaan penurunan ditolak. Materi tetap tayang untuk umum.',
            'data' => new PenarikanResource($penarikan->setRelation('materi', $materi)),
        ]);
    }
}
