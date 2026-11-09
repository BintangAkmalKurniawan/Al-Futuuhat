<?php

namespace Tests\Feature;

use App\Models\Materi;
use App\Models\RevisiMateri;
use App\Models\User;
use App\Models\VerifikasiMateri;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F7 — Revisi & Riwayat Versi (KF-12, KF-13).
 */
class RevisiMateriTest extends TestCase
{
    use RefreshDatabase;

    private function token(User $user): string
    {
        return $user->createToken('api')->plainTextToken;
    }

    /**
     * Materi yang sudah terbit dengan satu versi disetujui.
     *
     * @return array{0: Materi, 1: RevisiMateri}
     */
    private function materiTerbit(User $penulis, string $isi = '<p>Versi pertama.</p>'): array
    {
        $materi = Materi::factory()->terbit()->create([
            'penulis_id' => $penulis->id,
            'judul' => 'Judul Awal',
        ]);

        $v1 = RevisiMateri::factory()->disetujui()->create([
            'materi_id' => $materi->id,
            'nomor_versi' => 1,
            'konten_html' => $isi,
        ]);

        return [$materi, $v1];
    }

    public function test_bukan_penulis_tidak_dapat_membuat_revisi(): void
    {
        $penulis = User::factory()->create();
        [$materi] = $this->materiTerbit($penulis);

        // KP-12.1
        $this->withToken($this->token(User::factory()->create()))
            ->postJson("/api/materi/{$materi->id}/revisi")
            ->assertStatus(403);
    }

    public function test_revisi_baru_ditolak_saat_masih_ada_draf_atau_pengajuan(): void
    {
        $penulis = User::factory()->create();
        $materi = Materi::factory()->create(['penulis_id' => $penulis->id]);
        RevisiMateri::factory()->create(['materi_id' => $materi->id]); // draf

        // KP-12.2
        $this->withToken($this->token($penulis))
            ->postJson("/api/materi/{$materi->id}/revisi")
            ->assertStatus(422);

        RevisiMateri::where('materi_id', $materi->id)->update(['status' => RevisiMateri::STATUS_DIAJUKAN]);

        $this->withToken($this->token($penulis))
            ->postJson("/api/materi/{$materi->id}/revisi")
            ->assertStatus(422);
    }

    public function test_revisi_baru_menambah_nomor_versi_dan_menyalin_isi_terakhir(): void
    {
        $penulis = User::factory()->create();
        [$materi, $v1] = $this->materiTerbit($penulis, '<p>Isi versi satu.</p>');

        // KP-12.3
        $this->withToken($this->token($penulis))
            ->postJson("/api/materi/{$materi->id}/revisi")
            ->assertCreated()
            ->assertJsonPath('data.revisi.nomor_versi', 2)
            ->assertJsonPath('data.revisi.status', 'draf')
            ->assertJsonPath('data.revisi.konten_html', '<p>Isi versi satu.</p>');

        $v2 = RevisiMateri::where('materi_id', $materi->id)->where('nomor_versi', 2)->firstOrFail();
        $this->assertSame($v1->konten, $v2->konten);
    }

    public function test_materi_yang_sudah_terbit_tidak_dapat_mengubah_judul_lewat_revisi(): void
    {
        $penulis = User::factory()->create();
        [$materi, $v1] = $this->materiTerbit($penulis, '<p>Isi versi satu.</p>');
        $token = $this->token($penulis);

        $this->withToken($token)->postJson("/api/materi/{$materi->id}/revisi")->assertCreated();

        // KP-12.4: judul & ringkasan terkunci, hanya isi yang boleh berubah
        $this->withToken($token)->putJson("/api/materi/{$materi->id}", [
            'judul' => 'Judul Ingin Diubah',
            'ringkasan' => 'Ringkasan ingin diubah.',
            'konten' => ['ops' => [['insert' => "Isi versi dua.\n"]]],
            'konten_html' => '<p>Isi versi dua.</p>',
        ])->assertOk();

        $materi->refresh();
        $this->assertSame('Judul Awal', $materi->judul);

        // Versi terbit tetap yang lama sampai revisi baru disetujui.
        $this->withToken($token)->getJson("/api/materi/{$materi->slug}")
            ->assertOk()
            ->assertJsonPath('data.revisi.id', $v1->id)
            ->assertJsonPath('data.revisi.konten_html', '<p>Isi versi satu.</p>');
    }

