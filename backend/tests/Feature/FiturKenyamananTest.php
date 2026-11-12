<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\Komentar;
use App\Models\Materi;
use App\Models\RevisiMateri;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// Kontak, notifikasi komentar, profil, dan statistik
class FiturKenyamananTest extends TestCase
{
    use RefreshDatabase;

    private function sebagai(User $user): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($user->createToken('api')->plainTextToken);
    }

    private function anggota(string $nama = 'Ahmad'): User
    {
        $user = User::factory()->create(['nama' => $nama]);
        Anggota::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    private function materiMilik(User $penulis): Materi
    {
        $materi = Materi::factory()->create(['penulis_id' => $penulis->id]);
        RevisiMateri::factory()->create(['materi_id' => $materi->id, 'nomor_versi' => 1]);

        return $materi;
    }

    public function test_admin_mengatur_kontak_dan_pengunjung_membacanya(): void
    {
        $admin = User::factory()->admin()->create();

        $this->sebagai($admin)->post('/api/admin/beranda', [
            'kontak_alamat' => 'Jl. Masjid No. 1, Bandung',
            'kontak_whatsapp' => '0812-3456 7890',
            'kontak_email' => 'halo@alfutuuhat.test',
            'kontak_instagram' => '@alfutuuhat',
        ], ['Accept' => 'application/json'])->assertOk();

        $this->app['auth']->forgetGuards();

        $this->getJson('/api/publik/beranda')
            ->assertOk()
            ->assertJsonPath('data.kontak.alamat', 'Jl. Masjid No. 1, Bandung')
            ->assertJsonPath('data.kontak.whatsapp', '6281234567890')
            ->assertJsonPath('data.kontak.instagram', 'alfutuuhat');
    }

    public function test_kontak_tidak_valid_ditolak(): void
    {
        $this->sebagai(User::factory()->admin()->create())->post('/api/admin/beranda', [
            'kontak_whatsapp' => 'nomor saya',
            'kontak_email' => 'bukan-email',
            'kontak_instagram' => 'akun saya!',
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['kontak_whatsapp', 'kontak_email', 'kontak_instagram']);
    }

    public function test_penulis_diberi_tahu_saat_materinya_dikomentari(): void
    {
        $penulis = $this->anggota('Penulis');
        $materi = $this->materiMilik($penulis);

        $this->sebagai($this->anggota('Budi'))
            ->postJson("/api/materi/{$materi->id}/komentar", ['isi' => '<p>Bagus sekali.</p>'])
            ->assertCreated();

        $notifikasi = $penulis->notifications()->sole();
        $this->assertSame('komentar_baru', $notifikasi->data['tipe']);
        $this->assertSame('komentar', $notifikasi->data['jenis']);
        $this->assertSame('Budi', $notifikasi->data['pengirim']);
        $this->assertSame('Bagus sekali.', $notifikasi->data['cuplikan']);
    }

    public function test_pemilik_komentar_diberi_tahu_saat_dibalas_tanpa_notifikasi_ganda(): void
    {
        $penulis = $this->anggota('Penulis');
        $budi = $this->anggota('Budi');
        $materi = $this->materiMilik($penulis);
        $induk = Komentar::factory()->create(['materi_id' => $materi->id, 'user_id' => $budi->id]);

        $this->sebagai($penulis)
            ->postJson("/api/materi/{$materi->id}/komentar", ['isi' => '<p>Terima kasih.</p>', 'parent_id' => $induk->id])
            ->assertCreated();

        $this->assertSame('balasan', $budi->notifications()->sole()->data['jenis']);
        $this->assertSame(0, $penulis->notifications()->count());

        $indukPenulis = Komentar::factory()->create(['materi_id' => $materi->id, 'user_id' => $penulis->id]);
        $this->sebagai($this->anggota('Citra'))
            ->postJson("/api/materi/{$materi->id}/komentar", ['isi' => '<p>Setuju.</p>', 'parent_id' => $indukPenulis->id])
            ->assertCreated();

        $this->assertSame(1, $penulis->notifications()->count());
        $this->assertSame('balasan', $penulis->notifications()->sole()->data['jenis']);
    }

    public function test_anggota_mengubah_profil_dan_foto_sendiri_tanpa_mengubah_email(): void
    {
        Storage::fake('public');
        $user = $this->anggota('Nama Lama');
        $emailAwal = $user->email;

        $this->sebagai($user)->post('/api/profil', [
            'nama' => 'Nama Baru',
            'no_hp' => '081200000000',
            'alamat' => 'Alamat baru',
            'email' => 'coba-ganti@contoh.test',
            'foto' => UploadedFile::fake()->image('foto.jpg'),
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.nama', 'Nama Baru')
            ->assertJsonPath('data.email', $emailAwal)
            ->assertJsonPath('data.anggota.no_hp', '081200000000');

        Storage::disk('public')->assertExists((string) $user->anggota()->value('foto'));
    }

    public function test_profil_wajib_diisi_dan_butuh_login(): void
    {
        $this->postJson('/api/profil', [])->assertUnauthorized();

        $this->sebagai($this->anggota())->postJson('/api/profil', ['nama' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nama', 'no_hp', 'alamat']);
    }

    public function test_ganti_kata_sandi_butuh_kata_sandi_lama_dan_mencabut_sesi_lain(): void
    {
        $user = $this->anggota();
        $user->createToken('perangkat-lain');

        $this->sebagai($user)->putJson('/api/profil/password', [
            'password_lama' => 'salah',
            'password' => 'sandibaru123',
            'password_confirmation' => 'sandibaru123',
        ])->assertStatus(422)->assertJsonPath('errors.password_lama.0', 'Kata sandi lama belum cocok.');

        $this->sebagai($user)->putJson('/api/profil/password', [
            'password_lama' => 'password',
            'password' => 'sandibaru123',
            'password_confirmation' => 'sandibaru123',
        ])->assertOk();

        $this->assertTrue(Hash::check('sandibaru123', $user->fresh()->password));
        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_membuka_materi_menambah_jumlah_dibaca_tanpa_mengubah_waktu_diperbarui(): void
    {
        $materi = $this->materiMilik($this->anggota());
        $diperbarui = $materi->updated_at;

        $this->travel(1)->hours();
        $this->sebagai($this->anggota('Pembaca'))->getJson("/api/materi/{$materi->slug}")->assertOk();

        $materi->refresh();
        $this->assertSame(1, $materi->jumlah_dibaca);
        $this->assertTrue($materi->updated_at->equalTo($diperbarui));
    }

    public function test_admin_melihat_statistik_dan_anggota_ditolak(): void
    {
        $budi = $this->anggota('Budi');
        $populer = $this->materiMilik($budi);
        $populer->forceFill(['jumlah_dibaca' => 10])->save();
        Komentar::factory()->count(2)->create(['materi_id' => $populer->id, 'user_id' => $this->anggota('Citra')->id]);

        $this->sebagai($budi)->getJson('/api/admin/statistik')->assertForbidden();

        $this->sebagai(User::factory()->admin()->create())->getJson('/api/admin/statistik')
            ->assertOk()
            ->assertJsonPath('data.materi_terpopuler.0.id', $populer->id)
            ->assertJsonPath('data.materi_terpopuler.0.jumlah_dibaca', 10)
            ->assertJsonPath('data.anggota_teraktif.0.nama', 'Citra')
            ->assertJsonPath('data.anggota_teraktif.0.total', 2)
            ->assertJsonPath('data.anggota_teraktif.1.nama', 'Budi');
    }
}
