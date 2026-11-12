<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Data Anggota
class AnggotaAdminTest extends TestCase
{
    use RefreshDatabase;

    private function tokenAdmin(): string
    {
        return User::factory()->admin()->create()->createToken('api')->plainTextToken;
    }

    // KP-06.1
    public function test_daftar_anggota_bisa_dicari_dan_diffilter_status(): void
    {
        $token = $this->tokenAdmin();
        $budi = User::factory()->create(['nama' => 'Budi Santoso', 'email' => 'budi@alfutuuhat.test']);
        Anggota::factory()->create(['user_id' => $budi->id]);
        User::factory()->create(['nama' => 'Siti Aminah', 'email' => 'siti@alfutuuhat.test']);

        $this->withToken($token)->getJson('/api/admin/anggota?q=Budi')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama', 'Budi Santoso')
            ->assertJsonPath('data.0.anggota.id', $budi->anggota()->first()->id)
            ->assertJsonPath('data.0.anggota.no_hp', $budi->anggota()->first()->no_hp);

        $this->withToken($token)->getJson('/api/admin/anggota?q=siti@alfutuuhat.test')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->withToken($token)->getJson('/api/admin/anggota?status=aktif')
            ->assertOk()
            ->assertJsonPath('meta.total', 3);
    }

    // KP-06.2
    public function test_admin_mengubah_data_dan_peran_anggota(): void
    {
        $token = $this->tokenAdmin();
        $user = User::factory()->create();
        Anggota::factory()->create(['user_id' => $user->id]);

        $this->withToken($token)->putJson("/api/admin/anggota/{$user->id}", [
            'nama' => 'Nama Baru',
            'no_hp' => '081200000001',
            'alamat' => 'Jl. Baru No. 2',
            'role' => 'admin',
        ])->assertOk();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'nama' => 'Nama Baru', 'role' => 'admin']);
        $this->assertDatabaseHas('anggota', [
            'user_id' => $user->id,
            'no_hp' => '081200000001',
            'alamat' => 'Jl. Baru No. 2',
        ]);
    }

    // KP-06.3
    public function test_menonaktifkan_akun_menghapus_semua_token_dan_menolak_akses(): void
    {
        $token = $this->tokenAdmin();
        $user = User::factory()->create();
        $tokenUser = $user->createToken('api')->plainTextToken;

        $this->withToken($token)->patchJson("/api/admin/anggota/{$user->id}/status", [
            'status' => 'nonaktif',
        ])->assertOk();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'nonaktif']);
        $this->assertSame(0, $user->tokens()->count(), 'Semua token akun harus dihapus.');

        $this->app['auth']->forgetGuards();
        $this->withToken($tokenUser)->getJson('/api/me')->assertStatus(401);

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
            ->assertStatus(403)
            ->assertJsonPath('status_akun', 'nonaktif');
    }

    // KP-06.3
    public function test_akun_yang_diaktifkan_kembali_bisa_login(): void
    {
        $token = $this->tokenAdmin();
        $user = User::factory()->nonaktif()->create();

        $this->withToken($token)->patchJson("/api/admin/anggota/{$user->id}/status", [
            'status' => 'aktif',
        ])->assertOk();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'aktif']);
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])->assertOk();
    }

    // KP-06.4
    public function test_atur_ulang_kata_sandi_mengganti_kata_sandi_lama(): void
    {
        $token = $this->tokenAdmin();
        $user = User::factory()->create();

        $this->withToken($token)->patchJson("/api/admin/anggota/{$user->id}/password", [
            'password' => 'katasandi-baru',
            'password_confirmation' => 'katasandi-baru',
        ])->assertOk();

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'katasandi-baru'])->assertOk();
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])->assertStatus(422);
    }

    // KP-06.5
    public function test_admin_tidak_dapat_menonaktifkan_atau_menurunkan_peran_dirinya_sendiri(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('api')->plainTextToken;

        $this->withToken($token)->patchJson("/api/admin/anggota/{$admin->id}/status", [
            'status' => 'nonaktif',
        ])->assertStatus(422)->assertJsonValidationErrors('status');

        $this->withToken($token)->putJson("/api/admin/anggota/{$admin->id}", [
            'nama' => $admin->nama,
            'no_hp' => '081200000002',
            'alamat' => 'Jl. Admin No. 1',
            'role' => 'anggota',
        ])->assertStatus(422)->assertJsonValidationErrors('role');

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => 'admin', 'status' => 'aktif']);
    }
}