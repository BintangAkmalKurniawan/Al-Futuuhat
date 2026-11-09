<?php

namespace App\Http\Controllers;

use App\Http\Resources\DivisiResource;
use App\Http\Resources\PengurusPublikResource;
use App\Http\Resources\PeriodeResource;
use App\Models\Divisi;
use App\Models\PeriodeKepengurusan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Data publik: profil struktur organisasi (KF-01).
 * Hanya nama, jabatan, dan divisi yang dikirim (KP-01.5, KNF-02).
 */
class StrukturController extends Controller
{
    /**
     * GET /api/divisi.
     */
    public function divisi(): AnonymousResourceCollection
    {
        return DivisiResource::collection(Divisi::orderBy('nama_divisi')->get());
    }

    /**
     * GET /api/periode — periode aktif ditandai dan tampil paling atas (KP-01.2).
     */
    public function periode(): AnonymousResourceCollection
    {
        return PeriodeResource::collection(
            PeriodeKepengurusan::orderByDesc('is_aktif')->orderByDesc('tahun_mulai')->get()
        );
    }

    /**
     * GET /api/periode/{periode}/struktur (KP-01.3, KP-01.4).
     */
    public function struktur(PeriodeKepengurusan $periode): JsonResponse
    {
        // Satu query untuk semua pengurus periode ini, diurutkan sesuai jabatan.urutan.
        $pengurus = $periode->kepengurusan()
            ->join('jabatan', 'jabatan.id', '=', 'kepengurusan.jabatan_id')
            ->with(['anggota.user', 'jabatan', 'divisi'])
            ->orderBy('jabatan.urutan')
            ->orderBy('kepengurusan.id')
            ->get(['kepengurusan.*']);

        // KP-01.4: pengurus inti (tanpa divisi) dipisah dari pengurus per divisi.
        $inti = $pengurus->whereNull('divisi_id')->values();
        $perDivisi = $pengurus->whereNotNull('divisi_id')->groupBy('divisi_id');

        $divisi = Divisi::orderBy('nama_divisi')->get()->map(fn (Divisi $item) => [
            'id' => $item->id,
            'nama_divisi' => $item->nama_divisi,
            'deskripsi' => $item->deskripsi,
            'pengurus' => PengurusPublikResource::collection(
                $perDivisi->get($item->id, collect())
            ),
        ]);

        return response()->json([
            'data' => [
                'periode' => new PeriodeResource($periode),
                'inti' => PengurusPublikResource::collection($inti),
                'divisi' => $divisi,
            ],
        ]);
    }
}