<?php

namespace Tests\Feature;

use App\Models\Komentar;
use App\Models\Materi;
use App\Models\RevisiMateri;
use App\Models\User;
use App\Models\VerifikasiMateri;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Arsip & baca materi anggota
class PencarianMateriTest extends TestCase
{
    use RefreshDatabase;

    private function token(User $user): string
    {
        return $user->createToken('api')->plainTextToken;
    }

    private function materiTerbit(User $penulis, array $data = [], string $isi = '<p>Isi materi.</p>'): RevisiMateri
    {
        $materi = Materi::factory()->terbit()->create(array_merge([
            'penulis_id' => $penulis->id,
        ], $data));

        return RevisiMateri::factory()->disetujui()->create([
            'materi_id' => $materi->id,
            'nomor_versi' => 1,
            'konten_html' => $isi,
        ]);
    }

    // KP-16.1
    public function test_pencarian_judul_tidak_membedakan_huruf_besar_kecil(): void
    {
        $penulis = User::factory()->create();
        $this->materiTerbit($penulis, ['judul' => 'Panduan Sholat Berjamaah']);
        $this->materiTerbit($penulis, ['judul' => 'Adab Makan']);

        $this->withToken($this->token($penulis))
            ->getJson('/api/materi?q=SHOLAT')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Panduan Sholat Berjamaah')
            ->assertJsonPath('meta.total', 1);
    }

    // KP-16.1
    public function test_pencarian_isi_revisi_terakhir(): void
    {
        $penulis = User::factory()->create();
        $this->materiTerbit($penulis, ['judul' => 'Bab Thaharah'], '<p>Rukun wudhu ada enam.</p>');
        $this->materiTerbit($penulis, ['judul' => 'Bab Puasa'], '<p>Puasa wajib bagi mukallaf.</p>');

        $this->withToken($this->token($penulis))
            ->getJson('/api/materi?q=wudhu')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Bab Thaharah');
    }

    // KP-16.1, KP-17.5
    public function test_isi_revisi_belum_disetujui_tercari_untuk_anggota_tetapi_tidak_untuk_pengunjung(): void
    {
        $penulis = User::factory()->create();

        $revisi = $this->materiTerbit($penulis, ['judul' => 'Bab Manasik'], '<p>Isi lama.</p>');
        RevisiMateri::factory()->create([
            'materi_id' => $revisi->materi_id,
            'nomor_versi' => 2,
            'konten_html' => '<p>Pembahasan istinja lengkap.</p>',
        ]);

        $this->getJson('/api/publik/materi?q=istinja')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->withToken($this->token($penulis))
            ->getJson('/api/materi?q=istinja')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Bab Manasik');
    }

    // KP-16.1
    public function test_anggota_dapat_mencari_isi_materi_draf(): void
    {
        $penulis = User::factory()->create();

        $draf = Materi::factory()->create(['penulis_id' => $penulis->id, 'judul' => 'Catatan Fiqih']);
        RevisiMateri::factory()->create([
            'materi_id' => $draf->id,
            'nomor_versi' => 1,
            'konten_html' => '<p>Pembahasan istinja.</p>',
        ]);

        $this->withToken($this->token($penulis))
            ->getJson('/api/materi?q=istinja')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Catatan Fiqih');
    }

    // KP-16.1
    public function test_filter_penulis_dan_kata_kunci_dapat_digabung(): void
    {
        $ahmad = User::factory()->create(['nama' => 'Ahmad Fauzi']);
        $budi = User::factory()->create(['nama' => 'Budi Santoso']);

        $this->materiTerbit($ahmad, ['judul' => 'Fiqih Ibadah']);
        $this->materiTerbit($ahmad, ['judul' => 'Sirah Nabawiyah']);
        $this->materiTerbit($budi, ['judul' => 'Fiqih Muamalah']);

        $token = $this->token($ahmad);

        $this->withToken($token)->getJson('/api/materi?penulis=Ahmad')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->withToken($token)->getJson('/api/materi?penulis=Ahmad&q=fiqih')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Fiqih Ibadah');
    }

    public function test_pencarian_memakai_awalan_kata(): void
    {
        $penulis = User::factory()->create();
        $this->materiTerbit($penulis, ['judul' => 'Biskirintil']);
        $this->materiTerbit($penulis, ['judul' => 'Adab Makan']);

        $this->withToken($this->token($penulis))->getJson('/api/materi?q=bisk')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Biskirintil');
    }

