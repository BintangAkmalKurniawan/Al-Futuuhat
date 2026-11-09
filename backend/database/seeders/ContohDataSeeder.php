<?php

namespace Database\Seeders;

use App\Models\Anggota;
use App\Models\Divisi;
use App\Models\Jabatan;
use App\Models\Kepengurusan;
use App\Models\Komentar;
use App\Models\Materi;
use App\Models\PeriodeKepengurusan;
use App\Models\RevisiMateri;
use App\Models\User;
use App\Models\VerifikasiMateri;
use Illuminate\Database\Seeder;

/**
 * Data contoh untuk demo dan pengujian manual — hanya di lingkungan `local`,
 * karena image Docker dibangun tanpa paket dev (factory tidak tersedia) — PRD §13.2.
 */
class ContohDataSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $admin = User::where('role', User::ROLE_ADMIN)->first();

        $anggota = $this->buatAnggota();
        $this->buatPendaftar();
        $this->buatPeriode($anggota);
        $this->buatMateri($anggota, $admin);
    }

    /**
     * Delapan anggota aktif beserta data keanggotaannya.
     *
     * @return \Illuminate\Support\Collection<int, Anggota>
     */
    private function buatAnggota()
    {
        return User::factory()
            ->count(8)
            ->create()
            ->map(fn (User $user) => Anggota::factory()->create(['user_id' => $user->id]));
    }

    /** Dua pendaftar menunggu verifikasi dan satu yang sudah ditolak (bahan uji F2). */
    private function buatPendaftar(): void
    {
        User::factory()->menunggu()->count(2)->create()->each(
            fn (User $user) => Anggota::factory()->menunggu()->create(['user_id' => $user->id])
        );

        $ditolak = User::factory()->ditolak()->create();
        Anggota::factory()->menunggu()->create([
            'user_id' => $ditolak->id,
            'catatan_verifikasi' => 'Alamat belum lengkap. Silakan hubungi pengurus untuk melengkapi data.',
            'diverifikasi_pada' => now(),
        ]);
    }

    /**
     * Dua periode (satu aktif) dan susunan pengurus periode aktif:
     * Ketua tanpa divisi, lalu satu Koordinator per divisi.
     *
     * @param  \Illuminate\Support\Collection<int, Anggota>  $anggota
     */
    private function buatPeriode($anggota): PeriodeKepengurusan
    {
        $periodeAktif = PeriodeKepengurusan::firstOrCreate(
            ['nama_periode' => '2026/2027'],
            ['tahun_mulai' => 2026, 'tahun_selesai' => 2027, 'is_aktif' => true]
        );

        $periodeLama = PeriodeKepengurusan::firstOrCreate(
            ['nama_periode' => '2024/2025'],
            ['tahun_mulai' => 2024, 'tahun_selesai' => 2025, 'is_aktif' => false]
        );

        $ketua = Jabatan::where('nama_jabatan', 'Ketua')->first();
        $koordinator = Jabatan::where('nama_jabatan', 'Koordinator')->first();

        // Ketua: pengurus inti, tanpa divisi (KP-07.3)
        Kepengurusan::firstOrCreate([
            'anggota_id' => $anggota[0]->id,
            'periode_id' => $periodeAktif->id,
        ], [
            'divisi_id' => null,
            'jabatan_id' => $ketua->id,
        ]);

        // Satu koordinator di tiap divisi, memakai anggota berikutnya
        foreach (Divisi::orderBy('id')->get() as $index => $divisi) {
            $orang = $anggota[$index + 1] ?? null;

            if ($orang === null) {
                break; // anggota contoh habis
            }

            Kepengurusan::firstOrCreate([
                'anggota_id' => $orang->id,
                'periode_id' => $periodeAktif->id,
            ], [
                'divisi_id' => $divisi->id,
                'jabatan_id' => $koordinator->id,
            ]);
        }

        // Satu contoh pengurus pada periode lama, agar tombol ganti periode berguna (KP-01.3)
        Kepengurusan::firstOrCreate([
            'anggota_id' => $anggota[0]->id,
            'periode_id' => $periodeLama->id,
        ], [
            'divisi_id' => null,
            'jabatan_id' => $ketua->id,
        ]);

        return $periodeAktif;
    }

    /**
     * Materi contoh yang mewakili setiap status (PRD §8.2).
     *
     * @param  \Illuminate\Support\Collection<int, Anggota>  $anggota
     */
    private function buatMateri($anggota, ?User $admin): void
    {
        if (Materi::exists()) {
            return; // sudah pernah diisi, jangan diduplikasi
        }

        // 1. Materi terbit lengkap dengan verifikasi dan komentar forum
        $terbit = Materi::factory()->terbit()->create([
            'penulis_id' => $anggota[0]->user_id,
            'judul' => 'Adab Menuntut Ilmu',
            'slug' => 'adab-menuntut-ilmu',
            'ringkasan' => 'Pembahasan singkat tentang adab yang perlu dijaga saat menuntut ilmu.',
        ]);

        $revisiTerbit = RevisiMateri::factory()->disetujui()->create([
            'materi_id' => $terbit->id,
            'nomor_versi' => 1,
            'catatan_revisi' => 'Versi awal.',
        ]);

        if ($admin) {
            VerifikasiMateri::factory()->create([
                'revisi_id' => $revisiTerbit->id,
                'admin_id' => $admin->id,
                'catatan' => 'Sudah layak diterbitkan.',
            ]);
        }

        $komentar = Komentar::factory()->create([
            'materi_id' => $terbit->id,
            'user_id' => $anggota[1]->user_id,
            'isi' => 'Materi yang bermanfaat, jazakumullahu khairan.',
        ]);

        Komentar::factory()->balasan($komentar)->create([
            'user_id' => $anggota[2]->user_id,
            'isi' => 'Setuju, semoga ilmunya berkah.',
        ]);

        // 2. Materi terbit dengan riwayat: v1 ditolak, v2 disetujui (bahan uji riwayat versi KF-13)
        $denganRiwayat = Materi::factory()->terbit()->create([
            'penulis_id' => $anggota[1]->user_id,
            'judul' => 'Panduan Mengajar Anak Usia Dini',
            'slug' => 'panduan-mengajar-anak-usia-dini',
            'ringkasan' => 'Langkah praktis menyusun materi ajar untuk anak usia dini.',
        ]);

        $v1 = RevisiMateri::factory()->ditolak()->create([
            'materi_id' => $denganRiwayat->id,
            'nomor_versi' => 1,
            'catatan_revisi' => 'Draf awal.',
        ]);

        if ($admin) {
            VerifikasiMateri::factory()->ditolak()->create([
                'revisi_id' => $v1->id,
                'admin_id' => $admin->id,
            ]);
        }

        $v2 = RevisiMateri::factory()->disetujui()->create([
            'materi_id' => $denganRiwayat->id,
            'nomor_versi' => 2,
            'catatan_revisi' => 'Menambahkan bagian penilaian.',
        ]);

        if ($admin) {
            VerifikasiMateri::factory()->create([
                'revisi_id' => $v2->id,
                'admin_id' => $admin->id,
                'catatan' => 'Perbaikan sudah sesuai catatan.',
            ]);
        }

        // 3. Materi draf (bahan uji F5)
        $draf = Materi::factory()->create([
            'penulis_id' => $anggota[2]->user_id,
            'judul' => 'Ringkasan Kajian Pekanan',
            'slug' => 'ringkasan-kajian-pekanan',
            'ringkasan' => 'Catatan ringkas kajian pekanan divisi Edukasi.',
        ]);

        RevisiMateri::factory()->create([
            'materi_id' => $draf->id,
            'nomor_versi' => 1,
        ]);

        // 4. Materi yang sedang menunggu verifikasi admin (bahan uji F6)
        $diajukan = Materi::factory()->diajukan()->create([
            'penulis_id' => $anggota[3]->user_id,
            'judul' => 'Manajemen Waktu Pengurus',
            'slug' => 'manajemen-waktu-pengurus',
            'ringkasan' => 'Cara membagi waktu antara kegiatan organisasi dan kuliah.',
        ]);

        RevisiMateri::factory()->diajukan()->create([
            'materi_id' => $diajukan->id,
            'nomor_versi' => 1,
            'catatan_revisi' => 'Mohon diperiksa bagian penutup.',
        ]);
    }
}
