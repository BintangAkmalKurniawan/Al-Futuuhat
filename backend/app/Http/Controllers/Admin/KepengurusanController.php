<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\KepengurusanRequest;
use App\Http\Resources\KepengurusanResource;
use App\Http\Resources\PeriodeResource;
use App\Models\Kepengurusan;
use App\Models\PeriodeKepengurusan;
use App\Models\RiwayatStruktur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Menyusun pengurus periode saat ini (KF-07).
 *
 * Draf 2.5: admin tidak memilih periode. Setiap perubahan dicatat pada periode
 * saat ini — dibuat otomatis bila tahun sudah berganti (KP-07.1) — periode lama
 * hanya dapat dibaca (KP-07.6), dan setiap perubahan masuk riwayat (KP-07.7).
 */
class KepengurusanController extends Controller
{
    /**
     * GET /api/admin/kepengurusan — susunan pengurus periode aktif untuk tab Pengurus.
     */
    public function index(): AnonymousResourceCollection
    {
        $periode = PeriodeKepengurusan::aktif();

        $kepengurusan = Kepengurusan::query()
            ->with(['anggota.user', 'divisi', 'jabatan']) // KNF-09: eager loading
            ->where('kepengurusan.periode_id', $periode?->id)
            // Pengurus inti (tanpa divisi) lebih dulu, lalu sesuai urutan jabatan.
            ->join('jabatan', 'jabatan.id', '=', 'kepengurusan.jabatan_id')
            ->orderByRaw('kepengurusan.divisi_id NULLS FIRST')
            ->orderBy('jabatan.urutan')
            ->orderBy('kepengurusan.id')
            ->get(['kepengurusan.*']);

        return KepengurusanResource::collection($kepengurusan)->additional([
            // Kepala tab Pengurus: "Periode saat ini: 2026/2027" (null bila belum ada).
            'periode' => $periode ? new PeriodeResource($periode) : null,
        ]);
    }

    /**
     * POST /api/admin/kepengurusan.
     */
    public function store(KepengurusanRequest $request): JsonResponse
    {
        $data = $request->validated();

        $kepengurusan = DB::transaction(function () use ($request, $data) {
            $periode = PeriodeKepengurusan::saatIni($request->user());

            $this->pastikanBelumMenjabat($periode, $data['anggota_id']);

            $kepengurusan = $periode->kepengurusan()->create($data);
            $sesudah = $kepengurusan->ringkasan();

            $this->catat($periode, $request, RiwayatStruktur::AKSI_TAMBAH,
                "Menambahkan {$sesudah['nama']} sebagai ".Kepengurusan::teksPosisi($sesudah).'.',
                null, $sesudah);

            return $kepengurusan;
        });

        return response()->json([
            'message' => 'Pengurus ditambahkan.',
            'data' => new KepengurusanResource($kepengurusan->load('anggota.user', 'divisi', 'jabatan')),
        ], 201);
    }

    /**
     * PUT /api/admin/kepengurusan/{kepengurusan}.
     */
    public function update(KepengurusanRequest $request, Kepengurusan $kepengurusan): JsonResponse
    {
        $this->tolakPeriodeLama($kepengurusan);
        $data = $request->validated();

        $baris = DB::transaction(function () use ($request, $data, $kepengurusan) {
            $periode = PeriodeKepengurusan::saatIni($request->user());
            $baris = $this->barisDiPeriode($kepengurusan, $periode);

            $this->pastikanBelumMenjabat($periode, $data['anggota_id'], $baris->id);

            $sebelum = $baris->ringkasan();
            $baris->update($data);
            $sesudah = $baris->unsetRelations()->ringkasan();

            // Ubah tanpa perbedaan apa pun tidak dicatat.
            if ($sebelum !== $sesudah) {
                $keterangan = $baris->wasChanged('anggota_id')
                    ? "Mengganti {$sebelum['nama']} (".Kepengurusan::teksPosisi($sebelum).") dengan {$sesudah['nama']} (".Kepengurusan::teksPosisi($sesudah).').'
                    : "Mengubah jabatan {$sesudah['nama']} dari ".Kepengurusan::teksPosisi($sebelum).' menjadi '.Kepengurusan::teksPosisi($sesudah).'.';

                $this->catat($periode, $request, RiwayatStruktur::AKSI_UBAH, $keterangan, $sebelum, $sesudah);
            }

            return $baris;
        });

        return response()->json([
            'message' => 'Susunan pengurus diperbarui.',
            'data' => new KepengurusanResource($baris->load('anggota.user', 'divisi', 'jabatan')),
        ]);
    }

    /**
     * DELETE /api/admin/kepengurusan/{kepengurusan}.
     */
    public function destroy(Request $request, Kepengurusan $kepengurusan): JsonResponse
    {
        $this->tolakPeriodeLama($kepengurusan);

        DB::transaction(function () use ($request, $kepengurusan) {
            $periode = PeriodeKepengurusan::saatIni($request->user());
            $baris = $this->barisDiPeriode($kepengurusan, $periode);
            $sebelum = $baris->ringkasan();

            $baris->delete();

            $this->catat($periode, $request, RiwayatStruktur::AKSI_HAPUS,
                "Menghapus {$sebelum['nama']} (".Kepengurusan::teksPosisi($sebelum).') dari susunan pengurus.',
                $sebelum, null);
        });

        return response()->json(['message' => 'Pengurus dihapus dari susunan.']);
    }

    // ------------------------------------------------------------ Pembantu

    /** KP-07.6: baris milik periode yang sudah tidak aktif tidak boleh diubah. */
    private function tolakPeriodeLama(Kepengurusan $kepengurusan): void
    {
        if (! $kepengurusan->periode()->value('is_aktif')) {
            throw ValidationException::withMessages([
                'periode' => 'Susunan pengurus periode lama tidak dapat diubah.',
            ]);
        }
    }

    /**
     * Bila saatIni() baru saja memulai periode tahun berjalan, baris yang diminta
     * masih milik periode lama (kini arsip). Perubahan diterapkan pada salinannya
     * di periode baru — dicari lewat anggota yang sama.
     */
    private function barisDiPeriode(Kepengurusan $kepengurusan, PeriodeKepengurusan $periode): Kepengurusan
    {
        if ($kepengurusan->periode_id === $periode->id) {
            return $kepengurusan;
        }

        return $periode->kepengurusan()->where('anggota_id', $kepengurusan->anggota_id)->firstOrFail();
    }

    /** KP-07.4: satu anggota hanya sekali dalam satu periode. */
    private function pastikanBelumMenjabat(PeriodeKepengurusan $periode, int $anggotaId, ?int $abaikanId = null): void
    {
        $sudah = $periode->kepengurusan()
            ->where('anggota_id', $anggotaId)
            ->when($abaikanId !== null, fn ($query) => $query->where('id', '!=', $abaikanId))
            ->exists();

        if ($sudah) {
            throw ValidationException::withMessages([
                'anggota_id' => 'Anggota ini sudah memiliki jabatan pada periode ini.',
            ]);
        }
    }

    /**
     * @param  array<string, string|null>|null  $sebelum
     * @param  array<string, string|null>|null  $sesudah
     */
    private function catat(PeriodeKepengurusan $periode, Request $request, string $aksi, string $keterangan, ?array $sebelum, ?array $sesudah): void
    {
        $periode->riwayat()->create([
            'admin_id' => $request->user()->id,
            'aksi' => $aksi,
            'keterangan' => $keterangan,
            'data_sebelum' => $sebelum,
            'data_sesudah' => $sesudah,
        ]);
    }
}
