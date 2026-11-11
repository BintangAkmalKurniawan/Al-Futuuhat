<?php

namespace Tests\Feature;

use App\Models\DokumentasiProgram;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * KF-21 — dokumentasi program: foto, video MP4, dan tautan YouTube
 * (KP-21.3, KP-21.4, KP-21.6).
 */
class DokumentasiProgramTest extends TestCase
{
    use RefreshDatabase;

    private function sebagai(User $user): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($user->createToken('api')->plainTextToken);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function unggah(User $admin, Program $program, array $data): \Illuminate\Testing\TestResponse
    {
        return $this->sebagai($admin)->post(
            "/api/admin/program/{$program->id}/dokumentasi",
            $data,
            ['Accept' => 'application/json']
        );
    }

    // ------------------------------------------------------------ KP-21.3

    public function test_admin_mengunggah_foto_video_dan_tautan_youtube(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $program = Program::factory()->create();

        $foto = $this->unggah($admin, $program, [
            'jenis' => 'foto',
            'berkas' => UploadedFile::fake()->image('kajian.jpg'),
            'keterangan' => 'Suasana kajian pekan pertama',
        ])->assertCreated()
            ->assertJsonPath('data.jenis', 'foto')
            ->assertJsonPath('data.keterangan', 'Suasana kajian pekan pertama');

        $video = $this->unggah($admin, $program, [
            'jenis' => 'video',
            'berkas' => UploadedFile::fake()->create('kajian.mp4', 30 * 1024, 'video/mp4'),
        ])->assertCreated()
            ->assertJsonPath('data.jenis', 'video')
            ->assertJsonPath('data.embed', null);

        $this->unggah($admin, $program, [
            'jenis' => 'youtube',
            'tautan' => 'https://youtu.be/dQw4w9WgXcQ?si=abc',
        ])->assertCreated()
            ->assertJsonPath('data.embed', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ')
            ->assertJsonPath('data.thumbnail', 'https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg');

        $this->assertStringContainsString('/storage/program/dokumentasi/', (string) $foto->json('data.url'));
        $this->assertStringContainsString('/storage/program/dokumentasi/', (string) $video->json('data.url'));
        $this->assertSame(2, count(Storage::disk('public')->allFiles('program/dokumentasi')));
    }

    public function test_batas_ukuran_berlaku_per_berkas(): void
    {
        $admin = User::factory()->admin()->create();
        $program = Program::factory()->create();

        $this->unggah($admin, $program, ['jenis' => 'foto', 'berkas' => UploadedFile::fake()->image('besar.jpg')->size(2049)])
            ->assertStatus(422)
            ->assertJsonPath('errors.berkas.0', 'Ukuran foto maksimal 2 MB.');

        $this->unggah($admin, $program, ['jenis' => 'video', 'berkas' => UploadedFile::fake()->create('besar.mp4', 51201, 'video/mp4')])
            ->assertStatus(422)
            ->assertJsonPath('errors.berkas.0', 'Ukuran video maksimal 50 MB.');

        $this->assertDatabaseCount('dokumentasi_program', 0);
    }

    public function test_format_berkas_dan_tautan_divalidasi(): void
    {
        $admin = User::factory()->admin()->create();
        $program = Program::factory()->create();

        $this->unggah($admin, $program, ['jenis' => 'foto', 'berkas' => UploadedFile::fake()->create('a.gif', 10, 'image/gif')])
            ->assertStatus(422)
            ->assertJsonPath('errors.berkas.0', 'Foto harus berformat JPG atau PNG.');

        $this->unggah($admin, $program, ['jenis' => 'video', 'berkas' => UploadedFile::fake()->create('a.avi', 10, 'video/x-msvideo')])
            ->assertStatus(422)
            ->assertJsonPath('errors.berkas.0', 'Video harus berformat MP4.');

        $this->unggah($admin, $program, ['jenis' => 'youtube', 'tautan' => 'https://vimeo.com/12345'])
            ->assertStatus(422)
            ->assertJsonPath('errors.tautan.0', 'Tautan YouTube tidak dikenali. Salin ulang tautannya dari YouTube.');

        $this->unggah($admin, $program, ['jenis' => 'audio'])
            ->assertStatus(422)
            ->assertJsonPath('errors.jenis.0', 'Pilih jenis dokumentasi: foto, video, atau tautan YouTube.');
    }

    public function test_tautan_youtube_berbagai_bentuk_dikenali(): void
    {
        $this->assertSame('dQw4w9WgXcQ', DokumentasiProgram::ambilIdYoutube('https://www.youtube.com/watch?v=dQw4w9WgXcQ'));
        $this->assertSame('dQw4w9WgXcQ', DokumentasiProgram::ambilIdYoutube('https://m.youtube.com/watch?feature=share&v=dQw4w9WgXcQ'));
        $this->assertSame('dQw4w9WgXcQ', DokumentasiProgram::ambilIdYoutube('youtu.be/dQw4w9WgXcQ'));
        $this->assertSame('dQw4w9WgXcQ', DokumentasiProgram::ambilIdYoutube('https://youtube.com/shorts/dQw4w9WgXcQ'));
        $this->assertNull(DokumentasiProgram::ambilIdYoutube('https://www.youtube.com/'));
        $this->assertNull(DokumentasiProgram::ambilIdYoutube('https://contoh.com/watch?v=dQw4w9WgXcQ'));
    }

    public function test_hanya_admin_yang_dapat_mengelola_dokumentasi(): void
    {
        $program = Program::factory()->create();
        $dokumentasi = DokumentasiProgram::factory()->create(['program_id' => $program->id]);
        $anggota = User::factory()->create();

        $this->postJson("/api/admin/program/{$program->id}/dokumentasi", [])->assertUnauthorized();
        $this->deleteJson("/api/admin/dokumentasi/{$dokumentasi->id}")->assertUnauthorized();

        $this->sebagai($anggota)
            ->postJson("/api/admin/program/{$program->id}/dokumentasi", ['jenis' => 'youtube', 'tautan' => 'https://youtu.be/dQw4w9WgXcQ'])
            ->assertForbidden();
        $this->sebagai($anggota)->deleteJson("/api/admin/dokumentasi/{$dokumentasi->id}")->assertForbidden();

        $this->assertDatabaseCount('dokumentasi_program', 1);
    }

    // ------------------------------------------------------------ KP-21.4

    public function test_menghapus_dokumentasi_menghapus_berkasnya(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $path = UploadedFile::fake()->image('foto.jpg')->store('program/dokumentasi', 'public');
        $dokumentasi = DokumentasiProgram::factory()->foto()->create(['path' => $path]);

        $this->sebagai($admin)->deleteJson("/api/admin/dokumentasi/{$dokumentasi->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Dokumentasi dihapus.');

        $this->assertDatabaseMissing('dokumentasi_program', ['id' => $dokumentasi->id]);
        Storage::disk('public')->assertMissing($path);
    }

    // ------------------------------------------------------------ KP-21.6

    public function test_pengunjung_melihat_dokumentasi_terbaru_di_atas_dan_dapat_menyaring(): void
    {
        $program = Program::factory()->create(['slug' => 'kajian-rutin']);
        $lainnya = Program::factory()->create();

        $fotoLama = DokumentasiProgram::factory()->foto()->create(['program_id' => $program->id, 'created_at' => now()->subDays(2)]);
        $youtube = DokumentasiProgram::factory()->create(['program_id' => $program->id, 'created_at' => now()->subDay()]);
        $video = DokumentasiProgram::factory()->create([
            'program_id' => $program->id,
            'jenis' => 'video',
            'path' => 'program/dokumentasi/video.mp4',
            'youtube_id' => null,
        ]);
        DokumentasiProgram::factory()->create(['program_id' => $lainnya->id]); // milik program lain

        $this->getJson('/api/publik/program/kajian-rutin/dokumentasi')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.id', $video->id)
            ->assertJsonPath('data.2.id', $fotoLama->id)
            ->assertJsonPath('meta.per_page', 12);

        $this->getJson('/api/publik/program/kajian-rutin/dokumentasi?jenis=foto')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $fotoLama->id);

        // Penyaring video mencakup berkas MP4 dan tautan YouTube.
        $this->getJson('/api/publik/program/kajian-rutin/dokumentasi?jenis=video')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.1.id', $youtube->id);

        $this->getJson('/api/publik/program/tidak-ada/dokumentasi')->assertNotFound();
    }
}
