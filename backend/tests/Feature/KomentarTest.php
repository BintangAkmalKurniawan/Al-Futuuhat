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

// Forum Diskusi & Siaran Real-time
class KomentarTest extends TestCase
{
    use RefreshDatabase;

    private function token(User $user): string
    {
        return $user->createToken('api')->plainTextToken;
    }

    private function materiTerbit(User $penulis): Materi
    {
        $materi = Materi::factory()->terbit()->create(['penulis_id' => $penulis->id]);
        RevisiMateri::factory()->disetujui()->create([
            'materi_id' => $materi->id,
            'nomor_versi' => 1,
        ]);

        return $materi;
    }

    // KP-14.2, KP-15.1
    public function test_anggota_dapat_mengirim_komentar(): void
    {
        Event::fake([KomentarDibuat::class]);

        $anggota = User::factory()->create(['nama' => 'Hasan Basri']);
        $materi = $this->materiTerbit(User::factory()->create());

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

        Event::assertDispatched(KomentarDibuat::class);
    }

    // KP-14.2
    public function test_komentar_tanpa_teks_dan_gambar_ditolak(): void
    {
        $anggota = User::factory()->create();
        $materi = $this->materiTerbit(User::factory()->create());

        $this->withToken($this->token($anggota))
            ->postJson("/api/materi/{$materi->id}/komentar", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('isi');
    }

    public function test_komentar_rich_text_disaringkan_sebelum_disimpan(): void
    {
        $anggota = User::factory()->create();
        $materi = $this->materiTerbit(User::factory()->create());

        $respons = $this->withToken($this->token($anggota))
            ->postJson("/api/materi/{$materi->id}/komentar", [
                'isi' => '<p>Contoh <strong>tebal</strong> dan <em>miring</em>.</p>'
                    .'<script>alert(1)</script><img src="x" onerror="alert(2)">',
            ])
            ->assertCreated();

        $tersimpan = $respons->json('data.isi');

        $this->assertStringContainsString('<strong>tebal</strong>', $tersimpan);
        $this->assertStringContainsString('<em>miring</em>', $tersimpan);
        $this->assertStringNotContainsString('<script', $tersimpan);
        $this->assertStringNotContainsString('onerror', $tersimpan);
    }

    public function test_komentar_dengan_html_kosong_ditolak(): void
    {
        $anggota = User::factory()->create();
        $materi = $this->materiTerbit(User::factory()->create());

        $this->withToken($this->token($anggota))
            ->postJson("/api/materi/{$materi->id}/komentar", ['isi' => '<p><br></p>'])
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

        $this->withToken($token)->post("/api/materi/{$materi->id}/komentar", [
            'gambar' => UploadedFile::fake()->image('besar.jpg')->size(3000),
        ])->assertStatus(422)->assertJsonValidationErrors('gambar');
    }

    // KP-14.3
    public function test_balasan_ditempatkan_satu_tingkat_di_bawah_induk(): void
    {
        $materi = $this->materiTerbit(User::factory()->create());
        $induk = Komentar::factory()->create(['materi_id' => $materi->id]);
        $token = $this->token(User::factory()->create());

        $balasanId = $this->withToken($token)
            ->postJson("/api/materi/{$materi->id}/komentar", [
                'isi' => 'Balasan pertama.',
                'parent_id' => $induk->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.parent_id', $induk->id)
            ->json('data.id');

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
        $lain = Komentar::factory()->create();

        $this->withToken($this->token(User::factory()->create()))
            ->postJson("/api/materi/{$materi->id}/komentar", [
                'isi' => 'Salah tempat.',
                'parent_id' => $lain->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('parent_id');
    }

    // KP-14.1, KP-14.5
    public function test_daftar_komentar_hanya_memuat_induk_dan_jumlah_balasan(): void
    {
        $materi = $this->materiTerbit(User::factory()->create());
        $induk = Komentar::factory()->create(['materi_id' => $materi->id, 'isi' => 'Pertanyaan.']);
        Komentar::factory()->balasan($induk)->create(['isi' => 'Jawaban.']);
        Komentar::factory()->balasan($induk)->create(['isi' => 'Jawaban kedua.']);

        $this->withToken($this->token(User::factory()->create()))
            ->getJson("/api/materi/{$materi->id}/komentar")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.isi', 'Pertanyaan.')
            ->assertJsonPath('data.0.jumlah_balasan', 2)
            ->assertJsonMissingPath('data.0.balasan');
    }

    // KP-14.5
    public function test_balasan_satu_induk_diambil_terpisah(): void
    {
        $materi = $this->materiTerbit(User::factory()->create());
        $induk = Komentar::factory()->create(['materi_id' => $materi->id]);
        $lain = Komentar::factory()->create(['materi_id' => $materi->id]);

        Komentar::factory()->balasan($induk)->create(['isi' => 'Jawaban.']);
        Komentar::factory()->balasan($lain)->create(['isi' => 'Bukan milik induk ini.']);

        $this->withToken($this->token(User::factory()->create()))
            ->getJson("/api/komentar/{$induk->id}/balasan")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.isi', 'Jawaban.')
            ->assertJsonPath('data.0.parent_id', $induk->id);
    }

    // KP-14.3
    public function test_balasan_dari_sebuah_balasan_tidak_ada(): void
    {
        $materi = $this->materiTerbit(User::factory()->create());
        $induk = Komentar::factory()->create(['materi_id' => $materi->id]);
        $balasan = Komentar::factory()->balasan($induk)->create();

        $this->withToken($this->token(User::factory()->create()))
            ->getJson("/api/komentar/{$balasan->id}/balasan")
            ->assertNotFound();
    }

    // KP-14.1
    public function test_balasan_perlu_login(): void
    {
        $materi = $this->materiTerbit(User::factory()->create());
        $induk = Komentar::factory()->create(['materi_id' => $materi->id]);

        $this->getJson("/api/komentar/{$induk->id}/balasan")->assertUnauthorized();
    }

    // KP-14.4, KP-15.2
    public function test_pemilik_menghapus_komentar_dan_balasannya_ikut_terhapus(): void
    {
        Event::fake([KomentarDihapus::class]);

        $pemilik = User::factory()->create();
        $materi = $this->materiTerbit(User::factory()->create());
        $induk = Komentar::factory()->create(['materi_id' => $materi->id, 'user_id' => $pemilik->id]);
        $balasan = Komentar::factory()->balasan($induk)->create();

        $this->withToken($this->token($pemilik))
            ->deleteJson("/api/komentar/{$induk->id}")
            ->assertOk();

        $this->assertDatabaseMissing('komentar', ['id' => $induk->id]);
        $this->assertDatabaseMissing('komentar', ['id' => $balasan->id]);

        Event::assertDispatched(KomentarDihapus::class);
    }

    // KP-14.4
    public function test_bukan_pemilik_tidak_dapat_menghapus_komentar(): void
    {
        $materi = $this->materiTerbit(User::factory()->create());
        $komentar = Komentar::factory()->create(['materi_id' => $materi->id]);

        $this->withToken($this->token(User::factory()->create()))
            ->deleteJson("/api/komentar/{$komentar->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('komentar', ['id' => $komentar->id]);
    }

    // KP-14.1
    public function test_forum_tersedia_untuk_materi_yang_belum_terbit(): void
    {
        $anggota = User::factory()->create();
        $materi = Materi::factory()->create(['status' => Materi::STATUS_DIAJUKAN]);

        $this->withToken($this->token($anggota))
            ->postJson("/api/materi/{$materi->id}/komentar", ['isi' => 'Bagian ini perlu contoh.'])
            ->assertCreated();

        $this->withToken($this->token($anggota))
            ->getJson("/api/materi/{$materi->id}/komentar")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.isi', 'Bagian ini perlu contoh.');
    }
}