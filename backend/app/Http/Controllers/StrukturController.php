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

// Struktur organisasi
class StrukturController extends Controller
{
    // Daftar divisi
    public function divisi(): AnonymousResourceCollection
    {
        return DivisiResource::collection(Divisi::orderBy('nama_divisi')->get());
    }

    // Struktur periode saat ini
    public function publik(): JsonResponse
    {
        $periode = PeriodeKepengurusan::aktif();

        return response()->json(['data' => $periode ? $this->susun($periode) : null]);
    }

    // Daftar periode
    public function periode(): AnonymousResourceCollection
    {
        return PeriodeResource::collection(
            PeriodeKepengurusan::orderByDesc('is_aktif')->orderByDesc('tahun_mulai')->orderByDesc('id')->get()
        );
    }

    // Struktur satu periode
    public function struktur(PeriodeKepengurusan $periode): JsonResponse
    {
        return response()->json(['data' => $this->susun($periode)]);
    }

    // Riwayat perubahan struktur
    public function riwayat(PeriodeKepengurusan $periode): JsonResponse
    {
        $sebelumnya = $periode->sebelumnya();

        $log = $periode->riwayat()
            ->with('admin')
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

    // Pembantu

    /**
     * Susunan satu periode
     *
     * @return array<string, mixed>
     */
    private function susun(PeriodeKepengurusan $periode): array
    {
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
     * Bandingkan dengan periode sebelumnya
     *
     * @return array<string, array<int, array<string, string|null>>>
     */
    private function bandingkan(PeriodeKepengurusan $lama, PeriodeKepengurusan $baru): array
    {
        $muat = fn (PeriodeKepengurusan $periode): Collection => $periode->kepengurusan()
            ->with(['anggota.user', 'jabatan', 'divisi'])
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
