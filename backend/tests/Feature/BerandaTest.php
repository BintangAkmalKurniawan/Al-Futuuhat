<?php

namespace Tests\Feature;

use App\Models\PengaturanBeranda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * KF-18 — konten beranda yang diatur admin: cerita Al-Futuuhat.
 * Draf 2.9: fitur poster dihapus.
 */
class BerandaTest extends TestCase
{
    use RefreshDatabase;

    private function token(User $user): string
    {
        return $user->createToken('api')->plainTextToken;
    }

    public function test_beranda_tetap_terbuka_walau_admin_belum_mengatur_konten(): void
    {
        // KP-18.2: belum ada pengaturan, halaman tetap tampil tanpa galat.
        $this->getJson('/api/publik/beranda')
            ->assertOk()
            ->assertJsonPath('data.cerita_judul', null)
            ->assertJsonPath('data.cerita_isi', null)
            ->assertJsonMissingPath('data.poster');
    }

    public function test_pengunjung_membaca_cerita_beranda_tanpa_login(): void
    {
        $admin = User::factory()->admin()->create();

        // KP-18.1: admin menyimpan cerita.
        $this->withToken($this->token($admin))
            ->post('/api/admin/beranda', [
                'cerita_judul' => 'Dari Serambi ke Masyarakat',
                'cerita_isi' => '<p>Al-Futuuhat tumbuh dari kajian kecil.</p><script>curi()</script>',
            ])
            ->assertOk()
            ->assertJsonPath('data.cerita_judul', 'Dari Serambi ke Masyarakat');

        // Isi cerita disanitasi seperti isi materi (tag <script> dibuang).
        $this->assertStringNotContainsString('<script', (string) PengaturanBeranda::pengaturan()?->cerita_isi);

        // KP-18.3: pengunjung tanpa token hanya dapat membaca.
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/publik/beranda')
            ->assertOk()
            ->assertJsonPath('data.cerita_judul', 'Dari Serambi ke Masyarakat')
            ->assertJsonPath('data.cerita_isi', '<p>Al-Futuuhat tumbuh dari kajian kecil.</p>');
    }

    public function test_admin_mengatur_visi_dan_misi_untuk_halaman_tentang_kami(): void
    {
        // KF-26
        $this->withToken($this->token(User::factory()->admin()->create()))
            ->post('/api/admin/beranda', [
                'visi' => 'Menjadi wadah belajar yang bermanfaat.',
                'misi' => "Mengadakan kajian rutin.\nMembina anggota baru.",
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $this->app['auth']->forgetGuards();

        $this->getJson('/api/publik/beranda')
            ->assertJsonPath('data.visi', 'Menjadi wadah belajar yang bermanfaat.')
            ->assertJsonPath('data.misi', "Mengadakan kajian rutin.\nMembina anggota baru.");
    }

    public function test_judul_cerita_lebih_dari_150_karakter_ditolak(): void
    {
        $this->withToken($this->token(User::factory()->admin()->create()))
            ->post('/api/admin/beranda', ['cerita_judul' => str_repeat('a', 151)], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('cerita_judul');
    }

    public function test_anggota_biasa_tidak_dapat_mengubah_konten_beranda(): void
    {
        $anggota = User::factory()->create();

        // KP-18.3
        $this->withToken($this->token($anggota))
            ->post('/api/admin/beranda', ['cerita_judul' => 'Coba ubah'])
            ->assertForbidden();
    }

    public function test_tamu_tidak_dapat_mengubah_konten_beranda(): void
    {
        $this->getJson('/api/admin/beranda')->assertUnauthorized();
        $this->postJson('/api/admin/beranda', ['cerita_judul' => 'Coba ubah'])->assertUnauthorized();
    }
}