    public function test_revisi_yang_sudah_disetujui_tidak_dapat_diubah(): void
    {
        $penulis = User::factory()->create();
        [$materi] = $this->materiTerbit($penulis);

        // KP-12.5
        $this->withToken($this->token($penulis))->putJson("/api/materi/{$materi->id}", [
            'judul' => 'Judul Awal',
            'konten' => ['ops' => [['insert' => "x\n"]]],
            'konten_html' => '<p>x</p>',
        ])->assertStatus(422);
    }

    public function test_riwayat_versi_menampilkan_versi_dan_keputusan_admin(): void
    {
        $penulis = User::factory()->create();
        $admin = User::factory()->admin()->create();
        [$materi, $v1] = $this->materiTerbit($penulis);

        $v2 = RevisiMateri::factory()->ditolak()->create([
            'materi_id' => $materi->id,
            'nomor_versi' => 2,
            'catatan_revisi' => 'Menambah contoh.',
        ]);

        VerifikasiMateri::factory()->create([
            'revisi_id' => $v1->id,
            'admin_id' => $admin->id,
        ]);
        VerifikasiMateri::factory()->ditolak()->create([
            'revisi_id' => $v2->id,
            'admin_id' => $admin->id,
        ]);

        // KP-13.1, KP-13.2
        $this->withToken($this->token($penulis))
            ->getJson("/api/materi/{$materi->id}/riwayat")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.nomor_versi', 2)
            ->assertJsonPath('data.0.status', 'ditolak')
            ->assertJsonPath('data.0.catatan_revisi', 'Menambah contoh.')
            ->assertJsonPath('data.0.verifikasi.0.keputusan', 'ditolak')
            ->assertJsonPath('data.0.verifikasi.0.admin.nama', $admin->nama)
            ->assertJsonPath('data.1.nomor_versi', 1)
            ->assertJsonPath('data.1.verifikasi.0.keputusan', 'disetujui');
    }

    public function test_anggota_lain_hanya_melihat_versi_yang_disetujui(): void
    {
        $penulis = User::factory()->create();
        [$materi] = $this->materiTerbit($penulis);

        RevisiMateri::factory()->ditolak()->create([
            'materi_id' => $materi->id,
            'nomor_versi' => 2,
        ]);

        // KP-13.3
        $this->withToken($this->token(User::factory()->create()))
            ->getJson("/api/materi/{$materi->id}/riwayat")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'disetujui');
    }

    public function test_admin_dapat_membandingkan_revisi_dengan_versi_terbit(): void
    {
        $penulis = User::factory()->create();
        $admin = User::factory()->admin()->create();
        [$materi, $v1] = $this->materiTerbit($penulis, '<p>Isi lama.</p>');

        $v2 = RevisiMateri::factory()->diajukan()->create([
            'materi_id' => $materi->id,
            'nomor_versi' => 2,
            'konten_html' => '<p>Isi baru.</p>',
        ]);

        // KP-11.2
        $this->withToken($this->token($admin))
            ->getJson("/api/admin/revisi/{$v2->id}")
            ->assertOk()
            ->assertJsonPath('data.revisi.konten_html', '<p>Isi baru.</p>')
            ->assertJsonPath('data.versi_terbit.id', $v1->id)
            ->assertJsonPath('data.versi_terbit.konten_html', '<p>Isi lama.</p>');
    }

    public function test_materi_yang_ditolak_kembali_menjadi_draf_saat_revisi_baru_dibuat(): void
    {
        $penulis = User::factory()->create();
        $materi = Materi::factory()->create([
            'penulis_id' => $penulis->id,
            'status' => Materi::STATUS_DITOLAK,
        ]);
        RevisiMateri::factory()->ditolak()->create([
            'materi_id' => $materi->id,
            'nomor_versi' => 1,
        ]);

        // PRD §8.2
        $this->withToken($this->token($penulis))
            ->postJson("/api/materi/{$materi->id}/revisi")
            ->assertCreated()
            ->assertJsonPath('data.materi.status', 'draf')
            ->assertJsonPath('data.revisi.nomor_versi', 2);
    }
}