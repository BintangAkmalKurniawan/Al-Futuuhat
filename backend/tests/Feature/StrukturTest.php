<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\Divisi;
use App\Models\Jabatan;
use App\Models\Kepengurusan;
use App\Models\PeriodeKepengurusan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F4 — Struktur Organisasi: admin + beranda publik (KF-01, KF-07).
 */
class StrukturTest extends TestCase
{
    use RefreshDatabase;

    private function tokenAdmin(): string
    {
        return User::factory()->admin()->create()->createToken('api')->plainTextToken;
    }

    /**
     * Membuat anggota aktif dan mengembalikan baris `anggota`-nya.
     */
    private function anggotaAktif(string $nama): Anggota
    {
        $user = User::factory()->create(['nama' => $nama]);

        return Anggota::factory()->create(['user_id' => $user->id]);
    }

    public function test_mengaktifkan_periode_lain_menonaktifkan_periode_sebelumnya(): void
    {
        $token = $this->tokenAdmin();
        $lama = PeriodeKepengurusan::factory()->aktif()->create();
        $baru = PeriodeKepengurusan::factory()->create();

        // KP-07.1
        $this->withToken($token)->patchJson("/api/admin/periode/{$baru->id}/aktifkan")->assertOk();

        $this->assertFalse($lama->fresh()->is_aktif);
        $this->assertTrue($baru->fresh()->is_aktif);
        $this->assertSame(1, PeriodeKepengurusan::where('is_aktif', true)->count());
    }

    public function test_crud_divisi_dan_jabatan(): void
    {
        $token = $this->tokenAdmin();

        // KP-07.2: divisi
        $divisi = $this->withToken($token)->postJson('/api/admin/divisi', [
            'nama_divisi' => 'Kaderisasi',
            'deskripsi' => 'Pembinaan anggota baru.',
        ])->assertCreated()->json('data');

        $this->withToken($token)->putJson("/api/admin/divisi/{$divisi['id']}", [
            'nama_divisi' => 'Kaderisasi & Pembinaan',
            'deskripsi' => 'Pembinaan anggota baru.',
        ])->assertOk();

        // KP-07.2: jabatan dengan urutan
        $jabatan = $this->withToken($token)->postJson('/api/admin/jabatan', [
            'nama_jabatan' => 'Sekretaris',
            'urutan' => 2,
        ])->assertCreated()->json('data');

        $this->assertSame(2, $jabatan['urutan']);

        $this->withToken($token)->getJson('/api/admin/jabatan')
            ->assertOk()
            ->assertJsonFragment(['nama_jabatan' => 'Sekretaris', 'urutan' => 2]);

        $this->withToken($token)->deleteJson("/api/admin/jabatan/{$jabatan['id']}")->assertOk();
        $this->assertDatabaseMissing('jabatan', ['id' => $jabatan['id']]);

        $this->withToken($token)->deleteJson("/api/admin/divisi/{$divisi['id']}")->assertOk();
        $this->assertDatabaseMissing('divisi', ['id' => $divisi['id']]);
    }

    public function test_menambah_pengurus_inti_dengan_divisi_kosong(): void
    {
        $token = $this->tokenAdmin();
        $periode = PeriodeKepengurusan::factory()->create();
        $jabatan = Jabatan::factory()->create(['nama_jabatan' => 'Ketua', 'urutan' => 1]);
        $anggota = $this->anggotaAktif('Ketua Umum');

        // KP-07.3
        $this->withToken($token)->postJson('/api/admin/kepengurusan', [
            'periode_id' => $periode->id,
            'jabatan_id' => $jabatan->id,
            'anggota_id' => $anggota->id,
            'divisi_id' => null,
        ])->assertCreated()->assertJsonPath('data.nama', 'Ketua Umum');
    }

    public function test_anggota_yang_sama_tidak_boleh_dua_kali_pada_periode_yang_sama(): void
    {
        $token = $this->tokenAdmin();
        $periode = PeriodeKepengurusan::factory()->create();
        $jabatan = Jabatan::factory()->create();
        $anggota = $this->anggotaAktif('Anggota Ganda');

        Kepengurusan::factory()->create([
            'periode_id' => $periode->id,
            'jabatan_id' => $jabatan->id,
            'anggota_id' => $anggota->id,
            'divisi_id' => null,
        ]);

        // KP-07.4
        $this->withToken($token)->postJson('/api/admin/kepengurusan', [
            'periode_id' => $periode->id,
            'jabatan_id' => $jabatan->id,
            'anggota_id' => $anggota->id,
            'divisi_id' => null,
        ])->assertStatus(422)->assertJsonValidationErrors('anggota_id');
    }

    public function test_pengurus_hanya_boleh_dipilih_dari_anggota_aktif(): void
    {
        $token = $this->tokenAdmin();
        $periode = PeriodeKepengurusan::factory()->create();
        $jabatan = Jabatan::factory()->create();
        $anggota = Anggota::factory()->create([
            'user_id' => User::factory()->nonaktif()->create()->id,
        ]);

        // KP-07.3
        $this->withToken($token)->postJson('/api/admin/kepengurusan', [
            'periode_id' => $periode->id,
            'jabatan_id' => $jabatan->id,
            'anggota_id' => $anggota->id,
        ])->assertStatus(422)->assertJsonValidationErrors('anggota_id');
    }

