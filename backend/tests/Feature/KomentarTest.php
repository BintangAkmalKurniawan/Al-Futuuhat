<?php

namespace Tests\Feature;

use App\Events\KomentarDibuat;
use App\Events\KomentarDihapus;
use App\Models\Komentar;
use App\Models\Materi;
use App\Models\RevisiMateri;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * F9 — Forum Diskusi & Siaran Real-time (KF-14, KF-15).
 */
class KomentarTest extends TestCase
{
    use RefreshDatabase;

    private function token(User $user): string
    {
        return $user->createToken('api')->plainTextToken;
    }

    /** Materi terbit yang siap diberi komentar. */
    private function materiTerbit(User $penulis): Materi
    {
        $materi = Materi::factory()->terbit()->create(['penulis_id' => $penulis->id]);
        RevisiMateri::factory()->disetujui()->create([
            'materi_id' => $materi->id,
            'nomor_versi' => 1,
        ]);

        return $materi;
    }

    public function test_anggota_dapat_mengirim_komentar(): void
    {
        Event::fake([KomentarDibuat::class]);

        $anggota = User::factory()->create(['nama' => 'Hasan Basri']);
        $materi = $this->materiTerbit(User::factory()->create());

        // KP-14.2
        $this->withToken($this->token($anggota))
            ->postJson("/api/materi/{$materi->id}/komentar", ['isi' => 'Masya Allah, bermanfaat.'])
            ->assertCreated()
            ->assertJsonPath('data.isi', 'Masya Allah, bermanfaat.')
            ->assertJsonPath('data.user.nama', 'Hasan Basri')
            ->assertJsonPath('data.parent_id', null);

        $this->assertDatabaseHas('komentar', [
            'materi_id' => $materi->id,
            'user_id' => $anggota->id,
            'isi' => 'Masya Allah, bermanfaat.',
        ]);

        // KP-15.1: komentar baru disiarkan real-time
        Event::assertDispatched(KomentarDibuat::class);
    }

    public function test_komentar_tanpa_teks_dan_gambar_ditolak(): void
    {
        $anggota = User::factory()->create();
        $materi = $this->materiTerbit(User::factory()->create());

        // KP-14.2
        $this->withToken($this->token($anggota))
            ->postJson("/api/materi/{$materi->id}/komentar", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('isi');
    }

    public function test_komentar_dapat_berupa_gambar_saja(): void
    {
        Storage::fake('public');
        $anggota = User::factory()->create();
        $materi = $this->materiTerbit(User::factory()->create());
        $token = $this->token($anggota);

        $respons = $this->withToken($token)->post("/api/materi/{$materi->id}/komentar", [
            'gambar' => UploadedFile::fake()->image('catatan.jpg', 300, 200),
        ]);

        $respons->assertCreated();
        $this->assertStringContainsString('gambar-komentar/', $respons->json('data.gambar'));
        $this->assertCount(1, Storage::disk('public')->allFiles('gambar-komentar'));

        // KNF-03: maksimal 2 MB
        $this->withToken($token)->post("/api/materi/{$materi->id}/komentar", [
            'gambar' => UploadedFile::fake()->image('besar.jpg')->size(3000),
        ])->assertStatus(422)->assertJsonValidationErrors('gambar');
    }

    public function test_balasan_ditempatkan_satu_tingkat_di_bawah_induk(): void
    {
        $materi = $this->materiTerbit(User::factory()->create());
        $induk = Komentar::factory()->create(['materi_id' => $materi->id]);
        $token = $this->token(User::factory()->create());

        // KP-14.3: balasan langsung
        $balasanId = $this->withToken($token)
            ->postJson("/api/materi/{$materi->id}/komentar", [
                'isi' => 'Balasan pertama.',
                'parent_id' => $induk->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.parent_id', $induk->id)
            ->json('data.id');

        // KP-14.3: balasan atas balasan diratakan ke induk yang sama
        $this->withToken($token)
            ->postJson("/api/materi/{$materi->id}/komentar", [
                'isi' => 'Balasan kedua.',
                'parent_id' => $balasanId,
            ])
            ->assertCreated()
            ->assertJsonPath('data.parent_id', $induk->id);
    }

    public function test_komentar_induk_dari_materi_lain_ditolak(): void
    {
        $materi = $this->materiTerbit(User::factory()->create());
        $lain = Komentar::factory()->create(); // menempel pada materi lain

        $this->withToken($this->token(User::factory()->create()))
            ->postJson("/api/materi/{$materi->id}/komentar", [
                'isi' => 'Salah tempat.',
                'parent_id' => $lain->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('parent_id');
    }

    public function test_daftar_komentar_menampilkan_induk_beserta_balasannya(): void
    {
        $materi = $this->materiTerbit(User::factory()->create());
        $induk = Komentar::factory()->create(['materi_id' => $materi->id, 'isi' => 'Pertanyaan.']);
        Komentar::factory()->balasan($induk)->create(['isi' => 'Jawaban.']);

        // KP-14.1
        $this->withToken($this->token(User::factory()->create()))
            ->getJson("/api/materi/{$materi->id}/komentar")
            ->assertOk()
            ->assertJsonCount(1, 'data') // hanya induk di daftar utama
            ->assertJsonPath('data.0.isi', 'Pertanyaan.')
            ->assertJsonCount(1, 'data.0.balasan')
            ->assertJsonPath('data.0.balasan.0.isi', 'Jawaban.');
    }

    public function test_pemilik_menghapus_komentar_dan_balasannya_ikut_terhapus(): void
    {
        Event::fake([KomentarDihapus::class]);

        $pemilik = User::factory()->create();
        $materi = $this->materiTerbit(User::factory()->create());
        $induk = Komentar::factory()->create(['materi_id' => $materi->id, 'user_id' => $pemilik->id]);
        $balasan = Komentar::factory()->balasan($induk)->create();

        // KP-14.4
        $this->withToken($this->token($pemilik))
            ->deleteJson("/api/komentar/{$induk->id}")
            ->assertOk();

        $this->assertDatabaseMissing('komentar', ['id' => $induk->id]);
        $this->assertDatabaseMissing('komentar', ['id' => $balasan->id]);

        // KP-15.2: penghapusan disiarkan real-time
        Event::assertDispatched(KomentarDihapus::class);
    }

    public function test_bukan_pemilik_tidak_dapat_menghapus_komentar(): void
    {
        $materi = $this->materiTerbit(User::factory()->create());
        $komentar = Komentar::factory()->create(['materi_id' => $materi->id]);

        // KP-14.4
        $this->withToken($this->token(User::factory()->create()))
            ->deleteJson("/api/komentar/{$komentar->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('komentar', ['id' => $komentar->id]);
    }

    public function test_forum_tidak_tersedia_untuk_materi_yang_belum_terbit(): void
    {
        $anggota = User::factory()->create();
        $materi = Materi::factory()->create(['status' => Materi::STATUS_DIAJUKAN]);

        // KP-14.1
        $this->withToken($this->token($anggota))
            ->getJson("/api/materi/{$materi->id}/komentar")
            ->assertStatus(404);

        $this->withToken($this->token($anggota))
            ->postJson("/api/materi/{$materi->id}/komentar", ['isi' => 'Halo.'])
            ->assertStatus(404);
    }
}