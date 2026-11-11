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

/**
 * Pengelolaan program oleh admin (KF-21, KP-21.1, KP-21.2, KP-21.4).
 */
class ProgramController extends Controller
{
    /**
     * GET /api/admin/program — semua program + jumlah dokumentasi.
     */
    public function index(): AnonymousResourceCollection
    {
        return ProgramAdminResource::collection(
            Program::with('pic.user') // KNF-09: nama PIC dimuat sekaligus
                ->denganJumlahDokumentasi()
                ->urutanTampil()
                ->get()
        );
    }

    /**
     * GET /api/admin/program/{program} — isi awal formulir ubah.
     */
    public function show(Program $program): ProgramAdminResource
    {
        $program = Program::with('pic.user')
            ->denganJumlahDokumentasi()
            ->findOrFail($program->id);

        return new ProgramAdminResource($program);
    }

    /**
     * POST /api/admin/program — multipart karena dapat memuat sampul.
     */
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
                // Sanitasi seperti isi materi: penjelasan ditulis dengan editor Quill.
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

    /**
     * POST /api/admin/program/{program} — ubah program.
     * Memakai POST, bukan PUT, karena PHP tidak membaca body multipart pada
     * permintaan PUT (sama seperti formulir konten beranda).
     */
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
                // Slug hanya dibentuk ulang bila nama berubah, agar tautan lama tetap berlaku.
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

        // Sampul lama dihapus setelah datanya tersimpan (berkas lama dibuang setelah data baru aman).
        if ($sampulLama !== null && $sampulLama !== $program->sampul) {
            Storage::disk('public')->delete($sampulLama);
        }

        return response()->json([
            'message' => 'Program diperbarui.',
            'data' => new ProgramAdminResource($program->refresh()->load('pic.user')),
        ]);
    }

    /**
     * DELETE /api/admin/program/{program} — hapus program beserta seluruh
     * dokumentasi dan berkasnya (KP-21.4).
     */
    public function destroy(Program $program): JsonResponse
    {
        $berkas = $program->semuaBerkas();

        // Baris PIC dan dokumentasi ikut terhapus lewat cascade di basis data.
        $program->delete();

        Storage::disk('public')->delete($berkas);

        return response()->json(['message' => 'Program dihapus.']);
    }
}
