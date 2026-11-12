<?php

namespace App\Http\Controllers;

use App\Events\KomentarDibuat;
use App\Events\KomentarDihapus;
use App\Http\Requests\Komentar\SimpanKomentarRequest;
use App\Http\Resources\KomentarResource;
use App\Models\Komentar;
use App\Models\Materi;
use App\Models\User;
use App\Notifications\KomentarBaru;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;
use Mews\Purifier\Facades\Purifier;

// Forum diskusi materi dan penyiarannya secara real-time
class KomentarController extends Controller
{
    // Daftar komentar induk
    public function index(Materi $materi): AnonymousResourceCollection
    {
        $komentar = $materi->komentar()
            ->whereNull('parent_id')
            ->with('user')
            ->withCount('balasan')
            ->oldest()
            ->paginate(10)
            ->withQueryString();

        return KomentarResource::collection($komentar);
    }

    // Daftar balasan satu komentar
    public function balasan(Komentar $komentar): AnonymousResourceCollection
    {
        abort_if(
            $komentar->parent_id !== null,
            404,
            'Komentar ini tidak punya balasan.'
        );

        $balasan = $komentar->balasan()
            ->with('user')
            ->get();

        return KomentarResource::collection($balasan);
    }

    // Kirim komentar atau balasan
    public function store(SimpanKomentarRequest $request, Materi $materi): JsonResponse
    {
        $data = $request->validated();
        $parentId = null;
        $induk = null;

        if (! empty($data['parent_id'])) {
            $induk = Komentar::find($data['parent_id']);

            if ($induk === null || $induk->materi_id !== $materi->id) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Komentar yang mau kamu balas tidak ketemu. Mungkin sudah dihapus.',
                ]);
            }

            $parentId = $induk->parent_id ?? $induk->id;
        }

        $isi = isset($data['isi']) ? Purifier::clean($data['isi'], 'materi') : null;

        if ($this->teksKosong($isi) && ! $request->hasFile('gambar')) {
            throw ValidationException::withMessages([
                'isi' => 'Tulis komentar atau lampirkan gambar dulu ya.',
            ]);
        }

        $komentar = Komentar::create([
            'materi_id' => $materi->id,
            'user_id' => $request->user()->id,
            'parent_id' => $parentId,
            'isi' => $isi,
            'gambar' => $request->hasFile('gambar')
                ? $request->file('gambar')->store('gambar-komentar', 'public')
                : null,
        ]);

        $komentar->load('user');

        broadcast(new KomentarDibuat($komentar))->toOthers();

        $this->kirimNotifikasi($materi, $komentar, $induk, $request->user());

        return response()->json([
            'message' => 'Komentarmu sudah terkirim.',
            'data' => new KomentarResource($komentar),
        ], 201);
    }

    // Kirim notifikasi komentar
    private function kirimNotifikasi(Materi $materi, Komentar $komentar, ?Komentar $induk, User $pengirim): void
    {
        $sudahDiberiTahu = null;

        if ($induk !== null && $induk->user_id !== $pengirim->id) {
            $induk->loadMissing('user');
            $induk->user?->notify(new KomentarBaru($materi, $komentar, $pengirim->nama, KomentarBaru::JENIS_BALASAN));
            $sudahDiberiTahu = $induk->user_id;
        }

        if ($materi->penulis_id !== $pengirim->id && $materi->penulis_id !== $sudahDiberiTahu) {
            $materi->loadMissing('penulis');
            $materi->penulis?->notify(new KomentarBaru($materi, $komentar, $pengirim->nama, KomentarBaru::JENIS_KOMENTAR));
        }
    }

    // Hapus komentar
    public function destroy(Request $request, Komentar $komentar): JsonResponse
    {
        abort_unless(
            $komentar->user_id === $request->user()->id,
            403,
            'Kamu hanya bisa menghapus komentarmu sendiri.'
        );

        $materiId = $komentar->materi_id;
        $parentId = $komentar->parent_id;
        $komentarId = $komentar->id;

        $komentar->delete();

        broadcast(new KomentarDihapus($materiId, $komentarId, $parentId))->toOthers();

        return response()->json(['message' => 'Komentarnya sudah dihapus.']);
    }

    // Cek isi HTML kosong
    private function teksKosong(?string $html): bool
    {
        $teks = html_entity_decode(strip_tags($html ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $teks) ?? '') === '';
    }
}