    public function test_data_yang_masih_dipakai_tidak_dapat_dihapus_dengan_pesan_jelas(): void
    {
        $token = $this->tokenAdmin();
        $kepengurusan = Kepengurusan::factory()->create();

        // KP-07.5: bukan error 500, tetapi 422 dengan pesan.
        $this->withToken($token)->deleteJson("/api/admin/divisi/{$kepengurusan->divisi_id}")
            ->assertStatus(422)
            ->assertJsonValidationErrors('divisi');

        $this->withToken($token)->deleteJson("/api/admin/jabatan/{$kepengurusan->jabatan_id}")
            ->assertStatus(422)
            ->assertJsonValidationErrors('jabatan');

        $this->withToken($token)->deleteJson("/api/admin/periode/{$kepengurusan->periode_id}")
            ->assertStatus(422)
            ->assertJsonValidationErrors('periode');
    }

    // ------------------------------------------------------- Tampilan publik

    public function test_publik_dapat_melihat_divisi_dan_periode_tanpa_login(): void
    {
        $aktif = PeriodeKepengurusan::factory()->aktif()->create(['nama_periode' => '2026/2027']);
        PeriodeKepengurusan::factory()->create(['nama_periode' => '2025/2026']);
        Divisi::factory()->create(['nama_divisi' => 'Edukasi']);

        // KP-01.1
        $this->getJson('/api/divisi')->assertOk()->assertJsonFragment(['nama_divisi' => 'Edukasi']);

        // KP-01.2: periode aktif tampil pertama
        $this->getJson('/api/periode')
            ->assertOk()
            ->assertJsonPath('data.0.id', $aktif->id)
            ->assertJsonPath('data.0.is_aktif', true);
    }

    public function test_struktur_public_mengelompokkan_pengurus_inti_dan_per_divisi_sesuai_urutan(): void
    {
        $periode = PeriodeKepengurusan::factory()->aktif()->create();
        $divisi = Divisi::factory()->create(['nama_divisi' => 'Edukasi']);
        $ketua = Jabatan::factory()->create(['nama_jabatan' => 'Ketua', 'urutan' => 1]);
        $koordinator = Jabatan::factory()->create(['nama_jabatan' => 'Koordinator', 'urutan' => 2]);
        $anggotaDivisi = Jabatan::factory()->create(['nama_jabatan' => 'Anggota Divisi', 'urutan' => 3]);

        // Sengaja dibuat tidak berurutan untuk menguji pengurutan.
        Kepengurusan::factory()->create([
            'periode_id' => $periode->id, 'divisi_id' => $divisi->id,
            'jabatan_id' => $anggotaDivisi->id, 'anggota_id' => $this->anggotaAktif('Anggota Edukasi')->id,
        ]);
        Kepengurusan::factory()->create([
            'periode_id' => $periode->id, 'divisi_id' => null,
            'jabatan_id' => $ketua->id, 'anggota_id' => $this->anggotaAktif('Ketua Umum')->id,
        ]);
        Kepengurusan::factory()->create([
            'periode_id' => $periode->id, 'divisi_id' => $divisi->id,
            'jabatan_id' => $koordinator->id, 'anggota_id' => $this->anggotaAktif('Koordinator Edukasi')->id,
        ]);

        // KP-01.3, KP-01.4
        $respons = $this->getJson("/api/periode/{$periode->id}/struktur");

        $respons->assertOk()
            ->assertJsonPath('data.periode.id', $periode->id)
            ->assertJsonCount(1, 'data.inti')
            ->assertJsonPath('data.inti.0.nama', 'Ketua Umum')
            ->assertJsonPath('data.inti.0.jabatan', 'Ketua')
            ->assertJsonPath('data.inti.0.divisi', null)
            // urutan jabatan 2 lebih dulu daripada 3
            ->assertJsonPath('data.divisi.0.pengurus.0.nama', 'Koordinator Edukasi')
            ->assertJsonPath('data.divisi.0.pengurus.1.nama', 'Anggota Edukasi');
    }

    public function test_respons_struktur_publik_tidak_memuat_data_pribadi(): void
    {
        $periode = PeriodeKepengurusan::factory()->create();
        $divisi = Divisi::factory()->create();
        $jabatan = Jabatan::factory()->create(['urutan' => 1]);
        $anggota = Anggota::factory()->create([
            'user_id' => User::factory()->create(['email' => 'rahasia@alfutuuhat.test'])->id,
            'no_hp' => '081299998888',
            'alamat' => 'Jl. Rahasia No. 9',
            'alasan_bergabung' => 'Alasan pribadi.',
            'foto' => 'foto-anggota/rahasia.jpg',
        ]);

        Kepengurusan::factory()->create([
            'periode_id' => $periode->id,
            'divisi_id' => $divisi->id,
            'jabatan_id' => $jabatan->id,
            'anggota_id' => $anggota->id,
        ]);

        // KP-01.5, KNF-02
        $isi = $this->getJson("/api/periode/{$periode->id}/struktur")->assertOk()->getContent();

        foreach (['rahasia@alfutuuhat.test', '081299998888', 'Jl. Rahasia No. 9', 'Alasan pribadi.', 'foto-anggota'] as $terlarang) {
            $this->assertStringNotContainsString($terlarang, $isi);
        }
    }
}