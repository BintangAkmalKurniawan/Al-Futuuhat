<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\Divisi;
use App\Models\Jabatan;
use App\Models\Kepengurusan;
use App\Models\PeriodeKepengurusan;
use App\Models\RiwayatStruktur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

// Struktur organisasi
class StrukturTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::create(2026, 6, 1, 9));
        $this->admin = User::factory()->admin()->create(['nama' => 'Admin Ketua']);
    }

    private function sebagai(User $user): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($user->createToken('api')->plainTextToken);
    }

    private function anggotaAktif(string $nama): Anggota
    {
        return Anggota::factory()->create(['user_id' => User::factory()->create(['nama' => $nama])->id]);
    }

    private function periode(int $tahun, bool $aktif = false, ?string $nama = null): PeriodeKepengurusan
    {
        return PeriodeKepengurusan::factory()->create([
            'nama_periode' => $nama ?? (string) $tahun,
            'tahun_mulai' => $tahun,
            'tahun_selesai' => $tahun,
            'is_aktif' => $aktif,
        ]);
    }

    private function pengurus(PeriodeKepengurusan $periode, Anggota $anggota, Jabatan $jabatan, ?Divisi $divisi = null): Kepengurusan
    {
        return Kepengurusan::factory()->create([
            'periode_id' => $periode->id,
            'anggota_id' => $anggota->id,
            'jabatan_id' => $jabatan->id,
            'divisi_id' => $divisi?->id,
        ]);
    }

    // KP-07.2, KP-07.5

    public function test_crud_divisi_dan_jabatan(): void
    {
        $divisi = $this->sebagai($this->admin)->postJson('/api/admin/divisi', [
            'nama_divisi' => 'Kaderisasi',
            'deskripsi' => 'Pembinaan anggota baru.',
        ])->assertCreated()->json('data');

        $this->sebagai($this->admin)->putJson("/api/admin/divisi/{$divisi['id']}", [
            'nama_divisi' => 'Kaderisasi & Pembinaan',
            'deskripsi' => 'Pembinaan anggota baru.',
        ])->assertOk();

        $jabatan = $this->sebagai($this->admin)->postJson('/api/admin/jabatan', [
            'nama_jabatan' => 'Sekretaris',
            'urutan' => 2,
        ])->assertCreated()->json('data');

        $this->assertSame(2, $jabatan['urutan']);

        $this->sebagai($this->admin)->deleteJson("/api/admin/jabatan/{$jabatan['id']}")->assertOk();
        $this->sebagai($this->admin)->deleteJson("/api/admin/divisi/{$divisi['id']}")->assertOk();
        $this->assertDatabaseMissing('divisi', ['id' => $divisi['id']]);
    }

    public function test_divisi_dan_jabatan_yang_masih_dipakai_tidak_dapat_dihapus(): void
    {
        $kepengurusan = Kepengurusan::factory()->create();

        $this->sebagai($this->admin)->deleteJson("/api/admin/divisi/{$kepengurusan->divisi_id}")
            ->assertStatus(422)->assertJsonValidationErrors('divisi');

        $this->sebagai($this->admin)->deleteJson("/api/admin/jabatan/{$kepengurusan->jabatan_id}")
            ->assertStatus(422)->assertJsonValidationErrors('jabatan');
    }

    // KP-07.1
    public function test_rute_kelola_periode_manual_sudah_tidak_ada(): void
    {
        $this->sebagai($this->admin)->postJson('/api/admin/periode', ['nama_periode' => '2030'])->assertNotFound();
    }

    // KP-07.1, KP-07.3

    public function test_perubahan_pertama_tanpa_periode_membuat_periode_tahun_berjalan(): void
    {
        $jabatan = Jabatan::factory()->create(['nama_jabatan' => 'Ketua', 'urutan' => 1]);

        $this->sebagai($this->admin)->postJson('/api/admin/kepengurusan', [
            'anggota_id' => $this->anggotaAktif('Ahmad')->id,
            'jabatan_id' => $jabatan->id,
        ])->assertCreated();

        $periode = PeriodeKepengurusan::sole();
        $this->assertSame('2026', $periode->nama_periode);
        $this->assertTrue($periode->is_aktif);

        $this->assertSame(
            [RiwayatStruktur::AKSI_PERIODE_BARU, RiwayatStruktur::AKSI_TAMBAH],
            $periode->riwayat()->orderBy('id')->pluck('aksi')->all()
        );
        $this->assertSame('Periode 2026 dimulai otomatis.', $periode->riwayat()->orderBy('id')->first()->keterangan);
    }

    public function test_menambah_pengurus_inti_tercatat_pada_periode_aktif_dan_riwayat(): void
    {
        $aktif = $this->periode(2026, true, '2026/2027');
        $jabatan = Jabatan::factory()->create(['nama_jabatan' => 'Ketua', 'urutan' => 1]);

        $this->sebagai($this->admin)->postJson('/api/admin/kepengurusan', [
            'anggota_id' => $this->anggotaAktif('Ketua Umum')->id,
            'jabatan_id' => $jabatan->id,
            'divisi_id' => null,
        ])->assertCreated()
            ->assertJsonPath('data.nama', 'Ketua Umum')
            ->assertJsonPath('data.periode_id', $aktif->id);

        $this->assertSame(1, PeriodeKepengurusan::count());

        $riwayat = $aktif->riwayat()->sole();
        $this->assertSame(RiwayatStruktur::AKSI_TAMBAH, $riwayat->aksi);
        $this->assertSame($this->admin->id, $riwayat->admin_id);
        $this->assertSame('Menambahkan Ketua Umum sebagai Ketua (pengurus inti).', $riwayat->keterangan);
        $this->assertEquals(['nama' => 'Ketua Umum', 'jabatan' => 'Ketua', 'divisi' => null], $riwayat->data_sesudah);
    }

    public function test_pergantian_tahun_membuat_periode_baru_dan_menyalin_susunan(): void
    {
        $lama = $this->periode(2026, true, '2026/2027');
        $ketua = Jabatan::factory()->create(['nama_jabatan' => 'Ketua', 'urutan' => 1]);
        $koordinator = Jabatan::factory()->create(['nama_jabatan' => 'Koordinator', 'urutan' => 2]);
        $media = Divisi::factory()->create(['nama_divisi' => 'Media']);

        $this->pengurus($lama, $this->anggotaAktif('Ahmad'), $ketua);
        $baris = $this->pengurus($lama, $this->anggotaAktif('Siti'), $koordinator, $media);

        $this->travelTo(Carbon::create(2027, 1, 5, 8));

        $this->sebagai($this->admin)->putJson("/api/admin/kepengurusan/{$baris->id}", [
            'anggota_id' => $baris->anggota_id,
            'jabatan_id' => $ketua->id,
            'divisi_id' => null,
        ])->assertOk();

        $baru = PeriodeKepengurusan::where('is_aktif', true)->sole();
        $this->assertSame('2027', $baru->nama_periode);
        $this->assertFalse($lama->fresh()->is_aktif);

        $this->assertSame(2, $baru->kepengurusan()->count());
        $this->assertSame($koordinator->id, $baris->fresh()->jabatan_id);
        $this->assertSame($ketua->id, $baru->kepengurusan()->where('anggota_id', $baris->anggota_id)->value('jabatan_id'));

        $this->assertSame(
            'Periode 2027 dimulai otomatis; 2 pengurus disalin dari periode 2026/2027.',
            $baru->riwayat()->where('aksi', RiwayatStruktur::AKSI_PERIODE_BARU)->value('keterangan')
        );
        $this->assertSame(
            'Mengubah jabatan Siti dari Koordinator Divisi Media menjadi Ketua (pengurus inti).',
            $baru->riwayat()->where('aksi', RiwayatStruktur::AKSI_UBAH)->value('keterangan')
        );
    }

    // KP-07.6

    public function test_periode_lama_hanya_dapat_dibaca(): void
    {
        $this->periode(2026, true);
        $lama = $this->periode(2025);
        $jabatan = Jabatan::factory()->create();
        $baris = $this->pengurus($lama, $this->anggotaAktif('Arsip'), $jabatan);

        $this->sebagai($this->admin)->putJson("/api/admin/kepengurusan/{$baris->id}", [
            'anggota_id' => $baris->anggota_id,
            'jabatan_id' => $jabatan->id,
        ])->assertStatus(422)->assertJsonPath('errors.periode.0', 'Susunan pengurus periode lama tidak dapat diubah.');

        $this->sebagai($this->admin)->deleteJson("/api/admin/kepengurusan/{$baris->id}")
            ->assertStatus(422)->assertJsonPath('errors.periode.0', 'Susunan pengurus periode lama tidak dapat diubah.');

        $this->assertDatabaseHas('kepengurusan', ['id' => $baris->id]);
    }

    // KP-07.3, KP-07.4

    public function test_anggota_yang_sama_tidak_boleh_dua_kali_pada_periode_yang_sama(): void
    {
        $aktif = $this->periode(2026, true);
        $jabatan = Jabatan::factory()->create();
        $anggota = $this->anggotaAktif('Anggota Ganda');
        $this->pengurus($aktif, $anggota, $jabatan);

        $this->sebagai($this->admin)->postJson('/api/admin/kepengurusan', [
            'anggota_id' => $anggota->id,
            'jabatan_id' => $jabatan->id,
        ])->assertStatus(422)->assertJsonPath('errors.anggota_id.0', 'Anggota ini sudah memiliki jabatan pada periode ini.');
    }

    public function test_pengurus_hanya_boleh_dipilih_dari_anggota_aktif(): void
    {
        $anggota = Anggota::factory()->create(['user_id' => User::factory()->nonaktif()->create()->id]);

        $this->sebagai($this->admin)->postJson('/api/admin/kepengurusan', [
            'anggota_id' => $anggota->id,
            'jabatan_id' => Jabatan::factory()->create()->id,
        ])->assertStatus(422)->assertJsonValidationErrors('anggota_id');

        $this->assertSame(0, PeriodeKepengurusan::count());
    }

    // KP-07.7

    public function test_hapus_tercatat_dan_ubah_tanpa_perbedaan_tidak_dicatat(): void
    {
        $aktif = $this->periode(2026, true);
        $jabatan = Jabatan::factory()->create(['nama_jabatan' => 'Bendahara']);
        $baris = $this->pengurus($aktif, $this->anggotaAktif('Budi'), $jabatan);

        $this->sebagai($this->admin)->putJson("/api/admin/kepengurusan/{$baris->id}", [
            'anggota_id' => $baris->anggota_id,
            'jabatan_id' => $jabatan->id,
        ])->assertOk();

        $this->assertSame(0, $aktif->riwayat()->count());

        $this->sebagai($this->admin)->deleteJson("/api/admin/kepengurusan/{$baris->id}")->assertOk();

        $this->assertSame(
            'Menghapus Budi (Bendahara (pengurus inti)) dari susunan pengurus.',
            $aktif->riwayat()->sole()->keterangan
        );
    }

    public function test_admin_melihat_pengurus_periode_aktif_saja(): void
    {
        $aktif = $this->periode(2026, true, '2026/2027');
        $lama = $this->periode(2025);
        $divisi = Divisi::factory()->create(['nama_divisi' => 'Edukasi']);
        $ketua = Jabatan::factory()->create(['nama_jabatan' => 'Ketua', 'urutan' => 1]);
        $koordinator = Jabatan::factory()->create(['nama_jabatan' => 'Koordinator', 'urutan' => 2]);

        $this->pengurus($aktif, $this->anggotaAktif('Koordinator Edukasi'), $koordinator, $divisi);
        $this->pengurus($aktif, $this->anggotaAktif('Ketua Umum'), $ketua);
        $this->pengurus($lama, $this->anggotaAktif('Pengurus Lama'), $ketua);

        $this->sebagai($this->admin)->getJson('/api/admin/kepengurusan')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.nama', 'Ketua Umum')
            ->assertJsonPath('data.1.divisi.nama_divisi', 'Edukasi')
            ->assertJsonPath('periode.nama_periode', '2026/2027')
            ->assertJsonPath('periode.akan_berganti', false);

        $this->sebagai(User::factory()->create())->getJson('/api/admin/kepengurusan')->assertForbidden();
    }

    // KP-01.2, KP-01.3

    public function test_pengunjung_hanya_melihat_struktur_periode_saat_ini(): void
    {
        $this->getJson('/api/publik/struktur')->assertOk()->assertJsonPath('data', null);

        $aktif = $this->periode(2026, true);
        $lama = $this->periode(2025);

        $this->getJson('/api/publik/struktur')
            ->assertOk()
            ->assertJsonPath('data.periode.id', $aktif->id);

        $this->getJson('/api/periode')->assertUnauthorized();
        $this->getJson("/api/periode/{$lama->id}/struktur")->assertUnauthorized();
        $this->getJson("/api/periode/{$aktif->id}/riwayat")->assertUnauthorized();
    }

    // KP-01.4
    public function test_anggota_melihat_semua_periode_dan_struktur_periode_lama(): void
    {
        $aktif = $this->periode(2026, true);
        $lama = $this->periode(2025);
        $divisi = Divisi::factory()->create(['nama_divisi' => 'Edukasi']);
        $ketua = Jabatan::factory()->create(['nama_jabatan' => 'Ketua', 'urutan' => 1]);
        $koordinator = Jabatan::factory()->create(['nama_jabatan' => 'Koordinator', 'urutan' => 2]);
        $staf = Jabatan::factory()->create(['nama_jabatan' => 'Staf', 'urutan' => 3]);

        $this->pengurus($lama, $this->anggotaAktif('Staf Edukasi'), $staf, $divisi);
        $this->pengurus($lama, $this->anggotaAktif('Ketua Lama'), $ketua);
        $this->pengurus($lama, $this->anggotaAktif('Koordinator Edukasi'), $koordinator, $divisi);

        $anggota = User::factory()->create();

        $this->sebagai($anggota)->getJson('/api/periode')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $aktif->id);

        $this->sebagai($anggota)->getJson("/api/periode/{$lama->id}/struktur")
            ->assertOk()
            ->assertJsonPath('data.periode.is_aktif', false)
            ->assertJsonPath('data.inti.0.nama', 'Ketua Lama')
            ->assertJsonPath('data.divisi.0.pengurus.0.nama', 'Koordinator Edukasi')
            ->assertJsonPath('data.divisi.0.pengurus.1.nama', 'Staf Edukasi');
    }

    // KP-01.5
    public function test_struktur_publik_tidak_memuat_data_pribadi(): void
    {
        $periode = $this->periode(2026, true);
        $anggota = Anggota::factory()->create([
            'user_id' => User::factory()->create(['email' => 'rahasia@alfutuuhat.test'])->id,
            'no_hp' => '081299998888',
            'alamat' => 'Jl. Rahasia No. 9',
            'alasan_bergabung' => 'Alasan pribadi.',
            'foto' => 'foto-anggota/rahasia.jpg',
        ]);
        $this->pengurus($periode, $anggota, Jabatan::factory()->create(), Divisi::factory()->create());

        $isi = (string) $this->getJson('/api/publik/struktur')->assertOk()->getContent();

        foreach (['rahasia@alfutuuhat.test', '081299998888', 'Jl. Rahasia No. 9', 'Alasan pribadi.', 'foto-anggota'] as $terlarang) {
            $this->assertStringNotContainsString($terlarang, $isi);
        }
    }

    // KP-07.8

    public function test_riwayat_berisi_perbandingan_dengan_periode_sebelumnya_dan_log(): void
    {
        $lama = $this->periode(2025, false, '2025');
        $baru = $this->periode(2026, true, '2026');
        $ketua = Jabatan::factory()->create(['nama_jabatan' => 'Ketua', 'urutan' => 1]);
        $koordinator = Jabatan::factory()->create(['nama_jabatan' => 'Koordinator', 'urutan' => 2]);
        $edukasi = Divisi::factory()->create(['nama_divisi' => 'Edukasi']);
        $media = Divisi::factory()->create(['nama_divisi' => 'Media']);

        $ahmad = $this->anggotaAktif('Ahmad');
        $budi = $this->anggotaAktif('Budi');
        $citra = $this->anggotaAktif('Citra');
        $dewi = $this->anggotaAktif('Dewi');

        $this->pengurus($lama, $ahmad, $ketua);
        $this->pengurus($lama, $budi, $koordinator, $edukasi);
        $this->pengurus($lama, $citra, $koordinator, $media);
        $this->pengurus($baru, $ahmad, $ketua);
        $this->pengurus($baru, $budi, $koordinator, $media);
        $this->pengurus($baru, $dewi, $koordinator, $edukasi);

        $baru->riwayat()->create([
            'admin_id' => $this->admin->id, 'aksi' => 'tambah', 'keterangan' => 'Catatan pertama.',
        ]);
        $this->travel(5)->minutes();
        $baru->riwayat()->create([
            'admin_id' => $this->admin->id, 'aksi' => 'hapus', 'keterangan' => 'Catatan kedua.',
        ]);

        $this->sebagai(User::factory()->create())->getJson("/api/periode/{$baru->id}/riwayat")
            ->assertOk()
            ->assertJsonPath('data.periode_sebelumnya.id', $lama->id)
            ->assertJsonPath('data.perbandingan.masuk', [['nama' => 'Dewi', 'posisi' => 'Koordinator Divisi Edukasi']])
            ->assertJsonPath('data.perbandingan.keluar', [['nama' => 'Citra', 'posisi' => 'Koordinator Divisi Media']])
            ->assertJsonPath('data.perbandingan.pindah', [[
                'nama' => 'Budi',
                'dari' => 'Koordinator Divisi Edukasi',
                'menjadi' => 'Koordinator Divisi Media',
            ]])
            ->assertJsonPath('data.log.0.keterangan', 'Catatan kedua.')
            ->assertJsonPath('data.log.0.admin.nama', 'Admin Ketua');

        $this->sebagai($this->admin)->getJson("/api/periode/{$lama->id}/riwayat")
            ->assertOk()
            ->assertJsonPath('data.periode_sebelumnya', null)
            ->assertJsonPath('data.perbandingan', null);
    }
}
