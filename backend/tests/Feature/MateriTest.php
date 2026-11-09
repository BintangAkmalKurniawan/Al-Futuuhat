<?php

namespace Tests\Feature;

use App\Models\Materi;
use App\Models\RevisiMateri;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * F5 — Materi: Tulis & Kelola Draf (KF-08, KF-09).
 */
class MateriTest extends TestCase
{
    use RefreshDatabase;

    private function token(User $user): string
    {
        return $user->createToken('api')->plainTextToken;
    }

    /**
     * Isian editor Quill yang valid.
     *
     * @return array<string, mixed>
     */
    private function dataMateri(array $ubah = []): array
    {
        return array_merge([
            'judul' => 'Fiqih Puasa Ramadhan',
            'ringkasan' => 'Ringkasan materi puasa.',
            'konten' => ['ops' => [['insert' => "Isi materi puasa.\n"]]],
            'konten_html' => '<p>Isi materi puasa.</p>',
        ], $ubah);
    }

    public function test_menyimpan_materi_baru_membuat_materi_dan_revisi_versi_satu(): void
    {
        $penulis = User::factory()->create();
        $token = $this->token($penulis);

        // KP-08.1, KP-08.3
        $respons = $this->withToken($token)->postJson('/api/materi', $this->dataMateri());

        $respons->assertCreated()
            ->assertJsonPath('data.materi.status', 'draf')
            ->assertJsonPath('data.revisi.nomor_versi', 1)
            ->assertJsonPath('data.revisi.status', 'draf');

        $materi = Materi::where('penulis_id', $penulis->id)->firstOrFail();
        $this->assertSame(Materi::STATUS_DRAF, $materi->status);
        $this->assertSame(1, $materi->revisi()->count());
        $this->assertSame($penulis->id, $materi->penulis_id);

        // Delta Quill disimpan sebagai JSONB.
        $revisi = $materi->revisi()->firstOrFail();
        $this->assertIsArray($revisi->konten);
    }

    public function test_konten_html_disanitasi_sebelum_disimpan(): void
    {
        $token = $this->token(User::factory()->create());

        // KP-08.4: <script> dan atribut berbahaya dibuang
        $this->withToken($token)->postJson('/api/materi', $this->dataMateri([
            'judul' => 'Materi Berbahaya',
            'konten_html' => '<script>alert("xss")</script><p onclick="curi()">Isi aman <strong>tebal</strong></p>',
        ]))->assertCreated();

        $revisi = RevisiMateri::firstOrFail();
        $this->assertStringNotContainsString('<script', $revisi->konten_html);
        $this->assertStringNotContainsString('onclick', $revisi->konten_html);
        $this->assertStringContainsString('<strong>tebal</strong>', $revisi->konten_html);
    }

    public function test_unggah_gambar_editor_menyimpan_berkas_dan_mengembalikan_url(): void
    {
        Storage::fake('public');
        $token = $this->token(User::factory()->create());

        // KP-08.2: URL server, bukan base64
        $respons = $this->withToken($token)->post('/api/unggah-gambar', [
            'gambar' => UploadedFile::fake()->image('grafik.jpg', 400, 300),
        ]);

        $respons->assertCreated();
        $this->assertStringContainsString('gambar-materi/', $respons->json('url'));
        $this->assertCount(1, Storage::disk('public')->allFiles('gambar-materi'));
    }

    public function test_unggah_gambar_3mb_atau_gif_ditolak(): void
    {
        Storage::fake('public');
        $token = $this->token(User::factory()->create());

        // KP-08.2, KNF-03
        $this->withToken($token)->post('/api/unggah-gambar', [
            'gambar' => UploadedFile::fake()->image('besar.jpg')->size(3000),
        ])->assertStatus(422)->assertJsonValidationErrors('gambar');

        $this->withToken($token)->post('/api/unggah-gambar', [
            'gambar' => UploadedFile::fake()->image('animasi.gif'),
        ])->assertStatus(422)->assertJsonValidationErrors('gambar');
    }

    public function test_penulis_dapat_mengubah_drafnya_sendiri(): void
    {
        $penulis = User::factory()->create();
        $token = $this->token($penulis);

        $id = $this->withToken($token)->postJson('/api/materi', $this->dataMateri())
            ->json('data.materi.id');

        // KP-09.1
        $this->withToken($token)->putJson("/api/materi/{$id}", $this->dataMateri([
            'judul' => 'Fiqih Puasa (Revisi Judul)',
            'konten_html' => '<p>Isi yang sudah diperbaiki.</p>',
        ]))->assertOk();

        $materi = Materi::findOrFail($id);
        $this->assertSame('Fiqih Puasa (Revisi Judul)', $materi->judul);
        $this->assertSame('<p>Isi yang sudah diperbaiki.</p>', $materi->revisi()->first()->konten_html);
        $this->assertSame(1, $materi->revisi()->count(), 'Mengubah draf tidak membuat versi baru.');
    }

    public function test_penulis_dapat_menghapus_draf_yang_belum_pernah_terbit(): void
    {
        $penulis = User::factory()->create();
        $token = $this->token($penulis);

        $id = $this->withToken($token)->postJson('/api/materi', $this->dataMateri())
            ->json('data.materi.id');

        // KP-09.2
        $this->withToken($token)->deleteJson("/api/materi/{$id}")->assertOk();
        $this->assertDatabaseMissing('materi', ['id' => $id]);
        $this->assertDatabaseMissing('revisi_materi', ['materi_id' => $id]);
    }

    public function test_materi_yang_sudah_terbit_tidak_dapat_dihapus(): void
    {
        $penulis = User::factory()->create();
        $materi = Materi::factory()->terbit()->create(['penulis_id' => $penulis->id]);
        RevisiMateri::factory()->disetujui()->create(['materi_id' => $materi->id]);

        // KP-09.2
        $this->withToken($this->token($penulis))
            ->deleteJson("/api/materi/{$materi->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('materi', ['id' => $materi->id]);
    }

    public function test_anggota_lain_tidak_dapat_mengubah_atau_menghapus_materi(): void
    {
        $penulis = User::factory()->create();
        $lain = User::factory()->create();
        $materi = Materi::factory()->create(['penulis_id' => $penulis->id]);
        RevisiMateri::factory()->create(['materi_id' => $materi->id]);

        $token = $this->token($lain);

        // KP-09.3
        $this->withToken($token)->putJson("/api/materi/{$materi->id}", $this->dataMateri())
            ->assertStatus(403);

        $this->withToken($token)->deleteJson("/api/materi/{$materi->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('materi', ['id' => $materi->id]);
    }

    public function test_materi_saya_hanya_menampilkan_materi_milik_pengguna(): void
    {
        $saya = User::factory()->create();
        Materi::factory()->create(['penulis_id' => $saya->id, 'judul' => 'Punya Saya']);
        Materi::factory()->create(['judul' => 'Punya Orang Lain']);

        $this->withToken($this->token($saya))->getJson('/api/materi-saya')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Punya Saya');
    }
}