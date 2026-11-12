<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Program\SimpanProgramRequest;
use App\Http\Resources\ProgramAdminResource;
use App\Models\Program;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mews\Purifier\Facades\Purifier;

// Kelola program
class ProgramController extends Controller
{
    // Daftar program
    public function index(): AnonymousResourceCollection
    {
        return ProgramAdminResource::collection(
            Program::with('pic.user')
                ->denganJumlahDokumentasi()
                ->urutanTampil()
                ->get()
        );
    }

    // Detail program
    public function show(Program $program): ProgramAdminResource
    {
        $program = Program::with('pic.user')
            ->denganJumlahDokumentasi()
            ->findOrFail($program->id);

        return new ProgramAdminResource($program);
    }

    // Simpan program baru
    public function store(SimpanProgramRequest $request): JsonResponse
    {
        $data = $request->validated();

        $program = DB::transaction(function () use ($request, $data) {
            $program = Program::create([
                'nama' => $data['nama'],
                'slug' => Program::slugUnik($data['nama']),
                'ringkasan' => $data['ringkasan'] ?? null,
                'jadwal' => $data['jadwal'] ?? null,
                'sampul' => $request->hasFile('sampul')
                    ? $request->file('sampul')->store('program/sampul', 'public')
                    : null,
                'deskripsi' => Purifier::clean($data['deskripsi'], 'materi'),
                'status' => $data['status'],
            ]);

            $program->pic()->sync($data['pic']);

            return $program;
        });

        return response()->json([
            'message' => 'Program ditambahkan.',
            'data' => new ProgramAdminResource($program->load('pic.user')),
        ], 201);
    }

    // Ubah program
    public function update(SimpanProgramRequest $request, Program $program): JsonResponse
    {
        $data = $request->validated();
        $sampulLama = $program->sampul;

        DB::transaction(function () use ($request, $data, $program) {
            $sampul = $program->sampul;

            if ($request->hasFile('sampul')) {
                $sampul = $request->file('sampul')->store('program/sampul', 'public');
            } elseif ($request->boolean('hapus_sampul')) {
                $sampul = null;
            }

            $program->update([
                'nama' => $data['nama'],
                'slug' => $data['nama'] === $program->nama
                    ? $program->slug
                    : Program::slugUnik($data['nama'], $program->id),
                'ringkasan' => $data['ringkasan'] ?? null,
                'jadwal' => $data['jadwal'] ?? null,
                'sampul' => $sampul,
                'deskripsi' => Purifier::clean($data['deskripsi'], 'materi'),
                'status' => $data['status'],
            ]);

            $program->pic()->sync($data['pic']);
        });

        if ($sampulLama !== null && $sampulLama !== $program->sampul) {
            Storage::disk('public')->delete($sampulLama);
        }

        return response()->json([
            'message' => 'Program diperbarui.',
            'data' => new ProgramAdminResource($program->refresh()->load('pic.user')),
        ]);
    }

    // Hapus program
    public function destroy(Program $program): JsonResponse
    {
        $berkas = $program->semuaBerkas();

        $program->delete();

        Storage::disk('public')->delete($berkas);

        return response()->json(['message' => 'Program dihapus.']);
    }
}