    // KP-16.1
    public function test_kata_kunci_juga_mencocokkan_nama_penulis(): void
    {
        $ahmad = User::factory()->create(['nama' => 'Ahmad Fauzi']);
        $budi = User::factory()->create(['nama' => 'Budi Santoso']);

        $this->materiTerbit($ahmad, ['judul' => 'Fiqih Ibadah'], '<p>Isi pertama.</p>');
        $this->materiTerbit($budi, ['judul' => 'Fiqih Muamalah'], '<p>Isi kedua.</p>');

        $this->withToken($this->token($ahmad))->getJson('/api/materi?q=ahmad')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Fiqih Ibadah')
            ->assertJsonPath('data.0.penulis.nama', 'Ahmad Fauzi');
    }

    public function test_materi_saya_dapat_disaring_dengan_kata_kunci(): void
    {
        $penulis = User::factory()->create();
        $lain = User::factory()->create();

        $fiqih = Materi::factory()->create(['penulis_id' => $penulis->id, 'judul' => 'Catatan Fiqih']);
        RevisiMateri::factory()->create([
            'materi_id' => $fiqih->id,
            'nomor_versi' => 1,
            'konten_html' => '<p>Rukun wudhu ada enam.</p>',
        ]);

        $sirah = Materi::factory()->create(['penulis_id' => $penulis->id, 'judul' => 'Catatan Sirah']);
        RevisiMateri::factory()->create([
            'materi_id' => $sirah->id,
            'nomor_versi' => 1,
            'konten_html' => '<p>Perjalanan hijrah.</p>',
        ]);

        $milikLain = Materi::factory()->create([
            'penulis_id' => $lain->id,
            'judul' => 'Catatan Fiqih Lain',
        ]);
        RevisiMateri::factory()->create([
            'materi_id' => $milikLain->id,
            'nomor_versi' => 1,
            'konten_html' => '<p>Isi lain.</p>',
        ]);

        $token = $this->token($penulis);

        $this->withToken($token)->getJson('/api/materi-saya?q=fiqih')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Catatan Fiqih');

        $this->withToken($token)->getJson('/api/materi-saya?q=wudhu')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Catatan Fiqih');
    }

    // KP-16.1
    public function test_filter_status_publikasi(): void
    {
        $penulis = User::factory()->create();
        $this->materiTerbit($penulis, ['judul' => 'Sudah Terbit']);

        $draf = Materi::factory()->create([
            'penulis_id' => $penulis->id,
            'judul' => 'Masih Draf',
            'status' => Materi::STATUS_DRAF,
        ]);
        RevisiMateri::factory()->create([
            'materi_id' => $draf->id,
            'nomor_versi' => 1,
        ]);

        $token = $this->token($penulis);

        $this->withToken($token)->getJson('/api/materi?status=draf')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Masih Draf');

        $this->withToken($token)->getJson('/api/materi?status=terbit')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Sudah Terbit');
    }

    // KP-16.2
    public function test_daftar_materi_memuat_info_ringkas_dan_jumlah_komentar(): void
    {
        $penulis = User::factory()->create(['nama' => 'Umar Khattab']);
        $revisi = $this->materiTerbit($penulis, [
            'judul' => 'Keutamaan Ilmu',
            'ringkasan' => 'Ringkasan keutamaan ilmu.',
        ]);

        Komentar::factory()->count(2)->create([
            'materi_id' => $revisi->materi_id,
            'parent_id' => null,
        ]);

        $this->withToken($this->token($penulis))->getJson('/api/materi')
            ->assertOk()
            ->assertJsonStructure(['data' => [[
                'id', 'judul', 'slug', 'ringkasan', 'penulis' => ['id', 'nama'],
                'diterbitkan_pada', 'nomor_versi', 'jumlah_komentar',
            ]]])
            ->assertJsonPath('data.0.penulis.nama', 'Umar Khattab')
            ->assertJsonPath('data.0.nomor_versi', 1)
            ->assertJsonPath('data.0.jumlah_komentar', 2);
    }

    public function test_kata_kunci_tanpa_hasil_mengembalikan_daftar_kosong(): void
    {
        $penulis = User::factory()->create();
        $this->materiTerbit($penulis, ['judul' => 'Materi Biasa']);

        $this->withToken($this->token($penulis))->getJson('/api/materi?q=qwertyzxcv')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }

