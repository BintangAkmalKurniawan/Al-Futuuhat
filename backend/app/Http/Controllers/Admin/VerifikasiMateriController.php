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

/**
 * Verifikasi publikasi materi oleh admin (KF-11).
 */
class VerifikasiMateriController extends Controller
{
    /**
     * GET /api/admin/verifikasi-materi — antrean revisi diajukan (KP-11.1).
     */
    public function index(): AnonymousResourceCollection
    {
        $revisi = RevisiMateri::query()
            ->where('status', RevisiMateri::STATUS_DIAJUKAN)
            ->with(['materi.penulis']) // KNF-09: eager loading
            ->oldest()                 // terlama di atas
            ->paginate(10);

        return RevisiResource::collection($revisi);
    }

    /**
     * GET /api/admin/revisi/{revisi} — isi revisi + versi terbit sebagai pembanding (KP-11.2).
     */
    public function show(RevisiMateri $revisi): JsonResponse
    {
        $revisi->load(['materi.penulis', 'materi.versiTerbit', 'verifikasi.admin']);

        return response()->json([
            'data' => [
                'revisi' => new RevisiResource($revisi),
                'versi_terbit' => $revisi->materi->versiTerbit
                    ? new RevisiResource($revisi->materi->versiTerbit)
                    : null,
            ],
        ]);
    }

    /**
     * POST /api/admin/revisi/{revisi}/verifikasi — setujui/tolak (KP-11.3–11.6).
     */
    public function verifikasi(VerifikasiRevisiRequest $request, RevisiMateri $revisi): JsonResponse
    {
        // KP-11.6: hanya revisi berstatus diajukan yang dapat diverifikasi.
        if ($revisi->status !== RevisiMateri::STATUS_DIAJUKAN) {
            throw ValidationException::withMessages([
                'keputusan' => 'Hanya revisi yang sedang diajukan yang dapat diverifikasi.',
            ]);
        }

        $data = $request->validated();
        $materi = $revisi->materi()->first();

        DB::transaction(function () use ($request, $revisi, $materi, $data) {
            // KP-11.5: setiap keputusan dicatat sebagai riwayat.
            $revisi->verifikasi()->create([
                'admin_id' => $request->user()->id,
                'keputusan' => $data['keputusan'],
                'catatan' => $data['catatan'] ?? null,
            ]);

            if ($data['keputusan'] === VerifikasiMateri::KEPUTUSAN_DISETUJUI) {
                $revisi->update(['status' => RevisiMateri::STATUS_DISETUJUI]);

                // KP-11.3: materi terbit; tanggal terbit hanya diisi sekali.
                $materi->update([
                    'status' => Materi::STATUS_TERBIT,
                    'diterbitkan_pada' => $materi->diterbitkan_pada ?? now(),
                ]);

                return;
            }

            $revisi->update(['status' => RevisiMateri::STATUS_DITOLAK]);

            // KP-11.4: bila sudah pernah terbit, materi tetap terbit dengan versi lama.
            if ($materi->diterbitkan_pada === null) {
                $materi->update(['status' => Materi::STATUS_DITOLAK]);
            }
        });

        // KP-11.5: penulis menerima notifikasi berisi keputusan dan catatannya.
        $materi->penulis()->first()?->notify(
            new RevisiDiverifikasi($materi, $revisi, $data['keputusan'], $data['catatan'] ?? null)
        );

        return response()->json([
            'message' => $data['keputusan'] === VerifikasiMateri::KEPUTUSAN_DISETUJUI
                ? 'Materi disetujui dan diterbitkan.'
                : 'Revisi ditolak.',
            'data' => new RevisiResource($revisi->load('verifikasi.admin')),
        ]);
    }
}