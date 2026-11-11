<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Materi;
use App\Models\RevisiMateri;
use App\Models\User;
use App\Policies\MateriPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Draf 2.6 (KP-08.6) — admin hanya mengelola dan tidak dapat menulis materi,
 * tetapi tetap dapat membaca materi dan berdiskusi di forum.
 */
class AdminTidakMenulisTest extends TestCase
{
    use RefreshDatabase;

    private function sebagai(User $user): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($user->createToken('api')->plainTextToken);
    }

    /**
     * @return array<string, mixed>
     */
    private function dataMateri(): array
    {
        $kategori = Kategori::firstOrCreate(['slug' => 'fiqih'], ['nama' => 'Fiqih']);

        return [
            'judul' => 'Adab Menuntut Ilmu',
            'kategori' => [$kategori->id],
            'konten' => ['ops' => [['insert' => "Isi materi.\n"]]],
            'konten_html' => '<p>Isi materi.</p>',
        ];
    }

    /** Materi draf milik penulis tertentu beserta revisi v1-nya. */
    private function materiDraf(User $penulis): Materi
    {
        $materi = Materi::factory()->create(['penulis_id' => $penulis->id]);
        RevisiMateri::factory()->create(['materi_id' => $materi->id, 'nomor_versi' => 1]);

        return $materi;
    }

    public function test_admin_tidak_dapat_membuat_materi(): void
    {
        $admin = User::factory()->admin()->create();

        $this->sebagai($admin)->postJson('/api/materi', $this->dataMateri())
            ->assertForbidden()
            ->assertJsonPath('message', MateriPolicy::PESAN_ADMIN);

        // Ditolak sebelum validasi: data kosong pun 403, bukan 422.
        $this->sebagai($admin)->postJson('/api/materi', [])->assertForbidden();

        $this->assertDatabaseCount('materi', 0);
    }

    public function test_admin_tidak_punya_materi_saya_dan_tidak_dapat_mengunggah_sampul(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->sebagai($admin)->getJson('/api/materi-saya')->assertForbidden();

        $this->sebagai($admin)
            ->post('/api/unggah-sampul', ['sampul' => UploadedFile::fake()->image('s.jpg')], ['Accept' => 'application/json'])
            ->assertForbidden();
    }

    public function test_materi_anggota_yang_diangkat_menjadi_admin_terkunci_sampai_perannya_dikembalikan(): void
    {
        $penulis = User::factory()->create();
        $materi = $this->materiDraf($penulis);

        // Diangkat menjadi admin: semua aksi penulis ditolak.
        $penulis->update(['role' => User::ROLE_ADMIN]);

        $this->sebagai($penulis)->putJson("/api/materi/{$materi->id}", $this->dataMateri())
            ->assertForbidden()->assertJsonPath('message', MateriPolicy::PESAN_ADMIN);
        $this->sebagai($penulis)->getJson("/api/materi/{$materi->id}/edit")->assertForbidden();
        $this->sebagai($penulis)->postJson("/api/materi/{$materi->id}/ajukan")->assertForbidden();
        $this->sebagai($penulis)->postJson("/api/materi/{$materi->id}/revisi")->assertForbidden();
        $this->sebagai($penulis)->postJson("/api/materi/{$materi->id}/tarik", ['alasan' => 'Perlu diperbarui.'])->assertForbidden();
        $this->sebagai($penulis)->deleteJson("/api/materi/{$materi->id}")->assertForbidden();

        $this->assertDatabaseHas('materi', ['id' => $materi->id]);

        // Peran dikembalikan ke anggota: materinya dapat dikelola lagi.
        $penulis->update(['role' => User::ROLE_ANGGOTA]);

        $this->sebagai($penulis)->putJson("/api/materi/{$materi->id}", $this->dataMateri())->assertOk();
    }

    public function test_admin_tetap_dapat_membaca_materi_berkomentar_dan_mengunggah_gambar_editor(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $materi = $this->materiDraf(User::factory()->create());

        $this->sebagai($admin)->getJson('/api/materi')->assertOk()->assertJsonCount(1, 'data');
        $this->sebagai($admin)->getJson("/api/materi/{$materi->slug}")->assertOk();

        // Forum tetap terbuka untuk admin.
        $this->sebagai($admin)
            ->postJson("/api/materi/{$materi->id}/komentar", ['isi' => 'Arahan dari pengurus.'])
            ->assertCreated();

        // Gambar editor Quill tetap dipakai admin untuk konten beranda dan program.
        $this->sebagai($admin)
            ->post('/api/unggah-gambar', ['gambar' => UploadedFile::fake()->image('g.png')], ['Accept' => 'application/json'])
            ->assertCreated();
    }
}
