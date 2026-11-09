<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * F1 — Pendaftaran & Login (KF-02, KF-03, KF-04).
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Isian formulir pendaftaran yang valid.
     *
     * @return array<string, mixed>
     */
    private function dataPendaftaran(array $ubah = []): array
    {
        return array_merge([
            'nama' => 'Ahmad Fauzi',
            'email' => 'fauzi@alfutuuhat.test',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'no_hp' => '081234567890',
            'alamat' => 'Jl. Contoh No. 1, Bandung',
            'alasan_bergabung' => 'Ingin belajar dan mengajar bersama.',
        ], $ubah);
    }

    // ---------------------------------------------------------------- Daftar

    public function test_daftar_dengan_data_lengkap_menyimpan_users_dan_anggota(): void
    {
        Storage::fake('public');

        // KP-02.1, KP-03.1: satu baris users + satu baris anggota
        $respons = $this->post('/api/register', $this->dataPendaftaran([
            'foto' => UploadedFile::fake()->image('foto.jpg', 300, 300),
        ]));

        $respons->assertCreated();
        $this->assertDatabaseHas('users', [
            'email' => 'fauzi@alfutuuhat.test',
            'role' => 'anggota',
            'status' => 'menunggu',
        ]);

        $user = User::where('email', 'fauzi@alfutuuhat.test')->firstOrFail();
        $anggota = Anggota::where('user_id', $user->id)->firstOrFail();

        $this->assertSame('081234567890', $anggota->no_hp);
        $this->assertNotNull($anggota->foto);
        Storage::disk('public')->assertExists($anggota->foto); // KP-03.1 foto tersimpan
    }

    public function test_email_sudah_terdaftar_ditolak(): void
    {
        User::factory()->create(['email' => 'sama@alfutuuhat.test']);

        // KP-02.2
        $this->postJson('/api/register', $this->dataPendaftaran(['email' => 'sama@alfutuuhat.test']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('email')
            ->assertJsonPath('errors.email.0', 'Email sudah terdaftar.');
    }

    public function test_kata_sandi_kurang_delapan_karakter_ditolak(): void
    {
        // KP-02.3
        $this->postJson('/api/register', $this->dataPendaftaran([
            'password' => 'rahasia',
            'password_confirmation' => 'rahasia',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('password')
            ->assertJsonPath('errors.password.0', 'Kata sandi minimal 8 karakter.');
    }

    public function test_konfirmasi_kata_sandi_berbeda_ditolak(): void
    {
        // KP-02.3
        $this->postJson('/api/register', $this->dataPendaftaran([
            'password_confirmation' => 'rahasia456',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('password')
            ->assertJsonPath('errors.password.0', 'Konfirmasi kata sandi tidak sama.');
    }

    public function test_foto_selain_jpg_png_dan_lebih_dari_2mb_ditolak(): void
    {
        Storage::fake('public');

        // KP-02.4: berkas PDF
        $this->post('/api/register', $this->dataPendaftaran([
            'email' => 'pdf@alfutuuhat.test',
            'foto' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
        ]))->assertStatus(422)->assertJsonValidationErrors('foto');

        // KP-02.4: gambar 3 MB
        $this->post('/api/register', $this->dataPendaftaran([
            'email' => 'besar@alfutuuhat.test',
            'foto' => UploadedFile::fake()->image('besar.jpg')->size(3000),
        ]))->assertStatus(422)->assertJsonValidationErrors('foto');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_isian_kosong_dilaporkan_per_isian(): void
    {
        // KP-02.5: pesan kesalahan menempel pada isian yang salah
        $this->postJson('/api/register', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'nama', 'email', 'password', 'no_hp', 'alamat', 'alasan_bergabung',
            ]);
    }

    // ----------------------------------------------------------------- Login

    public function test_login_email_atau_kata_sandi_salah_memakai_pesan_yang_sama(): void
    {
        User::factory()->create(['email' => 'aktif@alfutuuhat.test']);

        // KP-04.1: pesan tidak membedakan email salah atau kata sandi salah
        $this->postJson('/api/login', ['email' => 'aktif@alfutuuhat.test', 'password' => 'salah-sekali'])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Email atau kata sandi tidak sesuai.');

        $this->postJson('/api/login', ['email' => 'tidak-terdaftar@alfutuuhat.test', 'password' => 'password'])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Email atau kata sandi tidak sesuai.');
    }

    public function test_login_akun_menunggu_ditolak(): void
    {
        $user = User::factory()->menunggu()->create();
        Anggota::factory()->menunggu()->create(['user_id' => $user->id]);

        // KP-03.3
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
            ->assertStatus(403)
            ->assertJsonPath('message', 'Pendaftaran Anda masih menunggu verifikasi admin.')
            ->assertJsonPath('status_akun', 'menunggu');
    }

    public function test_login_akun_ditolak_menyertakan_alasan_admin(): void
    {
        $user = User::factory()->ditolak()->create();
        Anggota::factory()->create([
            'user_id' => $user->id,
            'catatan_verifikasi' => 'Alamat tidak jelas, mohon lengkapi.',
        ]);

        // KP-04.2
        $respons = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password']);

        $respons->assertStatus(403)
            ->assertJsonPath('status_akun', 'ditolak')
            ->assertJsonPath('catatan', 'Alamat tidak jelas, mohon lengkapi.');

        $this->assertStringContainsString('Alamat tidak jelas, mohon lengkapi.', $respons->json('message'));
    }

    public function test_login_akun_nonaktif_ditolak(): void
    {
        $user = User::factory()->nonaktif()->create();

        // KP-04.3
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
            ->assertStatus(403)
            ->assertJsonPath('message', 'Akun Anda dinonaktifkan. Hubungi pengurus.')
            ->assertJsonPath('status_akun', 'nonaktif');
    }

    public function test_login_anggota_dan_admin_aktif_mendapat_token_dan_perannya(): void
    {
        $anggota = User::factory()->create();
        $admin = User::factory()->admin()->create();

        // KP-04.4
        $this->postJson('/api/login', ['email' => $anggota->email, 'password' => 'password'])
            ->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'nama', 'email', 'role', 'status']])
            ->assertJsonPath('user.role', 'anggota')
            ->assertJsonPath('user.status', 'aktif');

        $this->postJson('/api/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('user.role', 'admin');
    }

    // ------------------------------------------------------- Peran dan token

    public function test_anggota_tidak_dapat_memakai_api_admin(): void
    {
        $token = User::factory()->create()->createToken('api')->plainTextToken;

        // KP-04.5
        $this->withToken($token)->getJson('/api/admin/dasbor')->assertStatus(403);
    }

    public function test_logout_menghapus_token_yang_dipakai(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)->getJson('/api/me')->assertOk();
        $this->withToken($token)->postJson('/api/logout')->assertOk();

        // Di dalam satu tes, guard Sanctum menyimpan pengguna di memori.
        // Lupakan guard agar permintaan berikutnya benar-benar memeriksa token ke basis data.
        $this->app['auth']->forgetGuards();

        // KP-04.6: token lama tidak berlaku lagi
        $this->withToken($token)->getJson('/api/me')->assertStatus(401);
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_akun_yang_dinonaktifkan_tidak_dapat_memakai_api(): void
    {
        $user = User::factory()->nonaktif()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)->getJson('/api/me')->assertStatus(403);
    }

    public function test_me_mengembalikan_data_pengguna_yang_sedang_login(): void
    {
        $user = User::factory()->create();
        Anggota::factory()->create(['user_id' => $user->id, 'no_hp' => '089900112233']);

        $this->withToken($user->createToken('api')->plainTextToken)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.anggota.no_hp', '089900112233');
    }
}
