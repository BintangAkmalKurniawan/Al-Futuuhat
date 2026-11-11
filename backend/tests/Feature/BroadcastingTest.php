<?php

namespace Tests\Feature;

use App\Models\Materi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F9b — Otorisasi kanal real-time (KP-15.3).
 */
class BroadcastingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Di lingkungan tes driver siaran disetel `null` (phpunit.xml) sehingga kanal
        // tidak diperiksa. Pakai driver reverb dan daftarkan ulang kanal agar
        // otorisasi di routes/channels.php benar-benar diuji.
        config(['broadcasting.default' => 'reverb']);
        require base_path('routes/channels.php');
    }

    private function token(User $user): string
    {
        return $user->createToken('api')->plainTextToken;
    }

    /** Minta izin berlangganan kanal privat forum materi. */
    private function mintaIzin(User $user, Materi $materi)
    {
        return $this->withToken($this->token($user))->postJson('/api/broadcasting/auth', [
            'channel_name' => 'private-materi.'.$materi->id,
            'socket_id' => '1234.5678',
        ]);
    }

    public function test_anggota_aktif_boleh_berlangganan_materi_terbit(): void
    {
        $materi = Materi::factory()->terbit()->create();

        // KP-15.3
        $this->mintaIzin(User::factory()->create(), $materi)
            ->assertOk()
            ->assertJsonStructure(['auth']);
    }

    public function test_kanal_materi_yang_belum_terbit_diperbolehkan_untuk_anggota(): void
    {
        $materi = Materi::factory()->create(['status' => Materi::STATUS_DIAJUKAN]);

        // KP-15.3 (revisi): kanal forum mengikuti aturan diskusi, bukan status terbit.
        $this->mintaIzin(User::factory()->create(), $materi)
            ->assertOk()
            ->assertJsonStructure(['auth']);
    }

    public function test_anggota_nonaktif_ditolak_walau_token_masih_ada(): void
    {
        $user = User::factory()->create();
        $materi = Materi::factory()->terbit()->create();
        $token = $this->token($user);

        // Status diubah langsung di basis data: token belum terhapus.
        $user->update(['status' => User::STATUS_NONAKTIF]);

        $this->withToken($token)->postJson('/api/broadcasting/auth', [
            'channel_name' => 'private-materi.'.$materi->id,
            'socket_id' => '1234.5678',
        ])->assertStatus(403);
    }

    public function test_tamu_tidak_dapat_meminta_izin_kanal(): void
    {
        $materi = Materi::factory()->terbit()->create();

        // KP-15.3
        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => 'private-materi.'.$materi->id,
            'socket_id' => '1234.5678',
        ])->assertStatus(401);
    }
}