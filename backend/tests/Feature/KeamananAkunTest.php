<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

// Batas login & lupa kata sandi
class KeamananAkunTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_dibatasi_setelah_lima_kali_gagal(): void
    {
        User::factory()->create(['email' => 'budi@alfutuuhat.test']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', ['email' => 'budi@alfutuuhat.test', 'password' => 'salah123'])
                ->assertUnprocessable();
        }

        $this->postJson('/api/login', ['email' => 'budi@alfutuuhat.test', 'password' => 'salah123'])
            ->assertStatus(429)
            ->assertJsonPath('message', 'Terlalu banyak percobaan. Tunggu sebentar, lalu coba lagi.');
    }

    public function test_token_login_punya_masa_berlaku(): void
    {
        $this->assertNotNull(config('sanctum.expiration'));
    }

    public function test_lupa_kata_sandi_mengirim_tautan_ke_email_terdaftar(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'budi@alfutuuhat.test']);

        $this->postJson('/api/lupa-kata-sandi', ['email' => 'budi@alfutuuhat.test'])->assertOk();

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notifikasi) use ($user) {
            $url = $notifikasi->toMail($user)->actionUrl;

            return str_starts_with($url, config('app.frontend_url').'/atur-ulang-kata-sandi?token=');
        });
    }

    public function test_lupa_kata_sandi_email_tidak_terdaftar_jawabannya_sama(): void
    {
        Notification::fake();

        $this->postJson('/api/lupa-kata-sandi', ['email' => 'tidakada@alfutuuhat.test'])
            ->assertOk()
            ->assertJsonPath('message', 'Kalau emailnya terdaftar, tautannya sudah kami kirim. Cek kotak masuk atau folder spam ya.');

        Notification::assertNothingSent();
    }

    public function test_atur_ulang_kata_sandi_mengganti_sandi_dan_menghapus_token_lama(): void
    {
        $user = User::factory()->create(['email' => 'budi@alfutuuhat.test']);
        $user->createToken('api');
        $token = Password::createToken($user);

        $this->postJson('/api/atur-ulang-kata-sandi', [
            'token' => $token,
            'email' => 'budi@alfutuuhat.test',
            'password' => 'sandibaru123',
            'password_confirmation' => 'sandibaru123',
        ])->assertOk();

        $this->assertTrue(Hash::check('sandibaru123', $user->fresh()->password));
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_atur_ulang_dengan_token_salah_ditolak(): void
    {
        $user = User::factory()->create(['email' => 'budi@alfutuuhat.test']);

        $this->postJson('/api/atur-ulang-kata-sandi', [
            'token' => 'token-palsu',
            'email' => 'budi@alfutuuhat.test',
            'password' => 'sandibaru123',
            'password_confirmation' => 'sandibaru123',
        ])->assertUnprocessable()->assertJsonValidationErrors('token');

        $this->assertFalse(Hash::check('sandibaru123', $user->fresh()->password));
    }
}
