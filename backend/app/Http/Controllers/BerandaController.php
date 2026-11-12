<?php

namespace App\Http\Controllers;

use App\Http\Requests\Beranda\SimpanBerandaRequest;
use App\Http\Resources\PengaturanBerandaResource;
use App\Models\PengaturanBeranda;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Mews\Purifier\Facades\Purifier;

// Konten beranda
class BerandaController extends Controller
{
    // Konten beranda untuk pengunjung
    public function publik(): PengaturanBerandaResource
    {
        return new PengaturanBerandaResource(PengaturanBeranda::pengaturan() ?? new PengaturanBeranda());
    }

    // Konten beranda untuk formulir admin
    public function tampil(): PengaturanBerandaResource
    {
        return new PengaturanBerandaResource(PengaturanBeranda::pengaturan() ?? new PengaturanBeranda());
    }

    // Simpan cerita dan kontak organisasi
    public function simpan(SimpanBerandaRequest $request): JsonResponse
    {
        $data = $request->validated();
        $pengaturan = PengaturanBeranda::pengaturanAtauBuat();

        DB::transaction(function () use ($data, $pengaturan) {
            $pengaturan->update([
                'cerita_judul' => $data['cerita_judul'] ?? null,
                'cerita_isi' => isset($data['cerita_isi']) ? Purifier::clean($data['cerita_isi'], 'materi') : null,
                'visi' => $data['visi'] ?? null,
                'misi' => $data['misi'] ?? null,
                'kontak_alamat' => $data['kontak_alamat'] ?? null,
                'kontak_whatsapp' => $this->nomorWhatsapp($data['kontak_whatsapp'] ?? null),
                'kontak_email' => $data['kontak_email'] ?? null,
                'kontak_instagram' => isset($data['kontak_instagram']) ? ltrim($data['kontak_instagram'], '@') : null,
            ]);
        });

        return response()->json([
            'message' => 'Konten beranda disimpan.',
            'data' => new PengaturanBerandaResource($pengaturan->refresh()),
        ]);
    }

    // Rapikan nomor WhatsApp
    private function nomorWhatsapp(?string $nomor): ?string
    {
        $angka = preg_replace('/\D/', '', (string) $nomor);

        if ($angka === '') {
            return null;
        }

        return str_starts_with($angka, '0') ? '62'.substr($angka, 1) : $angka;
    }
}
