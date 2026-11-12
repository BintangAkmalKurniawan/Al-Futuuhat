<?php

namespace App\Http\Controllers;

use App\Http\Resources\DokumentasiProgramResource;
use App\Http\Resources\ProgramDetailResource;
use App\Http\Resources\ProgramResource;
use App\Models\DokumentasiProgram;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

// Program untuk publik
class ProgramPublikController extends Controller
{
    // Daftar program
    public function index(): AnonymousResourceCollection
    {
        return ProgramResource::collection(
            Program::with('pic.user')
                ->denganJumlahDokumentasi()
                ->urutanTampil()
                ->get()
        );
    }

    // Detail program
    public function show(Program $program): ProgramDetailResource
    {
        $program = Program::with('pic.user')
            ->denganJumlahDokumentasi()
            ->findOrFail($program->id);

        return new ProgramDetailResource($program);
    }

    // Dokumentasi program
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
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        return DokumentasiProgramResource::collection($dokumentasi);
    }
}
