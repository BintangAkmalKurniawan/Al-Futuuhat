<?php

namespace App\Http\Controllers;

use App\Http\Resources\DokumentasiProgramResource;
use App\Http\Resources\ProgramDetailResource;
use App\Http\Resources\ProgramResource;
use App\Models\DokumentasiProgram;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Program untuk pengunjung dan anggota — hanya baca, tanpa login
 * (KF-21, KP-21.5, KP-21.6).
 */
class ProgramPublikController extends Controller
{
    /**
     * GET /api/publik/program — semua program, yang aktif lebih dulu.
     * Tidak berpaginasi karena jumlah program organisasi sedikit.
     */
    public function index(): AnonymousResourceCollection
    {
        return ProgramResource::collection(
            Program::with('pic.user') // KNF-09
                ->denganJumlahDokumentasi()
                ->urutanTampil()
                ->get()
        );
    }

    /**
     * GET /api/publik/program/{slug} — penjelasan rinci satu program.
     */
    public function show(Program $program): ProgramDetailResource
    {
        // Dimuat ulang lewat query agar jumlah dokumentasi ikut dihitung.
        $program = Program::with('pic.user')
            ->denganJumlahDokumentasi()
            ->findOrFail($program->id);

        return new ProgramDetailResource($program);
    }

    /**
     * GET /api/publik/program/{slug}/dokumentasi?jenis=foto|video&page=
     * Terbaru di atas. Penyaring `video` mencakup berkas MP4 dan tautan YouTube.
     */
    public function dokumentasi(Request $request, Program $program): AnonymousResourceCollection
    {
        $jenis = $request->query('jenis');

        $dokumentasi = $program->dokumentasi()
            ->when($jenis === 'foto', fn ($query) => $query->where('jenis', DokumentasiProgram::JENIS_FOTO))
            ->when($jenis === 'video', fn ($query) => $query->whereIn('jenis', [
                DokumentasiProgram::JENIS_VIDEO,
                DokumentasiProgram::JENIS_YOUTUBE,
            ]))
            ->latest()
            ->orderByDesc('id') // urutan tetap bila waktu unggahnya sama
            ->paginate(12)
            ->withQueryString();

        return DokumentasiProgramResource::collection($dokumentasi);
    }
}
