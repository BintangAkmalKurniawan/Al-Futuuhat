<?php

namespace App\Http\Controllers;

use App\Http\Resources\DivisiResource;
use App\Http\Resources\PengurusPublikResource;
use App\Http\Resources\PeriodeResource;
use App\Http\Resources\RiwayatStrukturResource;
use App\Models\Divisi;
use App\Models\Kepengurusan;
use App\Models\PeriodeKepengurusan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;

/**
 * Struktur organisasi (KF-01, KF-07).
 *
 * Draf 2.5: pengunjung hanya melihat periode saat ini (KP-01.2); daftar periode,
 * struktur periode lama, dan riwayat perubahan hanya untuk anggota yang login
 * (KP-01.3, KP-07.8). Data pengurus hanya nama, jabatan, divisi (KP-01.5).
 */
class StrukturController extends Controller
{
    /**
     * GET /api/divisi (P).
     */
    public function divisi(): AnonymousResourceCollection
    {
        return DivisiResource::collection(Divisi::orderBy('nama_divisi')->get());
    }

    /**
     * GET /api/publik/struktur (P) — struktur periode saat ini saja.
     * `data` bernilai null bila belum ada periode sama sekali.
     */
    public function publik(): JsonResponse
    {
        $periode = PeriodeKepengurusan::aktif();

        return response()->json(['data' => $periode ? $this->susun($periode) : null]);
    }

    /**
     * GET /api/periode (A) — semua periode, periode aktif paling atas.
     */
    public function periode(): AnonymousResourceCollection
    {
        return PeriodeResource::collection(
            PeriodeKepengurusan::orderByDesc('is_aktif')->orderByDesc('tahun_mulai')->orderByDesc('id')->get()
        );
    }

    /**
     * GET /api/periode/{periode}/struktur (A) — termasuk periode lama (KP-01.3).
     */
    public function struktur(PeriodeKepengurusan $periode): JsonResponse
    {
        return response()->json(['data' => $this->susun($periode)]);
    }

    /**
     * GET /api/periode/{periode}/riwayat (A) — perbandingan dengan periode
     * sebelumnya + log perubahan, terbaru di atas (KP-07.8).
     */
    public function riwayat(PeriodeKepengurusan $periode): JsonResponse
    {
        $sebelumnya = $periode->sebelumnya();

        $log = $periode->riwayat()
            ->with('admin') // KNF-09
            ->latest()
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'data' => [
                'periode' => new PeriodeResource($periode),
                'periode_sebelumnya' => $sebelumnya ? new PeriodeResource($sebelumnya) : null,
                'perbandingan' => $sebelumnya ? $this->bandingkan($sebelumnya, $periode) : null,
                'log' => RiwayatStrukturResource::collection($log),
            ],
        ]);
    }

    // ------------------------------------------------------------ Pembantu

    /**
     * Susunan satu periode: pengurus inti di atas, lalu per divisi (KP-01.4).
     *
     * @return array<string, mixed>
     */
    private function susun(PeriodeKepengurusan $periode): array
    {
        // Satu query untuk semua pengurus periode ini, diurutkan sesuai jabatan.urutan.
        $pengurus = $periode->kepengurusan()
            ->join('jabatan', 'jabatan.id', '=', 'kepengurusan.jabatan_id')
            ->with(['anggota.user', 'jabatan', 'divisi'])
            ->orderBy('jabatan.urutan')
            ->orderBy('kepengurusan.id')
            ->get(['kepengurusan.*']);

        $inti = $pengurus->whereNull('divisi_id')->values();
        $perDivisi = $pengurus->whereNotNull('divisi_id')->groupBy('divisi_id');

        $divisi = Divisi::orderBy('nama_divisi')->get()->map(fn (Divisi $item) => [
            'id' => $item->id,
            'nama_divisi' => $item->nama_divisi,
            'deskripsi' => $item->deskripsi,
            'pengurus' => PengurusPublikResource::collection($perDivisi->get($item->id, collect())),
        ]);

        return [
            'periode' => new PeriodeResource($periode),
            'inti' => PengurusPublikResource::collection($inti),
            'divisi' => $divisi,
        ];
    }

    /**
     * Siapa yang masuk, keluar, dan pindah jabatan/divisi dibanding periode
     * sebelumnya. Dihitung dari data kepengurusan, dicocokkan lewat anggota.
     *
     * @return array<string, array<int, array<string, string|null>>>
     */
    private function bandingkan(PeriodeKepengurusan $lama, PeriodeKepengurusan $baru): array
    {
        $muat = fn (PeriodeKepengurusan $periode): Collection => $periode->kepengurusan()
            ->with(['anggota.user', 'jabatan', 'divisi']) // KNF-09
            ->get()
            ->keyBy('anggota_id');

        $susunanLama = $muat($lama);
        $susunanBaru = $muat($baru);

        $masuk = $susunanBaru->diffKeys($susunanLama)
            ->map(fn (Kepengurusan $baris) => $this->butir($baris->ringkasan()));

        $keluar = $susunanLama->diffKeys($susunanBaru)
            ->map(fn (Kepengurusan $baris) => $this->butir($baris->ringkasan()));

        $pindah = $susunanBaru->intersectByKeys($susunanLama)
            ->filter(fn (Kepengurusan $baris, int $anggotaId) => $baris->jabatan_id !== $susunanLama[$anggotaId]->jabatan_id
                || $baris->divisi_id !== $susunanLama[$anggotaId]->divisi_id)
            ->map(fn (Kepengurusan $baris, int $anggotaId) => [
                'nama' => $baris->ringkasan()['nama'],
                'dari' => Kepengurusan::teksPosisi($susunanLama[$anggotaId]->ringkasan()),
                'menjadi' => Kepengurusan::teksPosisi($baris->ringkasan()),
            ]);

        $urutNama = fn (Collection $daftar) => $daftar->sortBy('nama', SORT_NATURAL | SORT_FLAG_CASE)->values()->all();

        return [
            'masuk' => $urutNama($masuk),
            'keluar' => $urutNama($keluar),
            'pindah' => $urutNama($pindah),
        ];
    }

    /**
     * @param  array{nama: string|null, jabatan: string|null, divisi: string|null}  $ringkasan
     * @return array<string, string|null>
     */
    private function butir(array $ringkasan): array
    {
        return ['nama' => $ringkasan['nama'], 'posisi' => Kepengurusan::teksPosisi($ringkasan)];
    }
}
