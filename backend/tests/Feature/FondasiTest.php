<?php

namespace Tests\Feature;

use App\Models\Divisi;
use App\Models\Jabatan;
use App\Models\Kepengurusan;
use App\Models\Komentar;
use App\Models\Materi;
use App\Models\RevisiMateri;
use App\Models\User;
use App\Models\VerifikasiMateri;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * F0 — Fondasi.
 * Memastikan skema PRD §10, relasi model, seeder, dan indeks pencarian
 * sudah benar sebelum fitur F1 dan seterusnya dikerjakan.
 */
class FondasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_membuat_admin_enam_divisi_dan_jabatan_awal(): void
    {
        $this->seed();

        $admin = User::where('email', env('ADMIN_EMAIL', 'admin@alfutuuhat.test'))->first();

        $this->assertNotNull($admin, 'Akun admin dari .env tidak terbentuk.');
        $this->assertSame(User::ROLE_ADMIN, $admin->role);
        $this->assertSame(User::STATUS_AKTIF, $admin->status);
        $this->assertNotNull($admin->anggota()->first(), 'Admin harus juga punya baris anggota.');

        $this->assertSame(6, Divisi::count());
        $this->assertDatabaseHas('divisi', ['nama_divisi' => 'Edukasi']);
        $this->assertDatabaseHas('jabatan', ['nama_jabatan' => 'Ketua', 'urutan' => 1]);
        $this->assertDatabaseHas('jabatan', ['nama_jabatan' => 'Koordinator', 'urutan' => 2]);
    }

    public function test_registrasi_gagal_karena_email_duplikat_terjaga_basis_data(): void
    {
        $user = User::factory()->create(['email' => 'sama@alfutuuhat.test']);

        $this->expectException(QueryException::class);
        User::factory()->create(['email' => $user->email]);
    }

    public function test_relasi_materi_revisi_verifikasi_dan_komentar(): void
    {
        $materi = Materi::factory()->terbit()->create();
        $v1 = RevisiMateri::factory()->ditolak()->create(['materi_id' => $materi->id, 'nomor_versi' => 1]);
        $v2 = RevisiMateri::factory()->disetujui()->create(['materi_id' => $materi->id, 'nomor_versi' => 2]);

        VerifikasiMateri::factory()->ditolak()->create(['revisi_id' => $v1->id]);
        VerifikasiMateri::factory()->create(['revisi_id' => $v2->id]);

        // Kolom JSONB `konten` otomatis dibaca sebagai array (cast 'array')
        $this->assertIsArray($v2->refresh()->konten);

        // Selalu pakai eager loading supaya tidak memicu galat lazy loading (KNF-09)
        $materi = Materi::with(['penulis', 'revisiTerakhir', 'versiTerbit'])->find($materi->id);

        $this->assertSame(2, $materi->revisiTerakhir->nomor_versi, 'revisiTerakhir harus versi dengan nomor terbesar.');
        $this->assertSame($v2->id, $materi->versiTerbit->id, 'versiTerbit harus revisi disetujui dengan nomor terbesar.');
        $this->assertSame($materi->penulis_id, $materi->penulis->id, 'Relasi penulis harus memakai kolom penulis_id.');

        // Riwayat keputusan admin terbaca dari revisi (KP-13.2)
        $revisi = RevisiMateri::with('verifikasi.admin')->find($v1->id);
        $this->assertCount(1, $revisi->verifikasi);
        $this->assertSame(VerifikasiMateri::KEPUTUSAN_DITOLAK, $revisi->verifikasi->first()->keputusan);
        $this->assertNotNull($revisi->verifikasi->first()->admin->id);

        // Forum: balasan satu tingkat tersimpan lewat parent_id (KP-14.3)
        $induk = Komentar::factory()->create(['materi_id' => $materi->id]);
        Komentar::factory()->balasan($induk)->create(['user_id' => $induk->user_id]);

        $induk = Komentar::with('balasan')->find($induk->id);
        $this->assertCount(1, $induk->balasan);
        $this->assertSame($induk->id, $induk->balasan->first()->parent_id);
    }

    public function test_menghapus_materi_ikut_menghapus_revisi_dan_komentar(): void
    {
        $materi = Materi::factory()->create();
        RevisiMateri::factory()->create(['materi_id' => $materi->id, 'nomor_versi' => 1]);
        Komentar::factory()->create(['materi_id' => $materi->id]);

        $materi->delete();

        $this->assertDatabaseCount('revisi_materi', 0);
        $this->assertDatabaseCount('komentar', 0);
    }

    public function test_satu_anggota_tidak_boleh_dua_kali_dalam_periode_yang_sama(): void
    {
        $kepengurusan = Kepengurusan::factory()->create();

        $this->expectException(QueryException::class);
        Kepengurusan::factory()->create([
            'anggota_id' => $kepengurusan->anggota_id,
            'periode_id' => $kepengurusan->periode_id,
        ]);
    }

    public function test_data_yang_masih_dipakai_tidak_bisa_dihapus_langsung(): void
    {
        $kepengurusan = Kepengurusan::factory()->create();

        // RESTRICT: divisi yang masih dipakai di kepengurusan ditolak basis data (KP-07.5)
        $this->expectException(QueryException::class);
        DB::table('divisi')->where('id', $kepengurusan->divisi_id)->delete();
    }

    public function test_indeks_gin_full_text_tersedia(): void
    {
        $indeksMateri = DB::table('pg_indexes')->where('tablename', 'materi')->pluck('indexname');
        $indeksRevisi = DB::table('pg_indexes')->where('tablename', 'revisi_materi')->pluck('indexname');

        $this->assertTrue($indeksMateri->contains('materi_judul_fts_index'), 'Indeks full-text judul materi belum ada.');
        $this->assertTrue($indeksRevisi->contains('revisi_materi_konten_fts_index'), 'Indeks full-text isi materi belum ada.');
    }

    public function test_pencarian_full_text_memakai_ekspresi_indeks_yang_benar(): void
    {
        $materi = Materi::factory()->create(['judul' => 'Adab Menuntut Ilmu']);
        RevisiMateri::factory()->disetujui()->create([
            'materi_id' => $materi->id,
            'nomor_versi' => 1,
            'konten_html' => '<p>Pembahasan tentang kesabaran dalam belajar.</p>',
        ]);

        // Kata yang ada di judul (indeks GIN materi.judul)
        $this->assertSame(1, Materi::whereRaw(
            "to_tsvector('simple', judul) @@ plainto_tsquery('simple', ?)",
            ['menuntut']
        )->count());

        // Kata yang hanya ada di isi versi terbit (indeks GIN revisi_materi.konten_html)
        $this->assertSame(1, RevisiMateri::whereRaw(
            "to_tsvector('simple', regexp_replace(konten_html, '<[^>]+>', ' ', 'g')) @@ plainto_tsquery('simple', ?)",
            ['kesabaran']
        )->count());
    }
}
