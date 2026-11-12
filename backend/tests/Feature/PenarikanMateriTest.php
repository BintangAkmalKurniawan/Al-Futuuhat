<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Materi;
use App\Models\PenarikanMateri;
use App\Models\RevisiMateri;
use App\Models\User;
use App\Notifications\PenarikanDiajukan;
use App\Notifications\PenarikanDiputuskan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

// Penarikan materi
class PenarikanMateriTest extends TestCase
{
    use RefreshDatabase;

    private function sebagai(User $user): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($user->createToken('api')->plainTextToken);
    }

    private function kategori(string $nama): Kategori
    {
        return Kategori::create(['nama' => $nama, 'slug' => strtolower($nama)]);
    }

    private function materiTerbit(User $penulis, Kategori $kategori, string $judul = 'Adab Menuntut Ilmu'): Materi
    {
        $materi = Materi::factory()->terbit()->create([
            'penulis_id' => $penulis->id,
            'judul' => $judul,
            'slug' => str($judul)->slug()->toString(),
        ]);

        $v1 = RevisiMateri::factory()->disetujui()->create([
            'materi_id' => $materi->id,
            'nomor_versi' => 1,
            'konten_html' => '<p>Isi versi publik.</p>',
        ]);

        $materi->kategori()->attach($kategori->id);
        $v1->kategori()->attach($kategori->id);

        return $materi;
    }

    /**
     * @return array<string, mixed>
     */
    private function dataBaru(Kategori $kategori, string $judul = 'Adab Penuntut Ilmu (Revisi)'): array
    {
        return [
            'judul' => $judul,
            'ringkasan' => 'Ringkasan versi baru.',
            'kategori' => [$kategori->id],
            'konten' => ['ops' => [['insert' => "Isi versi baru.\n"]]],
            'konten_html' => '<p>Isi versi baru.</p>',
        ];
    }

    private function tarik(User $penulis, Materi $materi, string $alasan = 'Ada bagian yang perlu diperbarui.'): TestResponse
    {
        return $this->sebagai($penulis)->postJson("/api/materi/{$materi->id}/tarik", ['alasan' => $alasan]);
    }

    // KP-09.1, KP-12.3, KP-12.4, KP-16.2, KP-16.4, KP-17.2
    public function test_revisi_materi_terbit_mengubah_semua_bagian_tanpa_mengubah_versi_publik(): void
    {
        $penulis = User::factory()->create();
        $fiqih = $this->kategori('Fiqih');
        $akhlak = $this->kategori('Akhlak');
        $materi = $this->materiTerbit($penulis, $fiqih);

        $this->sebagai($penulis)->postJson("/api/materi/{$materi->id}/revisi")
            ->assertCreated()
            ->assertJsonPath('data.revisi.judul', 'Adab Menuntut Ilmu')
            ->assertJsonPath('data.kategori_ids', [$fiqih->id]);

        $this->sebagai($penulis)->putJson("/api/materi/{$materi->id}", $this->dataBaru($akhlak))->assertOk();

        $this->sebagai($penulis)->getJson("/api/materi/{$materi->slug}")
            ->assertOk()
            ->assertJsonPath('data.judul', 'Adab Penuntut Ilmu (Revisi)')
            ->assertJsonPath('data.ringkasan', 'Ringkasan versi baru.')
            ->assertJsonPath('data.kategori.0.slug', 'akhlak')
            ->assertJsonPath('data.versi_publik.nomor_versi', 1);

        $this->getJson("/api/publik/materi/{$materi->slug}")
            ->assertOk()
            ->assertJsonPath('data.judul', 'Adab Menuntut Ilmu')
            ->assertJsonPath('data.kategori.0.slug', 'fiqih')
            ->assertJsonPath('data.konten_html', '<p>Isi versi publik.</p>');
    }

    // KP-13.4
    public function test_isi_versi_lama_memuat_judul_dan_kategori_versi_itu(): void
    {
        $penulis = User::factory()->create();
        $fiqih = $this->kategori('Fiqih');
        $akhlak = $this->kategori('Akhlak');
        $materi = $this->materiTerbit($penulis, $fiqih);
        $v1 = $materi->revisiTerakhir()->first();

        $this->sebagai($penulis)->postJson("/api/materi/{$materi->id}/revisi")->assertCreated();
        $this->sebagai($penulis)->putJson("/api/materi/{$materi->id}", $this->dataBaru($akhlak))->assertOk();

        $this->sebagai($penulis)->getJson("/api/materi/{$materi->id}/riwayat/{$v1->id}")
            ->assertOk()
            ->assertJsonPath('data.judul', 'Adab Menuntut Ilmu')
            ->assertJsonPath('data.kategori.0.slug', 'fiqih')
            ->assertJsonPath('data.konten_html', '<p>Isi versi publik.</p>');
    }

    // KP-16.1, KP-17.5
    public function test_pencarian_internal_memakai_judul_revisi_terakhir_sedangkan_publik_tidak(): void
    {
        $penulis = User::factory()->create();
        $fiqih = $this->kategori('Fiqih');
        $materi = $this->materiTerbit($penulis, $fiqih);

        $this->sebagai($penulis)->postJson("/api/materi/{$materi->id}/revisi")->assertCreated();
        $this->sebagai($penulis)->putJson(
            "/api/materi/{$materi->id}",
            $this->dataBaru($fiqih, 'Panduan Tholabul Ilmi')
        )->assertOk();

        $this->sebagai($penulis)->getJson('/api/materi?q=tholabul')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/publik/materi?q=tholabul')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    // KP-19.1
    public function test_kategori_yang_hanya_dipakai_versi_lama_tidak_dapat_dihapus(): void
    {
        $admin = User::factory()->admin()->create();
        $penulis = User::factory()->create();
        $fiqih = $this->kategori('Fiqih');
        $akhlak = $this->kategori('Akhlak');
        $materi = $this->materiTerbit($penulis, $fiqih);

        $materi->kategori()->sync([$akhlak->id]);

        $this->sebagai($admin)->deleteJson("/api/admin/kategori/{$fiqih->id}")
            ->assertUnprocessable()
            ->assertJsonPath('errors.kategori.0', 'Kategori ini masih dipakai oleh materi sehingga belum dapat dihapus.');
    }

    // KP-10.1
    public function test_ajukan_publikasi_saat_materi_terbit_ditolak(): void
    {
        $penulis = User::factory()->create();
        $materi = $this->materiTerbit($penulis, $this->kategori('Fiqih'));

        $this->sebagai($penulis)->postJson("/api/materi/{$materi->id}/revisi")->assertCreated();

        $this->sebagai($penulis)->postJson("/api/materi/{$materi->id}/ajukan")
            ->assertUnprocessable()
            ->assertJsonPath('errors.materi.0', 'Materi ini masih tayang. Turunkan dulu dari halaman umum, baru ajukan versi barunya ya.');
    }

    // KP-10.6
    public function test_penulis_dapat_membatalkan_pengajuan_publikasi(): void
    {
        $penulis = User::factory()->create();
        $lain = User::factory()->create();
        $materi = Materi::factory()->diajukan()->create(['penulis_id' => $penulis->id]);
        $revisi = RevisiMateri::factory()->diajukan()->create(['materi_id' => $materi->id, 'nomor_versi' => 1]);

        $this->sebagai($lain)->postJson("/api/materi/{$materi->id}/batal-ajukan")->assertForbidden();

        $this->sebagai($penulis)->postJson("/api/materi/{$materi->id}/batal-ajukan")->assertOk();

        $this->assertSame(RevisiMateri::STATUS_DRAF, $revisi->fresh()->status);
        $this->assertSame(Materi::STATUS_DRAF, $materi->fresh()->status);

        $this->sebagai($penulis)->postJson("/api/materi/{$materi->id}/batal-ajukan")->assertUnprocessable();
    }

    // KP-20.1, KP-20.2
    public function test_penulis_mengajukan_penarikan_dan_semua_admin_mendapat_notifikasi(): void
    {
        $penulis = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $materi = $this->materiTerbit($penulis, $this->kategori('Fiqih'));

        $this->tarik($penulis, $materi)
            ->assertCreated()
            ->assertJsonPath('data.status', PenarikanMateri::STATUS_DIAJUKAN)
            ->assertJsonPath('data.alasan', 'Ada bagian yang perlu diperbarui.');

        $this->assertSame(1, $admin->notifications()->count());
        $this->assertSame(PenarikanDiajukan::class, $admin->notifications()->first()->type);

        $this->assertSame(Materi::STATUS_TERBIT, $materi->fresh()->status);
        $this->getJson("/api/publik/materi/{$materi->slug}")->assertOk();
    }

    public function test_pengajuan_penarikan_yang_tidak_sah_ditolak_422(): void
    {
        $penulis = User::factory()->create();
        $materi = $this->materiTerbit($penulis, $this->kategori('Fiqih'));
        $draf = Materi::factory()->create(['penulis_id' => $penulis->id]);

        $this->tarik($penulis, $materi, '')
            ->assertUnprocessable()
            ->assertJsonPath('errors.alasan.0', 'Tulis dulu alasannya ya.');

        $this->tarik($penulis, $draf)->assertUnprocessable();

        $this->tarik($penulis, $materi)->assertCreated();
        $this->tarik($penulis, $materi)->assertUnprocessable();
    }

    // KP-20.1
    public function test_bukan_penulis_tidak_dapat_mengajukan_atau_membatalkan_penarikan(): void
    {
        $penulis = User::factory()->create();
        $lain = User::factory()->create();
        $materi = $this->materiTerbit($penulis, $this->kategori('Fiqih'));

        $this->tarik($lain, $materi, '')->assertForbidden();

        $this->tarik($penulis, $materi)->assertCreated();

        $this->sebagai($lain)->postJson("/api/materi/{$materi->id}/batal-tarik")->assertForbidden();
    }

    // KP-20.3
    public function test_penulis_dapat_membatalkan_penarikan(): void
    {
        $penulis = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $materi = $this->materiTerbit($penulis, $this->kategori('Fiqih'));

        $this->tarik($penulis, $materi)->assertCreated();

        $this->sebagai($penulis)->postJson("/api/materi/{$materi->id}/batal-tarik")
            ->assertOk()
            ->assertJsonPath('data.status', PenarikanMateri::STATUS_DIBATALKAN);

        $this->sebagai($admin)->getJson('/api/admin/penarikan-materi')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->sebagai($penulis)->postJson("/api/materi/{$materi->id}/batal-tarik")->assertUnprocessable();
    }

    // KP-20.4
    public function test_antrean_penarikan_admin_terlama_di_atas(): void
    {
        $admin = User::factory()->admin()->create();
        $anggota = User::factory()->create();
        $fiqih = $this->kategori('Fiqih');

        $lama = $this->materiTerbit(User::factory()->create(), $fiqih, 'Materi Lama');
        $baru = $this->materiTerbit(User::factory()->create(), $fiqih, 'Materi Baru');

        $lama->penarikan()->create(['alasan' => 'Alasan lama.', 'status' => PenarikanMateri::STATUS_DIAJUKAN])
            ->forceFill(['created_at' => now()->subDay()])->save();
        $baru->penarikan()->create(['alasan' => 'Alasan baru.', 'status' => PenarikanMateri::STATUS_DIAJUKAN]);

        $this->sebagai($admin)->getJson('/api/admin/penarikan-materi')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.materi.judul', 'Materi Lama')
            ->assertJsonPath('data.0.alasan', 'Alasan lama.')
            ->assertJsonPath('data.1.materi.judul', 'Materi Baru');

        $this->sebagai($anggota)->getJson('/api/admin/penarikan-materi')->assertForbidden();
    }

    // KP-09.2, KP-10.7, KP-17.3, KP-20.5
    public function test_admin_menyetujui_penarikan_materi_hilang_dari_publik(): void
    {
        $penulis = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $fiqih = $this->kategori('Fiqih');
        $akhlak = $this->kategori('Akhlak');
        $materi = $this->materiTerbit($penulis, $fiqih);
        $slugPublik = $materi->slug;
        $v1 = $materi->revisiTerakhir()->first();

        $this->sebagai($penulis)->postJson("/api/materi/{$materi->id}/revisi")->assertCreated();
        $this->sebagai($penulis)->putJson("/api/materi/{$materi->id}", $this->dataBaru($akhlak))->assertOk();
        $this->tarik($penulis, $materi)->assertCreated();

        $penarikan = $materi->penarikanMenunggu()->first();

        $this->sebagai($admin)->postJson("/api/admin/penarikan/{$penarikan->id}/verifikasi", [
            'keputusan' => 'disetujui',
        ])->assertOk();

        $materi->refresh();
        $this->assertSame(Materi::STATUS_DRAF, $materi->status);
        $this->assertNotNull($materi->diterbitkan_pada);
        $this->assertSame(PenarikanMateri::STATUS_DISETUJUI, $penarikan->fresh()->status);
        $this->assertSame($admin->id, $penarikan->fresh()->admin_id);

        $this->assertSame('Adab Penuntut Ilmu (Revisi)', $materi->judul);
        $this->assertSame([$akhlak->id], $materi->kategori()->pluck('kategori.id')->all());

        $this->assertSame(RevisiMateri::STATUS_DISETUJUI, $v1->fresh()->status);
        $this->assertSame('Adab Menuntut Ilmu', $v1->fresh()->judul);

        $this->getJson("/api/publik/materi/{$slugPublik}")->assertNotFound();
        $this->getJson("/api/publik/materi/{$materi->slug}")->assertNotFound();

        $notifikasi = $penulis->notifications()->first();
        $this->assertSame(PenarikanDiputuskan::class, $notifikasi->type);
        $this->assertSame('disetujui', $notifikasi->data['keputusan']);

        $this->sebagai($penulis)->getJson("/api/materi/{$materi->id}/edit")
            ->assertOk()
            ->assertJsonPath('data.versi_publik_terakhir.nomor_versi', 1)
            ->assertJsonPath('data.materi.versi_publik', null);

        $this->sebagai($penulis)->deleteJson("/api/materi/{$materi->id}")->assertUnprocessable();
    }

    // KP-20.6, KP-20.7
    public function test_admin_menolak_penarikan_materi_tetap_terbit(): void
    {
        $penulis = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $materi = $this->materiTerbit($penulis, $this->kategori('Fiqih'));

        $this->tarik($penulis, $materi)->assertCreated();
        $penarikan = $materi->penarikanMenunggu()->first();

        $this->sebagai($admin)->postJson("/api/admin/penarikan/{$penarikan->id}/verifikasi", [
            'keputusan' => 'ditolak',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.catatan.0', 'Mohon isi catatan untuk penulis.');

        $this->sebagai($admin)->postJson("/api/admin/penarikan/{$penarikan->id}/verifikasi", [
            'keputusan' => 'ditolak',
            'catatan' => 'Perbaiki lewat revisi berikutnya saja.',
        ])->assertOk();

        $this->assertSame(Materi::STATUS_TERBIT, $materi->fresh()->status);
        $this->assertSame(PenarikanMateri::STATUS_DITOLAK, $penarikan->fresh()->status);
        $this->getJson("/api/publik/materi/{$materi->slug}")->assertOk();

        $this->assertSame('Perbaiki lewat revisi berikutnya saja.', $penulis->notifications()->first()->data['catatan']);

        $this->sebagai($admin)->postJson("/api/admin/penarikan/{$penarikan->id}/verifikasi", [
            'keputusan' => 'disetujui',
        ])->assertUnprocessable();
    }

    // KP-20.7
    public function test_penarikan_yang_sudah_dibatalkan_tidak_dapat_diputuskan(): void
    {
        $penulis = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $materi = $this->materiTerbit($penulis, $this->kategori('Fiqih'));

        $this->tarik($penulis, $materi)->assertCreated();
        $penarikan = $materi->penarikanMenunggu()->first();
        $this->sebagai($penulis)->postJson("/api/materi/{$materi->id}/batal-tarik")->assertOk();

        $this->sebagai($admin)->postJson("/api/admin/penarikan/{$penarikan->id}/verifikasi", [
            'keputusan' => 'disetujui',
        ])->assertUnprocessable();
    }

    // KP-20.7
    public function test_alasan_penarikan_hanya_untuk_penulis_dan_admin(): void
    {
        $penulis = User::factory()->create();
        $lain = User::factory()->create();
        $materi = $this->materiTerbit($penulis, $this->kategori('Fiqih'));

        $this->tarik($penulis, $materi)->assertCreated();

        $this->sebagai($penulis)->getJson("/api/materi/{$materi->slug}")
            ->assertOk()
            ->assertJsonPath('data.penarikan.status', PenarikanMateri::STATUS_DIAJUKAN)
            ->assertJsonPath('data.penarikan.alasan', 'Ada bagian yang perlu diperbarui.');

        $respons = $this->sebagai($lain)->getJson("/api/materi/{$materi->slug}")
            ->assertOk()
            ->assertJsonPath('data.penarikan.status', PenarikanMateri::STATUS_DIAJUKAN);

        $this->assertArrayNotHasKey('alasan', $respons->json('data.penarikan'));
        $this->assertArrayNotHasKey('catatan', $respons->json('data.penarikan'));
    }

    // KP-10.1, KP-11.3
    public function test_setelah_ditarik_materi_dapat_diajukan_dan_terbit_dengan_data_revisi_baru(): void
    {
        $penulis = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $fiqih = $this->kategori('Fiqih');
        $akhlak = $this->kategori('Akhlak');
        $materi = $this->materiTerbit($penulis, $fiqih);

        $this->sebagai($penulis)->postJson("/api/materi/{$materi->id}/revisi")->assertCreated();
        $this->sebagai($penulis)->putJson("/api/materi/{$materi->id}", $this->dataBaru($akhlak))->assertOk();
        $this->tarik($penulis, $materi)->assertCreated();
        $penarikan = $materi->penarikanMenunggu()->first();
        $this->sebagai($admin)->postJson("/api/admin/penarikan/{$penarikan->id}/verifikasi", [
            'keputusan' => 'disetujui',
        ])->assertOk();

        $this->sebagai($penulis)->postJson("/api/materi/{$materi->id}/ajukan")->assertCreated();
        $v2 = $materi->revisiTerakhir()->first();

        $this->sebagai($admin)->postJson("/api/admin/revisi/{$v2->id}/verifikasi", [
            'keputusan' => 'disetujui',
        ])->assertOk();

        $materi->refresh();
        $this->assertSame(Materi::STATUS_TERBIT, $materi->status);

        $this->getJson("/api/publik/materi/{$materi->slug}")
            ->assertOk()
            ->assertJsonPath('data.judul', 'Adab Penuntut Ilmu (Revisi)')
            ->assertJsonPath('data.kategori.0.slug', 'akhlak')
            ->assertJsonPath('data.konten_html', '<p>Isi versi baru.</p>');
    }

    public function test_dasbor_admin_menghitung_penarikan_yang_menunggu(): void
    {
        $penulis = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $materi = $this->materiTerbit($penulis, $this->kategori('Fiqih'));

        $this->tarik($penulis, $materi)->assertCreated();

        $this->sebagai($admin)->getJson('/api/admin/dasbor')
            ->assertOk()
            ->assertJsonPath('data.penarikan_diajukan', 1);
    }
}
