<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Verifikasi Pendaftaran
class PendaftaranTest extends TestCase
{
    use RefreshDatabase;

    private function tokenAdmin(): string
    {
        return User::factory()->admin()->create()->createToken('api')->plainTextToken;
    }

    private function pendaftar(?string $catatan = null): User
    {
        $user = User::factory()->menunggu()->create();
        Anggota::factory()->menunggu()->create([
            'user_id' => $user->id,
            'alasan_bergabung' => 'Ingin menambah wawasan keislaman.',
            'catatan_verifikasi' => $catatan,
        ]);

        return $user;
    }

    // KP-05.1
    public function test_daftar_pendaftar_menampilkan_status_menunggu_dan_filter_ditolak(): void
    {
        $token = $this->tokenAdmin();
        $menunggu = $this->pendaftar();

        $ditolak = User::factory()->ditolak()->create();
        Anggota::factory()->create([
            'user_id' => $ditolak->id,
            'catatan_verifikasi' => 'Alamat tidak lengkap.',
        ]);

        $this->withToken($token)->getJson('/api/admin/pendaftaran')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $menunggu->id);

        $this->withToken($token)->getJson('/api/admin/pendaftaran?status=ditolak')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ditolak->id);
    }

    // KP-05.2
    public function test_detail_pendaftar_menampilkan_alasan_dan_foto(): void
    {
        $token = $this->tokenAdmin();
        $user = $this->pendaftar();
        $user->anggota()->update(['foto' => 'foto-anggota/contoh.jpg']);

        $respons = $this->withToken($token)->getJson("/api/admin/pendaftaran/{$user->id}");

        $respons->assertOk()
            ->assertJsonPath('data.nama', $user->nama)
            ->assertJsonPath('data.anggota.alasan_bergabung', 'Ingin menambah wawasan keislaman.');

        $this->assertStringContainsString('foto-anggota/contoh.jpg', $respons->json('data.anggota.foto'));
    }

    // KP-05.3
    public function test_menyetujui_pendaftar_mengaktifkan_akun_dan_mengisi_tanggal(): void
    {
        $token = $this->tokenAdmin();
        $user = $this->pendaftar();

        $this->withToken($token)->patchJson("/api/admin/pendaftaran/{$user->id}", [
            'keputusan' => 'disetujui',
        ])->assertOk();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'aktif']);

        $anggota = Anggota::where('user_id', $user->id)->firstOrFail();
        $this->assertNotNull($anggota->tanggal_bergabung);
        $this->assertNotNull($anggota->diverifikasi_pada);

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk();
    }

    // KP-05.4
    public function test_menolak_tanpa_catatan_ditolak_422(): void
    {
        $token = $this->tokenAdmin();
        $user = $this->pendaftar();

        $this->withToken($token)->patchJson("/api/admin/pendaftaran/{$user->id}", [
            'keputusan' => 'ditolak',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('catatan');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'menunggu']);
    }

    // KP-04.2, KP-05.4
    public function test_menolak_dengan_catatan_menyimpan_alasan(): void
    {
        $token = $this->tokenAdmin();
        $user = $this->pendaftar();

        $this->withToken($token)->patchJson("/api/admin/pendaftaran/{$user->id}", [
            'keputusan' => 'ditolak',
            'catatan' => 'Alamat belum lengkap.',
        ])->assertOk();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'ditolak']);
        $this->assertDatabaseHas('anggota', [
            'user_id' => $user->id,
            'catatan_verifikasi' => 'Alamat belum lengkap.',
        ]);

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
            ->assertStatus(403)
            ->assertJsonPath('catatan', 'Alamat belum lengkap.');
    }

    // KP-05.5
    public function test_pendaftar_yang_sudah_diputuskan_tidak_bisa_diputuskan_ulang(): void
    {
        $token = $this->tokenAdmin();
        $user = $this->pendaftar();

        $this->withToken($token)->patchJson("/api/admin/pendaftaran/{$user->id}", [
            'keputusan' => 'disetujui',
        ])->assertOk();

        $this->withToken($token)->patchJson("/api/admin/pendaftaran/{$user->id}", [
            'keputusan' => 'ditolak',
            'catatan' => 'Dibatalkan.',
        ])->assertStatus(422);
    }

    // KP-04.5
    public function test_anggota_biasa_tidak_boleh_membuka_verifikasi_pendaftaran(): void
    {
        $token = User::factory()->create()->createToken('api')->plainTextToken;

        $this->withToken($token)->getJson('/api/admin/pendaftaran')->assertStatus(403);
    }
}