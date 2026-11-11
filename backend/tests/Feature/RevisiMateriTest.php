<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Materi;
use App\Models\RevisiMateri;
use App\Models\User;
use App\Models\VerifikasiMateri;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F7 — Revisi & Riwayat Versi (KF-12, KF-13).
 */
class RevisiMateriTest extends TestCase
{
    use RefreshDatabase;

    private function token(User $user): string
    {
        return $user->createToken('api')->plainTextToken;
    }

    /** Satu kategori contoh untuk muatan PUT /materi (KF-19, KP-19.2). */
    private function idKategori(string $nama = 'Fiqih'): int
    {
        return Kategori::firstOrCreate(['nama' => $nama], ['slug' => strtolower($nama)])->id;
    }

    /**
     * Materi yang sudah terbit dengan satu versi disetujui.
     *
     * @return array{0: Materi, 1: RevisiMateri}
     */
    private function materiTerbit(User $penulis, string $isi = '<p>Versi pertama.</p>'): array
    {
        $materi = Materi::factory()->terbit()->create([
            'penulis_id' => $penulis->id,
            'judul' => 'Judul Awal',
        ]);

        $v1 = RevisiMateri::factory()->disetujui()->create([
            'materi_id' => $materi->id,
            'nomor_versi' => 1,
            'konten_html' => $isi,
        ]);

        return [$materi, $v1];
    }

    public function test_bukan_penulis_tidak_dapat_membuat_revisi(): void
    {
        $penulis = User::factory()->create();
        [$materi] = $this->materiTerbit($penulis);

        // KP-12.1
        $this->withToken($this->token(User::factory()->create()))
            ->postJson("/api/materi/{$materi->id}/revisi")
            ->assertStatus(403);
    }

    public function test_revisi_baru_ditolak_saat_masih_ada_draf_atau_pengajuan(): void
    {
        $penulis = User::factory()->create();
        $materi = Materi::factory()->create(['penulis_id' => $penulis->id]);
        RevisiMateri::factory()->create(['materi_id' => $materi->id]); // draf

        // KP-12.2
        $this->withToken($this->token($penulis))
            ->postJson("/api/materi/{$materi->id}/revisi")
            ->assertStatus(422);

        RevisiMateri::where('materi_id', $materi->id)->update(['status' => RevisiMateri::STATUS_DIAJUKAN]);

        $this->withToken($this->token($penulis))
            ->postJson("/api/materi/{$materi->id}/revisi")
            ->assertStatus(422);
    }

    public function test_revisi_baru_menambah_nomor_versi_dan_menyalin_isi_terakhir(): void
    {
        $penulis = User::factory()->create();
        [$materi, $v1] = $this->materiTerbit($penulis, '<p>Isi versi satu.</p>');

        // KP-12.3
        $this->withToken($this->token($penulis))
            ->postJson("/api/materi/{$materi->id}/revisi")
            ->assertCreated()
            ->assertJsonPath('data.revisi.nomor_versi', 2)
            ->assertJsonPath('data.revisi.status', 'draf')
            ->assertJsonPath('data.revisi.konten_html', '<p>Isi versi satu.</p>');

        $v2 = RevisiMateri::where('materi_id', $materi->id)->where('nomor_versi', 2)->firstOrFail();
        $this->assertSame($v1->konten, $v2->konten);
    }

    public function test_revisi_materi_terbit_dapat_mengubah_judul_tanpa_mengubah_versi_publik(): void
    {
        $penulis = User::factory()->create();
        [$materi, $v1] = $this->materiTerbit($penulis, '<p>Isi versi satu.</p>');
        $token = $this->token($penulis);

        $this->withToken($token)->postJson("/api/materi/{$materi->id}/revisi")->assertCreated();

        // KP-12.4 (Draf 2.3): judul & ringkasan boleh diubah lewat revisi draf
        $this->withToken($token)->putJson("/api/materi/{$materi->id}", [
            'judul' => 'Judul Ingin Diubah',
            'ringkasan' => 'Ringkasan ingin diubah.',
            'kategori' => [$this->idKategori()],
            'konten' => ['ops' => [['insert' => "Isi versi dua.\n"]]],
            'konten_html' => '<p>Isi versi dua.</p>',
        ])->assertOk();

        // Data versi publik di tabel `materi` tidak berubah.
        $materi->refresh();
        $this->assertSame('Judul Awal', $materi->judul);

        // KP-16.4: anggota melihat revisi terakhir (v2 draf) lengkap dengan
        // judul barunya, sedangkan versi publik tetap v1.
        $this->withToken($token)->getJson("/api/materi/{$materi->slug}")
            ->assertOk()
            ->assertJsonPath('data.judul', 'Judul Ingin Diubah')
            ->assertJsonPath('data.ringkasan', 'Ringkasan ingin diubah.')
            ->assertJsonPath('data.revisi.nomor_versi', 2)
            ->assertJsonPath('data.revisi.konten_html', '<p>Isi versi dua.</p>')
            ->assertJsonPath('data.versi_publik.id', $v1->id)
            ->assertJsonPath('data.versi_publik.nomor_versi', 1);

        // KP-17.2: pengunjung tetap melihat judul dan isi versi publik.
        $this->getJson("/api/publik/materi/{$materi->slug}")
            ->assertOk()
            ->assertJsonPath('data.judul', 'Judul Awal')
            ->assertJsonPath('data.konten_html', '<p>Isi versi satu.</p>');
    }

