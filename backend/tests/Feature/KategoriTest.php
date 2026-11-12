<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Materi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Kelola kategori
class KategoriTest extends TestCase
{
    use RefreshDatabase;

    private function tokenAdmin(): string
    {
        return User::factory()->admin()->create()->createToken('api')->plainTextToken;
    }

    // KP-19.1
    public function test_admin_dapat_menambah_kategori_dengan_slug_otomatis(): void
    {
        $token = $this->tokenAdmin();

        $this->withToken($token)
            ->postJson('/api/admin/kategori', ['nama' => 'Politik Islam'])
            ->assertCreated()
            ->assertJsonPath('data.nama', 'Politik Islam')
            ->assertJsonPath('data.slug', 'politik-islam');

        $this->assertDatabaseHas('kategori', [
            'nama' => 'Politik Islam',
            'slug' => 'politik-islam',
        ]);
    }

    // KP-19.1
    public function test_nama_kategori_harus_unik(): void
    {
        $token = $this->tokenAdmin();
        Kategori::create(['nama' => 'Fiqih', 'slug' => 'fiqih']);

        $this->withToken($token)
            ->postJson('/api/admin/kategori', ['nama' => 'Fiqih'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('nama')
            ->assertJsonPath('errors.nama.0', 'Nama kategori sudah ada.');

        $this->withToken($token)
            ->postJson('/api/admin/kategori', ['nama' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('nama');
    }

    // KP-19.1
    public function test_admin_dapat_mengubah_nama_kategori(): void
    {
        $token = $this->tokenAdmin();
        $kategori = Kategori::create(['nama' => 'Akhlaq', 'slug' => 'akhlaq']);

        $this->withToken($token)
            ->putJson("/api/admin/kategori/{$kategori->id}", ['nama' => 'Akhlak'])
            ->assertOk()
            ->assertJsonPath('data.slug', 'akhlak');

        $this->withToken($token)
            ->putJson("/api/admin/kategori/{$kategori->id}", ['nama' => 'Akhlak'])
            ->assertOk();

        $this->assertDatabaseHas('kategori', ['id' => $kategori->id, 'nama' => 'Akhlak']);
    }

    // KP-19.1
    public function test_admin_dapat_menghapus_kategori_yang_belum_dipakai(): void
    {
        $token = $this->tokenAdmin();
        $kategori = Kategori::create(['nama' => 'Sejarah Islam', 'slug' => 'sejarah-islam']);

        $this->withToken($token)
            ->deleteJson("/api/admin/kategori/{$kategori->id}")
            ->assertOk();

        $this->assertDatabaseMissing('kategori', ['id' => $kategori->id]);
    }

    // KP-19.1
    public function test_kategori_yang_masih_dipakai_materi_tidak_dapat_dihapus(): void
    {
        $token = $this->tokenAdmin();
        $kategori = Kategori::create(['nama' => 'Fiqih', 'slug' => 'fiqih']);

        $materi = Materi::factory()->create();
        $materi->kategori()->attach($kategori->id);

        $this->withToken($token)
            ->deleteJson("/api/admin/kategori/{$kategori->id}")
            ->assertStatus(422)
            ->assertJsonPath('errors.kategori.0', 'Kategori ini masih dipakai oleh materi sehingga belum dapat dihapus.');

        $this->assertDatabaseHas('kategori', ['id' => $kategori->id]);
        $this->assertDatabaseHas('kategori_materi', ['kategori_id' => $kategori->id]);
    }

    // KP-19.1
    public function test_daftar_kategori_admin_memuat_jumlah_materi(): void
    {
        $token = $this->tokenAdmin();

        $fiqih = Kategori::create(['nama' => 'Fiqih', 'slug' => 'fiqih']);
        Kategori::create(['nama' => 'Akhlak', 'slug' => 'akhlak']);

        $materi = Materi::factory()->create();
        $materi->kategori()->attach($fiqih->id);

        $this->withToken($token)->getJson('/api/admin/kategori')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.nama', 'Akhlak')
            ->assertJsonPath('data.0.jumlah_materi', 0)
            ->assertJsonPath('data.1.nama', 'Fiqih')
            ->assertJsonPath('data.1.jumlah_materi', 1);
    }

    // KP-19.1
    public function test_anggota_biasa_tidak_dapat_mengelola_kategori(): void
    {
        $token = User::factory()->create()->createToken('api')->plainTextToken;
        $kategori = Kategori::create(['nama' => 'Fiqih', 'slug' => 'fiqih']);

        $this->withToken($token)
            ->getJson('/api/admin/kategori')
            ->assertStatus(403);

        $this->withToken($token)
            ->postJson('/api/admin/kategori', ['nama' => 'Kategori Baru'])
            ->assertStatus(403);

        $this->withToken($token)
            ->putJson("/api/admin/kategori/{$kategori->id}", ['nama' => 'Diubah'])
            ->assertStatus(403);

        $this->withToken($token)
            ->deleteJson("/api/admin/kategori/{$kategori->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('kategori', ['id' => $kategori->id, 'nama' => 'Fiqih']);
    }

    public function test_tamu_tanpa_login_tidak_dapat_mengelola_kategori(): void
    {
        $this->postJson('/api/admin/kategori', ['nama' => 'Kategori Baru'])
            ->assertStatus(401);

        $this->getJson('/api/admin/kategori')->assertStatus(401);
    }

    // KP-19.2
    public function test_daftar_kategori_publik_dapat_dibaca_tanpa_login(): void
    {
        Kategori::create(['nama' => 'Fiqih', 'slug' => 'fiqih']);
        Kategori::create(['nama' => 'Akhlak', 'slug' => 'akhlak']);

        $this->getJson('/api/kategori')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.nama', 'Akhlak')
            ->assertJsonPath('data.0.slug', 'akhlak')
            ->assertJsonMissingPath('data.0.jumlah_materi');
    }
}
