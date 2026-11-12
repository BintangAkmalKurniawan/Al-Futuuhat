<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\DokumentasiProgram;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

// Program kegiatan rutin
class ProgramTest extends TestCase
{
    use RefreshDatabase;

    private function sebagai(User $user): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($user->createToken('api')->plainTextToken);
    }

    private function anggotaAktif(string $nama = 'Ahmad Fauzi'): Anggota
    {
        return Anggota::factory()->create([
            'user_id' => User::factory()->create(['nama' => $nama])->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function dataProgram(array $data = []): array
    {
        return $data + [
            'nama' => 'Kajian Rutin Jumat Malam',
            'ringkasan' => 'Kajian kitab bersama setiap pekan.',
            'jadwal' => "Setiap Jumat malam, ba'da Isya",
            'status' => Program::STATUS_AKTIF,
            'deskripsi' => '<p>Kajian dibuka untuk umum.</p>',
            'pic' => [$this->anggotaAktif()->id],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function tambah(User $admin, array $data = []): TestResponse
    {
        return $this->sebagai($admin)->post('/api/admin/program', $this->dataProgram($data), ['Accept' => 'application/json']);
    }

    // KP-21.1

    public function test_admin_menambah_program_dengan_sampul_dan_slug_otomatis(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $respons = $this->tambah($admin, [
            'sampul' => UploadedFile::fake()->image('sampul.jpg', 1200, 750),
            'deskripsi' => '<p>Kajian dibuka untuk umum.</p><script>curi()</script>',
        ])->assertCreated()
            ->assertJsonPath('message', 'Program ditambahkan.')
            ->assertJsonPath('data.slug', 'kajian-rutin-jumat-malam')
            ->assertJsonPath('data.pic.0.nama', 'Ahmad Fauzi');

        $program = Program::firstOrFail();

        Storage::disk('public')->assertExists((string) $program->sampul);
        $this->assertStringNotContainsString('<script', $program->deskripsi);
        $this->assertStringContainsString('/storage/program/sampul/', (string) $respons->json('data.sampul'));
    }

    public function test_isian_wajib_dan_nama_unik_divalidasi(): void
    {
        $admin = User::factory()->admin()->create();
        Program::factory()->create(['nama' => 'Kajian Rutin Jumat Malam']);

        $this->tambah($admin, ['deskripsi' => '<p><br></p>', 'pic' => []])
            ->assertStatus(422)
            ->assertJsonPath('errors.nama.0', 'Nama program sudah ada.')
            ->assertJsonPath('errors.deskripsi.0', 'Penjelasan program wajib diisi.')
            ->assertJsonPath('errors.pic.0', 'Pilih minimal satu PIC.');
    }

    public function test_sampul_bukan_jpg_png_atau_lebih_dari_dua_megabita_ditolak(): void
    {
        $admin = User::factory()->admin()->create();

        $this->tambah($admin, ['sampul' => UploadedFile::fake()->create('sampul.pdf', 100, 'application/pdf')])
            ->assertStatus(422)
            ->assertJsonValidationErrors('sampul');

        $this->tambah($admin, ['sampul' => UploadedFile::fake()->image('besar.jpg')->size(2049)])
            ->assertStatus(422)
            ->assertJsonPath('errors.sampul.0', 'Ukuran sampul maksimal 2 MB.');
    }

    public function test_hanya_admin_yang_dapat_mengelola_program(): void
    {
        $program = Program::factory()->create();
        $anggota = User::factory()->create();

        $this->postJson('/api/admin/program', [])->assertUnauthorized();
        $this->deleteJson("/api/admin/program/{$program->id}")->assertUnauthorized();

        $this->sebagai($anggota)->postJson('/api/admin/program', $this->dataProgram())->assertForbidden();
        $this->sebagai($anggota)->postJson("/api/admin/program/{$program->id}", $this->dataProgram())->assertForbidden();
        $this->sebagai($anggota)->deleteJson("/api/admin/program/{$program->id}")->assertForbidden();

        $this->assertDatabaseCount('program', 1);
    }

    public function test_admin_mengubah_program_dan_mengganti_sampul(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->tambah($admin, ['sampul' => UploadedFile::fake()->image('lama.jpg')])->assertCreated();
        $program = Program::firstOrFail();
        $sampulLama = (string) $program->sampul;
        $picBaru = $this->anggotaAktif('Siti Aminah');

        $this->sebagai($admin)->post("/api/admin/program/{$program->id}", $this->dataProgram([
            'nama' => 'Kajian Kitab Pekanan',
            'status' => Program::STATUS_SELESAI,
            'sampul' => UploadedFile::fake()->image('baru.png'),
            'pic' => [$picBaru->id],
        ]), ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.slug', 'kajian-kitab-pekanan')
            ->assertJsonPath('data.status', Program::STATUS_SELESAI)
            ->assertJsonPath('data.pic.0.anggota_id', $picBaru->id);

        $program->refresh();
        Storage::disk('public')->assertMissing($sampulLama);
        Storage::disk('public')->assertExists((string) $program->sampul);

        $this->sebagai($admin)->post("/api/admin/program/{$program->id}", $this->dataProgram([
            'nama' => 'Kajian Kitab Pekanan',
            'hapus_sampul' => true,
        ]), ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.sampul', null);
    }

    // KP-21.2

    public function test_pic_boleh_lebih_dari_satu_tetapi_harus_anggota_aktif(): void
    {
        $admin = User::factory()->admin()->create();
        $nonaktif = Anggota::factory()->create(['user_id' => User::factory()->nonaktif()->create()->id]);

        $this->tambah($admin, ['pic' => [$nonaktif->id]])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['pic.0' => 'PIC harus anggota aktif.']);

        $this->tambah($admin, ['pic' => [99999]])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['pic.0' => 'PIC harus anggota aktif.']);

        $this->tambah($admin, ['pic' => [$this->anggotaAktif('Ketua')->id, $this->anggotaAktif('Bendahara')->id]])
            ->assertCreated()
            ->assertJsonCount(2, 'data.pic');
    }

    // KP-21.4

    public function test_menghapus_program_menghapus_dokumentasi_dan_berkasnya(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $sampul = UploadedFile::fake()->image('sampul.jpg')->store('program/sampul', 'public');
        $foto = UploadedFile::fake()->image('foto.jpg')->store('program/dokumentasi', 'public');

        $program = Program::factory()->create(['sampul' => $sampul]);
        $program->pic()->attach($this->anggotaAktif()->id);
        DokumentasiProgram::factory()->foto()->create(['program_id' => $program->id, 'path' => $foto]);
        DokumentasiProgram::factory()->create(['program_id' => $program->id]);

        $this->sebagai($admin)->deleteJson("/api/admin/program/{$program->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Program dihapus.');

        $this->assertDatabaseCount('program', 0);
        $this->assertDatabaseCount('dokumentasi_program', 0);
        $this->assertDatabaseCount('pic_program', 0);
        Storage::disk('public')->assertMissing($sampul);
        Storage::disk('public')->assertMissing($foto);
    }

    // KP-21.5

    public function test_pengunjung_melihat_daftar_program_aktif_lebih_dulu_tanpa_data_pribadi_pic(): void
    {
        $pic = $this->anggotaAktif('Ahmad Fauzi');

        $selesai = Program::factory()->selesai()->create(['nama' => 'A Program Selesai']);
        $aktif = Program::factory()->create(['nama' => 'Z Program Aktif']);
        $aktif->pic()->attach($pic->id);
        $selesai->pic()->attach($pic->id);
        DokumentasiProgram::factory()->foto()->create(['program_id' => $aktif->id]);
        DokumentasiProgram::factory()->count(2)->create(['program_id' => $aktif->id]);

        $respons = $this->getJson('/api/publik/program')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.nama', 'Z Program Aktif')
            ->assertJsonPath('data.0.jumlah_foto', 1)
            ->assertJsonPath('data.0.jumlah_video', 2)
            ->assertJsonPath('data.0.pic', [['nama' => 'Ahmad Fauzi']])
            ->assertJsonPath('data.1.status', Program::STATUS_SELESAI);

        $json = $respons->getContent();
        $this->assertStringNotContainsString($pic->no_hp, (string) $json);
        $this->assertStringNotContainsString('email', (string) $json);
        $this->assertStringNotContainsString('anggota_id', (string) $json);
        $this->assertArrayNotHasKey('deskripsi', $respons->json('data.0'));
    }

    public function test_pengunjung_membaca_penjelasan_program_lewat_slug(): void
    {
        $program = Program::factory()->create([
            'slug' => 'kajian-rutin',
            'deskripsi' => '<p>Penjelasan rinci.</p>',
        ]);
        $program->pic()->attach($this->anggotaAktif()->id);

        $this->getJson('/api/publik/program/kajian-rutin')
            ->assertOk()
            ->assertJsonPath('data.nama', $program->nama)
            ->assertJsonPath('data.deskripsi', '<p>Penjelasan rinci.</p>')
            ->assertJsonPath('data.jumlah_foto', 0);

        $this->getJson('/api/publik/program/tidak-ada')->assertNotFound();
    }
}