    public function test_revisi_yang_sudah_disetujui_tidak_dapat_diubah(): void
    {
        $penulis = User::factory()->create();
        [$materi] = $this->materiTerbit($penulis);

        // KP-12.5
        $this->withToken($this->token($penulis))->putJson("/api/materi/{$materi->id}", [
            'judul' => 'Judul Awal',
            'kategori' => [$this->idKategori()],
            'konten' => ['ops' => [['insert' => "x\n"]]],
            'konten_html' => '<p>x</p>',
        ])->assertStatus(422);
    }

    public function test_riwayat_versi_menampilkan_versi_dan_keputusan_admin(): void
    {
        $penulis = User::factory()->create();
        $admin = User::factory()->admin()->create();
        [$materi, $v1] = $this->materiTerbit($penulis);

        $v2 = RevisiMateri::factory()->ditolak()->create([
            'materi_id' => $materi->id,
            'nomor_versi' => 2,
            'catatan_revisi' => 'Menambah contoh.',
        ]);

        VerifikasiMateri::factory()->create([
            'revisi_id' => $v1->id,
            'admin_id' => $admin->id,
        ]);
        VerifikasiMateri::factory()->ditolak()->create([
            'revisi_id' => $v2->id,
            'admin_id' => $admin->id,
        ]);

        // KP-13.1, KP-13.2
        $this->withToken($this->token($penulis))
            ->getJson("/api/materi/{$materi->id}/riwayat")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.nomor_versi', 2)
            ->assertJsonPath('data.0.status', 'ditolak')
            ->assertJsonPath('data.0.catatan_revisi', 'Menambah contoh.')
            ->assertJsonPath('data.0.verifikasi.0.keputusan', 'ditolak')
            ->assertJsonPath('data.0.verifikasi.0.admin.nama', $admin->nama)
            ->assertJsonPath('data.1.nomor_versi', 1)
            ->assertJsonPath('data.1.verifikasi.0.keputusan', 'disetujui');
    }

    public function test_anggota_lain_melihat_semua_versi_tanpa_keputusan_admin(): void
    {
        $penulis = User::factory()->create();
        $admin = User::factory()->admin()->create();
        [$materi, $v1] = $this->materiTerbit($penulis);

        VerifikasiMateri::factory()->create([
            'revisi_id' => $v1->id,
            'admin_id' => $admin->id,
        ]);
        RevisiMateri::factory()->ditolak()->create([
            'materi_id' => $materi->id,
            'nomor_versi' => 2,
        ]);

        // KP-13.3: semua anggota aktif melihat seluruh versi, tetapi keputusan
        // admin (dan catatannya) tidak dikirim ke anggota lain.
        $this->withToken($this->token(User::factory()->create()))
            ->getJson("/api/materi/{$materi->id}/riwayat")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.status', 'ditolak')
            ->assertJsonPath('data.1.status', 'disetujui')
            ->assertJsonMissingPath('data.0.verifikasi')
            ->assertJsonMissingPath('data.1.verifikasi');
    }

