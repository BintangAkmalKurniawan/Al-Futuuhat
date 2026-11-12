<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

// Verifikasi email
class VerifikasiEmailTest extends TestCase
{
    use RefreshDatabase;

    private function tautan(User $user, ?string $hash = null): string
    {
        return URL::temporarySignedRoute(
            'verifikasi.email',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => $hash ?? sha1($user->email)],
            absolute: false,
        );
    }

    private function pendaftar(): User
    {
        $user = User::factory()->menunggu()->belumKonfirmasi()->create();
        Anggota::factory()->menunggu()->create(['user_id' => $user->id]);

        return $user;
    }

    public function test_daftar_mengirim_email_konfirmasi(): void
    {
        Notification::fake();

        $this->post('/api/register', [
            'nama' => 'Ahmad Fauzi',
            'email' => 'fauzi@alfutuuhat.test',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'no_hp' => '081234567890',
            'alamat' => 'Jl. Contoh No. 1, Bandung',
            'alasan_bergabung' => 'Ingin belajar dan mengajar bersama.',
        ])->assertCreated();

        $user = User::where('email', 'fauzi@alfutuuhat.test')->firstOrFail();

        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_tautan_valid_mengonfirmasi_email(): void
    {
        $user = $this->pendaftar();

        $this->get($this->tautan($user))
            ->assertRedirect(config('app.frontend_url').'/verifikasi-email?status=berhasil');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_tautan_dipakai_lagi_dianggap_sudah(): void
    {
        $user = $this->pendaftar();
        $user->markEmailAsVerified();

        $this->get($this->tautan($user))
            ->assertRedirect(config('app.frontend_url').'/verifikasi-email?status=sudah');
    }

    public function test_tautan_palsu_ditolak(): void
    {
        $user = $this->pendaftar();

        $this->get($this->tautan($user, sha1('lain@alfutuuhat.test')))
            ->assertRedirect(config('app.frontend_url').'/verifikasi-email?status=gagal');

        $this->get('/api/email/verifikasi/'.$user->id.'/'.sha1($user->email))
            ->assertRedirect(config('app.frontend_url').'/verifikasi-email?status=gagal');

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_kirim_ulang_hanya_untuk_email_belum_dikonfirmasi(): void
    {
        Notification::fake();
        $user = $this->pendaftar();
        $sudah = User::factory()->create();

        $this->postJson('/api/email/kirim-ulang', ['email' => $user->email])->assertOk();
        $this->postJson('/api/email/kirim-ulang', ['email' => $sudah->email])->assertOk();
        $this->postJson('/api/email/kirim-ulang', ['email' => 'tidakada@alfutuuhat.test'])->assertOk();

        Notification::assertSentTo($user, VerifyEmail::class);
        Notification::assertNotSentTo($sudah, VerifyEmail::class);
    }

    public function test_login_sebelum_konfirmasi_diminta_konfirmasi_email(): void
    {
        $user = $this->pendaftar();

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
            ->assertForbidden()
            ->assertJsonPath('email_terkonfirmasi', false)
            ->assertJsonPath('message', 'Konfirmasi emailmu dulu ya. Cek kotak masuk atau folder spam, lalu klik tautannya.');
    }

    public function test_admin_tidak_bisa_menyetujui_sebelum_email_dikonfirmasi(): void
    {
        $admin = User::factory()->admin()->create();
        $user = $this->pendaftar();

        $this->actingAs($admin)
            ->patchJson('/api/admin/pendaftaran/'.$user->id, ['keputusan' => 'disetujui'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('keputusan');

        $this->assertSame(User::STATUS_MENUNGGU, $user->fresh()->status);
    }
}
