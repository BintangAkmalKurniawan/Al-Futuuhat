<?php

namespace App\Http\Controllers;

use App\Events\KomentarDibuat;
use App\Events\KomentarDihapus;
use App\Http\Requests\Komentar\SimpanKomentarRequest;
use App\Http\Resources\KomentarResource;
use App\Models\Komentar;
use App\Models\Materi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

/**
 * Forum diskusi materi terbit (KF-14) dan penyiarannya secara real-time (KF-15).
 */
class KomentarController extends Controller
{
    /**
     * GET /api/materi/{materi}/komentar — komentar induk beserta balasannya.
     */
    public function index(Request $request, Materi $materi): AnonymousResourceCollection
    {
        // KP-14.1: forum hanya ada pada materi terbit.
        abort_unless($materi->status === Materi::STATUS_TERBIT, 404, 'Materi tidak ditemukan.');

        $komentar = $materi->komentar()
            ->whereNull('parent_id')                  // balasan tampil di dalam induknya
            ->with(['user', 'balasan.user'])          // KNF-09: eager loading
            ->oldest()
            ->paginate(10)
            ->withQueryString();

        return KomentarResource::collection($komentar);
    }

    /**
     * POST /api/materi/{materi}/komentar — kirim komentar atau balasan (KP-14.2, KP-14.3).
     */
    public function store(SimpanKomentarRequest $request, Materi $materi): JsonResponse
    {
        abort_unless($materi->status === Materi::STATUS_TERBIT, 404, 'Materi tidak ditemukan.');

        $data = $request->validated();
        $parentId = null;

        if (! empty($data['parent_id'])) {
            $induk = Komentar::find($data['parent_id']);

            // Komentar induk harus berada pada materi yang sama.
            if ($induk === null || $induk->materi_id !== $materi->id) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Komentar induk tidak ditemukan pada materi ini.',
                ]);
            }

            // KP-14.3: balasan hanya satu tingkat — balasan atas balasan
            // ditempatkan pada induk yang sama (diratakan).
            $parentId = $induk->parent_id ?? $induk->id;
        }

        $komentar = Komentar::create([
            'materi_id' => $materi->id,
            'user_id' => $request->user()->id,
            'parent_id' => $parentId,
            'isi' => $data['isi'] ?? null,
            'gambar' => $request->hasFile('gambar')
                ? $request->file('gambar')->store('gambar-komentar', 'public')
                : null,
        ]);

        $komentar->load(['user', 'balasan.user']);

        // KP-15.1: siarkan ke anggota lain yang sedang membuka materi yang sama.
        broadcast(new KomentarDibuat($komentar))->toOthers();

        return response()->json([
            'message' => 'Komentar terkirim.',
            'data' => new KomentarResource($komentar),
        ], 201);
    }

    /**
     * DELETE /api/komentar/{komentar} — hanya pemiliknya (KP-14.4).
     */
    public function destroy(Request $request, Komentar $komentar): JsonResponse
    {
        abort_unless(
            $komentar->user_id === $request->user()->id,
            403,
            'Anda hanya dapat menghapus komentar Anda sendiri.'
        );

        $materiId = $komentar->materi_id;
        $parentId = $komentar->parent_id;
        $komentarId = $komentar->id;

        // Balasan ikut terhapus karena foreign key ON DELETE CASCADE (KP-14.4).
        $komentar->delete();

        // KP-15.2: beri tahu peramban lain agar komentar ini hilang dari daftar.
        broadcast(new KomentarDihapus($materiId, $komentarId, $parentId))->toOthers();

        return response()->json(['message' => 'Komentar dihapus.']);
    }
}