    public function test_isi_versi_lama_dapat_dibaca_kembali(): void
    {
        $penulis = User::factory()->create();
        [$materi, $v1] = $this->materiTerbit($penulis, '<p>Isi versi pertama.</p>');

        $v2 = RevisiMateri::factory()->disetujui()->create([
            'materi_id' => $materi->id,
            'nomor_versi' => 2,
            'konten_html' => '<p>Isi versi kedua.</p>',
        ]);

        // KP-13.4: isi versi lama tetap utuh dan dapat dibuka oleh anggota lain.
        // Kedua permintaan memakai token yang sama: RequestGuard mengingat
        // pengguna dari request pertama, jadi dua token berbeda dalam satu
        // metode tes tidak menghasilkan dua identitas yang berbeda.
        $token = $this->token(User::factory()->create());

        $this->withToken($token)
            ->getJson("/api/materi/{$materi->id}/riwayat/{$v1->id}")
            ->assertOk()
            ->assertJsonPath('data.nomor_versi', 1)
            ->assertJsonPath('data.konten_html', '<p>Isi versi pertama.</p>');

        // Versi terbaru tetap berisi isinya sendiri.
        $this->withToken($token)
            ->getJson("/api/materi/{$materi->id}/riwayat/{$v2->id}")
            ->assertOk()
            ->assertJsonPath('data.konten_html', '<p>Isi versi kedua.</p>');
    }

    public function test_penulis_melihat_keputusan_admin_pada_isi_versi(): void
    {
        $penulis = User::factory()->create();
        $admin = User::factory()->admin()->create();
        [$materi, $v1] = $this->materiTerbit($penulis);

        VerifikasiMateri::factory()->create([
            'revisi_id' => $v1->id,
            'admin_id' => $admin->id,
        ]);

        // KP-13.2: penulis melihat keputusan admin beserta catatannya.
        $this->withToken($this->token($penulis))
            ->getJson("/api/materi/{$materi->id}/riwayat/{$v1->id}")
            ->assertOk()
            ->assertJsonPath('data.verifikasi.0.keputusan', 'disetujui');
    }

    public function test_anggota_lain_melihat_isi_versi_tanpa_keputusan_admin(): void
    {
        $penulis = User::factory()->create();
        $admin = User::factory()->admin()->create();
        [$materi, $v1] = $this->materiTerbit($penulis);

        VerifikasiMateri::factory()->create([
            'revisi_id' => $v1->id,
            'admin_id' => $admin->id,
        ]);

        // KP-13.3: anggota lain boleh membaca isi versinya, tetapi keputusan
        // admin (dan catatannya) tidak dikirim.
        $this->withToken($this->token(User::factory()->create()))
            ->getJson("/api/materi/{$materi->id}/riwayat/{$v1->id}")
            ->assertOk()
            ->assertJsonPath('data.konten_html', '<p>Versi pertama.</p>')
            ->assertJsonMissingPath('data.verifikasi');
    }

    public function test_versi_milik_materi_lain_tidak_dapat_dibuka(): void
    {
        $materi = $this->materiTerbit(User::factory()->create())[0];
        $lain = RevisiMateri::factory()->create(); // menempel pada materi lain

        // KP-13.4: versi harus milik materi pada URL.
        $this->withToken($this->token(User::factory()->create()))
            ->getJson("/api/materi/{$materi->id}/riwayat/{$lain->id}")
            ->assertNotFound();
    }

    public function test_admin_dapat_membandingkan_revisi_dengan_versi_terbit(): void
    {
        $penulis = User::factory()->create();
        $admin = User::factory()->admin()->create();
        [$materi, $v1] = $this->materiTerbit($penulis, '<p>Isi lama.</p>');

        $v2 = RevisiMateri::factory()->diajukan()->create([
            'materi_id' => $materi->id,
            'nomor_versi' => 2,
            'konten_html' => '<p>Isi baru.</p>',
        ]);

        // KP-11.2
        $this->withToken($this->token($admin))
            ->getJson("/api/admin/revisi/{$v2->id}")
            ->assertOk()
            ->assertJsonPath('data.revisi.konten_html', '<p>Isi baru.</p>')
            ->assertJsonPath('data.versi_terbit.id', $v1->id)
            ->assertJsonPath('data.versi_terbit.konten_html', '<p>Isi lama.</p>');
    }

    public function test_materi_yang_ditolak_kembali_menjadi_draf_saat_revisi_baru_dibuat(): void
    {
        $penulis = User::factory()->create();
        $materi = Materi::factory()->create([
            'penulis_id' => $penulis->id,
            'status' => Materi::STATUS_DITOLAK,
        ]);
        RevisiMateri::factory()->ditolak()->create([
            'materi_id' => $materi->id,
            'nomor_versi' => 1,
        ]);

        // PRD §8.2
        $this->withToken($this->token($penulis))
            ->postJson("/api/materi/{$materi->id}/revisi")
            ->assertCreated()
            ->assertJsonPath('data.materi.status', 'draf')
            ->assertJsonPath('data.revisi.nomor_versi', 2);
    }
}