    // KP-13.3, KP-16.4
    public function test_halaman_baca_anggota_menampilkan_revisi_terakhir_dan_versi_publik(): void
    {
        $penulis = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $versiPublik = $this->materiTerbit($penulis, ['judul' => 'Bab Zakat'], '<p>Isi versi publik.</p>');

        VerifikasiMateri::factory()->create([
            'revisi_id' => $versiPublik->id,
            'admin_id' => $admin->id,
        ]);

        $revisiTerakhir = RevisiMateri::factory()->create([
            'materi_id' => $versiPublik->materi_id,
            'nomor_versi' => 2,
            'konten_html' => '<p>Isi revisi terakhir.</p>',
        ]);

        $this->withToken($this->token(User::factory()->create()))
            ->getJson("/api/materi/{$versiPublik->materi->slug}")
            ->assertOk()
            ->assertJsonPath('data.judul', 'Bab Zakat')
            ->assertJsonPath('data.revisi.id', $revisiTerakhir->id)
            ->assertJsonPath('data.revisi.konten_html', '<p>Isi revisi terakhir.</p>')
            ->assertJsonPath('data.versi_publik.nomor_versi', 1)
            ->assertJsonCount(2, 'data.riwayat')
            ->assertJsonMissingPath('data.riwayat.0.verifikasi')
            ->assertJsonMissingPath('data.riwayat.1.verifikasi');
    }

    // KP-16.1
    public function test_anggota_dapat_membuka_materi_yang_belum_terbit(): void
    {
        $penulis = User::factory()->create();
        $materi = Materi::factory()->create([
            'penulis_id' => $penulis->id,
            'status' => Materi::STATUS_DIAJUKAN,
        ]);
        $revisi = RevisiMateri::factory()->create([
            'materi_id' => $materi->id,
            'nomor_versi' => 1,
            'konten_html' => '<p>Draf untuk diskusi.</p>',
        ]);

        $this->withToken($this->token($penulis))
            ->getJson("/api/materi/{$materi->slug}")
            ->assertOk()
            ->assertJsonPath('data.revisi.id', $revisi->id)
            ->assertJsonPath('data.revisi.konten_html', '<p>Draf untuk diskusi.</p>')
            ->assertJsonPath('data.versi_publik', null);
    }

    public function test_tamu_tidak_dapat_membuka_arsip_dan_halaman_anggota(): void
    {
        $penulis = User::factory()->create();
        $revisi = $this->materiTerbit($penulis, ['judul' => 'Materi Terbit']);

        $this->getJson('/api/materi')->assertUnauthorized();
        $this->getJson("/api/materi/{$revisi->materi->slug}")->assertUnauthorized();
    }

    // KP-17.1
    public function test_pengunjung_hanya_melihat_materi_terbit_di_halaman_publikasi(): void
    {
        $penulis = User::factory()->create(['nama' => 'Penulis Publik']);
        $this->materiTerbit($penulis, ['judul' => 'Materi Terbit']);
        Materi::factory()->create(['penulis_id' => $penulis->id, 'judul' => 'Masih Draf']);

        $this->getJson('/api/publik/materi')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Materi Terbit')
            ->assertJsonPath('data.0.penulis.nama', 'Penulis Publik')
            ->assertJsonMissingPath('data.0.status')
            ->assertJsonMissingPath('data.0.jumlah_komentar');
    }

    // KP-17.2, KP-17.4
    public function test_pengunjung_membaca_versi_publik_bukan_revisi_terakhir(): void
    {
        $penulis = User::factory()->create();
        $versiPublik = $this->materiTerbit(
            $penulis,
            ['judul' => 'Bab Zakat'],
            '<p>Isi versi publik.</p>'
        );

        RevisiMateri::factory()->create([
            'materi_id' => $versiPublik->materi_id,
            'nomor_versi' => 2,
            'konten_html' => '<p>Isi draf baru.</p>',
        ]);

        $this->getJson("/api/publik/materi/{$versiPublik->materi->slug}")
            ->assertOk()
            ->assertJsonPath('data.judul', 'Bab Zakat')
            ->assertJsonPath('data.konten_html', '<p>Isi versi publik.</p>')
            ->assertJsonPath('data.penulis.nama', $penulis->nama)
            ->assertJsonMissingPath('data.riwayat')
            ->assertJsonMissingPath('data.revisi')
            ->assertJsonMissingPath('data.jumlah_komentar')
            ->assertJsonMissingPath('data.penulis.email');
    }

    // KP-17.3
    public function test_pengunjung_tidak_dapat_membuka_materi_belum_terbit(): void
    {
        $penulis = User::factory()->create();
        $draf = Materi::factory()->create([
            'penulis_id' => $penulis->id,
            'status' => Materi::STATUS_DRAF,
        ]);

        $this->getJson("/api/publik/materi/{$draf->slug}")->assertNotFound();
    }
}