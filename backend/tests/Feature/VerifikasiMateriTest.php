<?php

namespace Tests\Feature;

use App\Models\Materi;
use App\Models\RevisiMateri;
use App\Models\User;
use App\Models\VerifikasiMateri;
use App\Notifications\MateriDiajukan;
use App\Notifications\RevisiDiverifikasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F6 — Pengajuan & Verifikasi Publikasi (KF-10, KF-11).
 */
class VerifikasiMateriTest extends TestCase
{
    use RefreshDatabase;

    private function token(User $user): string
    {
        return $user->createToken('api')->plainTextToken;
    }

    /**
     * Materi dengan revisi yang sudah berstatus diajukan.
     *
     * @return array{0: Materi, 1: RevisiMateri}
     */
    private function materiDiajukan(User $penulis): array
    {
        $materi = Materi::factory()->diajukan()->create(['penulis_id' => $penulis->id]);
        $revisi = RevisiMateri::factory()->diajukan()->create([
            'materi_id' => $materi->id,
            'nomor_versi' => 1,
        ]);

        return [$materi, $revisi];
    }

    public function test_penulis_mengajukan_draf_dan_semua_admin_mendapat_notifikasi(): void
    {
        $penulis = User::factory()->create();
        $admin1 = User::factory()->admin()->create();
        $admin2 = User::factory()->admin()->create();
        User::factory()->admin()->nonaktif()->create(); // admin nonaktif tidak dikabari

        $materi = Materi::factory()->create(['penulis_id' => $penulis->id, 'judul' => 'Adab Menuntut Ilmu']);
        $revisi = RevisiMateri::factory()->create(['materi_id' => $materi->id, 'nomor_versi' => 1]);

        // KP-10.1, KP-10.2
        $this->withToken($this->token($penulis))
            ->postJson("/api/materi/{$materi->id}/ajukan")
            ->assertCreated();

        $this->assertSame(RevisiMateri::STATUS_DIAJUKAN, $revisi->fresh()->status);
        $this->assertSame(Materi::STATUS_DIAJUKAN, $materi->fresh()->status);

        // KP-10.3: semua admin aktif menerima notifikasi
        $this->assertSame(1, $admin1->notifications()->count());
        $this->assertSame(1, $admin2->notifications()->count());

        $notifikasi = $admin1->notifications()->first();
        $this->assertSame(MateriDiajukan::class, $notifikasi->type);
        $this->assertSame('Adab Menuntut Ilmu', $notifikasi->data['judul']);
        $this->assertSame($penulis->nama, $notifikasi->data['penulis']);
        $this->assertSame(1, $notifikasi->data['nomor_versi']);
    }

    public function test_bukan_penulis_tidak_dapat_mengajukan(): void
    {
        $penulis = User::factory()->create();
        $lain = User::factory()->create();
        $materi = Materi::factory()->create(['penulis_id' => $penulis->id]);
        RevisiMateri::factory()->create(['materi_id' => $materi->id]);

        // KP-10.1
        $this->withToken($this->token($lain))
            ->postJson("/api/materi/{$materi->id}/ajukan")
            ->assertStatus(403);

        $this->assertSame(Materi::STATUS_DRAF, $materi->fresh()->status);
    }

    public function test_materi_yang_sedang_diajukan_tidak_dapat_diubah(): void
    {
        $penulis = User::factory()->create();
        [$materi] = $this->materiDiajukan($penulis);

        // KP-10.4
        $this->withToken($this->token($penulis))->putJson("/api/materi/{$materi->id}", [
            'judul' => 'Diubah Saat Diajukan',
            'konten' => ['ops' => [['insert' => "x\n"]]],
            'konten_html' => '<p>x</p>',
        ])->assertStatus(422);
    }

