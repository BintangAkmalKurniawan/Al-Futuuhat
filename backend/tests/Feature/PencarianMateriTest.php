<?php

namespace Tests\Feature;

use App\Models\Komentar;
use App\Models\Materi;
use App\Models\RevisiMateri;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F8 — Arsip, Pencarian & Halaman Baca (KF-16).
 */
class PencarianMateriTest extends TestCase
{
    use RefreshDatabase;

    private function token(User $user): string
    {
        return $user->createToken('api')->plainTextToken;
    }

    /**
     * Materi terbit dengan satu versi disetujui.
     */
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

    public function test_pencarian_judul_tidak_membedakan_huruf_besar_kecil(): void
    {
        $penulis = User::factory()->create();
        $this->materiTerbit($penulis, ['judul' => 'Panduan Sholat Berjamaah']);
        $this->materiTerbit($penulis, ['judul' => 'Adab Makan']);

        // KP-16.2
        $this->withToken($this->token($penulis))
            ->getJson('/api/materi?q=SHOLAT')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Panduan Sholat Berjamaah')
            ->assertJsonPath('meta.total', 1);
    }

    public function test_pencarian_isi_versi_terbit(): void
    {
        $penulis = User::factory()->create();
        $this->materiTerbit($penulis, ['judul' => 'Bab Thaharah'], '<p>Rukun wudhu ada enam.</p>');
        $this->materiTerbit($penulis, ['judul' => 'Bab Puasa'], '<p>Puasa wajib bagi mukallaf.</p>');

        // KP-16.2: kata "wudhu" hanya ada di isi materi pertama
        $this->withToken($this->token($penulis))
            ->getJson('/api/materi?q=wudhu')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Bab Thaharah');
    }

    public function test_isi_draf_yang_belum_terbit_tidak_ikut_tercari(): void
    {
        $penulis = User::factory()->create();

        // Materi terbit, tetapi draf revisi barunya berisi kata rahasia.
        $revisi = $this->materiTerbit($penulis, ['judul' => 'Bab Manasik'], '<p>Isi lama.</p>');
        RevisiMateri::factory()->create([
            'materi_id' => $revisi->materi_id,
            'nomor_versi' => 2,
            'konten_html' => '<p>Pembahasan istinja lengkap.</p>',
        ]);

        // Materi yang seluruhnya masih draf.
        $draf = Materi::factory()->create(['penulis_id' => $penulis->id, 'judul' => 'Rahasia Istinja']);
        RevisiMateri::factory()->create(['materi_id' => $draf->id, 'konten_html' => '<p>Istinja.</p>']);

        // KP-16.1, KP-16.2
        $this->withToken($this->token($penulis))
            ->getJson('/api/materi?q=istinja')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }

    public function test_filter_penulis_dan_kata_kunci_dapat_digabung(): void
    {
        $ahmad = User::factory()->create(['nama' => 'Ahmad Fauzi']);
        $budi = User::factory()->create(['nama' => 'Budi Santoso']);

        $this->materiTerbit($ahmad, ['judul' => 'Fiqih Ibadah']);
        $this->materiTerbit($ahmad, ['judul' => 'Sirah Nabawiyah']);
        $this->materiTerbit($budi, ['judul' => 'Fiqih Muamalah']);

        $token = $this->token($ahmad);

        // KP-16.3: filter nama penulis
        $this->withToken($token)->getJson('/api/materi?penulis=Ahmad')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        // KP-16.3: filter penulis + kata kunci sekaligus
        $this->withToken($token)->getJson('/api/materi?penulis=Ahmad&q=fiqih')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Fiqih Ibadah');
    }

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

        // KP-16.2: kartu daftar berisi judul, penulis, nomor versi, jumlah komentar
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

    public function test_halaman_baca_menampilkan_isi_versi_terbit_dan_riwayat(): void
    {
        $penulis = User::factory()->create();
        $revisi = $this->materiTerbit($penulis, ['judul' => 'Bab Zakat'], '<p>Isi versi terbit.</p>');

        // KP-16.4
        $this->withToken($this->token(User::factory()->create()))
            ->getJson("/api/materi/{$revisi->materi->slug}")
            ->assertOk()
            ->assertJsonPath('data.judul', 'Bab Zakat')
            ->assertJsonPath('data.revisi.id', $revisi->id)
            ->assertJsonPath('data.revisi.konten_html', '<p>Isi versi terbit.</p>')
            ->assertJsonCount(1, 'data.riwayat');
    }

    public function test_materi_yang_belum_terbit_tidak_dapat_dibuka_lewat_halaman_baca(): void
    {
        $penulis = User::factory()->create();
        $materi = Materi::factory()->create(['penulis_id' => $penulis->id, 'status' => Materi::STATUS_DIAJUKAN]);

        // KP-16.1
        $this->withToken($this->token($penulis))
            ->getJson("/api/materi/{$materi->slug}")
            ->assertStatus(404);
    }

    public function test_tamu_tidak_dapat_membuka_arsip_materi(): void
    {
        // PRD §11: seluruh endpoint materi wajib login.
        $this->getJson('/api/materi')->assertStatus(401);
    }
}