    public function test_antrean_admin_berisi_revisi_diajukan_terlama_di_atas(): void
    {
        $admin = User::factory()->admin()->create();
        $penulis = User::factory()->create();

        $baru = RevisiMateri::factory()->diajukan()->create([
            'materi_id' => Materi::factory()->create(['penulis_id' => $penulis->id])->id,
            'created_at' => now()->subHour(),
        ]);
        $lama = RevisiMateri::factory()->diajukan()->create([
            'materi_id' => Materi::factory()->create(['penulis_id' => $penulis->id])->id,
            'created_at' => now()->subDay(),
        ]);
        RevisiMateri::factory()->create(['materi_id' => Materi::factory()->create()->id]); // draf, tidak masuk antrean

        // KP-11.1
        $this->withToken($this->token($admin))->getJson('/api/admin/verifikasi-materi')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $lama->id)
            ->assertJsonPath('data.1.id', $baru->id)
            // ringkasan materi ikut terkirim
            ->assertJsonStructure(['data' => [['materi' => ['id', 'judul', 'penulis' => ['id', 'nama']]]]]);
    }

    public function test_admin_menyetujui_revisi_menerbitkan_materi(): void
    {
        $admin = User::factory()->admin()->create();
        $penulis = User::factory()->create();
        [$materi, $revisi] = $this->materiDiajukan($penulis);
        $materi->update(['diterbitkan_pada' => null]);

        // KP-11.3
        $this->withToken($this->token($admin))->postJson("/api/admin/revisi/{$revisi->id}/verifikasi", [
            'keputusan' => 'disetujui',
        ])->assertOk();

        $this->assertSame(RevisiMateri::STATUS_DISETUJUI, $revisi->fresh()->status);
        $materi->refresh();
        $this->assertSame(Materi::STATUS_TERBIT, $materi->status);
        $this->assertNotNull($materi->diterbitkan_pada);

        // KP-11.5: keputusan tercatat
        $this->assertDatabaseHas('verifikasi_materi', [
            'revisi_id' => $revisi->id,
            'admin_id' => $admin->id,
            'keputusan' => VerifikasiMateri::KEPUTUSAN_DISETUJUI,
        ]);
    }

    public function test_menolak_tanpa_catatan_ditolak_422(): void
    {
        $admin = User::factory()->admin()->create();
        $penulis = User::factory()->create();
        [, $revisi] = $this->materiDiajukan($penulis);

        // KP-11.4
        $this->withToken($this->token($admin))->postJson("/api/admin/revisi/{$revisi->id}/verifikasi", [
            'keputusan' => 'ditolak',
        ])->assertStatus(422)->assertJsonValidationErrors('catatan');
    }

    public function test_menolak_revisi_pertama_membuat_materi_ditolak_dan_penulis_dinotifikasi(): void
    {
        $admin = User::factory()->admin()->create();
        $penulis = User::factory()->create();
        [$materi, $revisi] = $this->materiDiajukan($penulis);

        // KP-11.4, KP-11.5
        $this->withToken($this->token($admin))->postJson("/api/admin/revisi/{$revisi->id}/verifikasi", [
            'keputusan' => 'ditolak',
            'catatan' => 'Perlu tambahan rujukan.',
        ])->assertOk();

        $this->assertSame(RevisiMateri::STATUS_DITOLAK, $revisi->fresh()->status);
        $this->assertSame(Materi::STATUS_DITOLAK, $materi->fresh()->status);

        $this->assertDatabaseHas('verifikasi_materi', [
            'revisi_id' => $revisi->id,
            'keputusan' => VerifikasiMateri::KEPUTUSAN_DITOLAK,
            'catatan' => 'Perlu tambahan rujukan.',
        ]);

        $notifikasi = $penulis->notifications()->first();
        $this->assertSame(RevisiDiverifikasi::class, $notifikasi->type);
        $this->assertSame('ditolak', $notifikasi->data['keputusan']);
        $this->assertSame('Perlu tambahan rujukan.', $notifikasi->data['catatan']);
    }

    public function test_menolak_revisi_materi_yang_sudah_terbit_membuat_materi_tetap_terbit(): void
    {
        $admin = User::factory()->admin()->create();
        $penulis = User::factory()->create();

        $materi = Materi::factory()->terbit()->create(['penulis_id' => $penulis->id]);
        RevisiMateri::factory()->disetujui()->create(['materi_id' => $materi->id, 'nomor_versi' => 1]);
        $v2 = RevisiMateri::factory()->diajukan()->create(['materi_id' => $materi->id, 'nomor_versi' => 2]);

        // KP-11.4: materi tetap terbit dengan versi lama
        $this->withToken($this->token($admin))->postJson("/api/admin/revisi/{$v2->id}/verifikasi", [
            'keputusan' => 'ditolak',
            'catatan' => 'Belum sesuai.',
        ])->assertOk();

        $this->assertSame(Materi::STATUS_TERBIT, $materi->fresh()->status);
    }

    public function test_hanya_revisi_diajukan_yang_dapat_diverifikasi(): void
    {
        $admin = User::factory()->admin()->create();
        $materi = Materi::factory()->create();
        $revisi = RevisiMateri::factory()->create(['materi_id' => $materi->id]); // masih draf

        // KP-11.6
        $this->withToken($this->token($admin))->postJson("/api/admin/revisi/{$revisi->id}/verifikasi", [
            'keputusan' => 'disetujui',
        ])->assertStatus(422);
    }

    public function test_anggota_biasa_tidak_dapat_membuka_antrean_verifikasi(): void
    {
        User::factory()->create();

        // KP-04.5
        $this->withToken($this->token(User::factory()->create()))
            ->getJson('/api/admin/verifikasi-materi')
            ->assertStatus(403);
    }
}