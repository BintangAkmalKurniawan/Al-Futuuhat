<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Materi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * KF-18 — gambar sampul materi untuk daftar dan kartu artikel publik.
 */
class SampulMateriTest extends TestCase
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
            // Kategori wajib diisi minimal satu (KF-19, KP-19.2).
            'kategori' => [$this->idKategori()],
            'konten' => ['ops' => [['insert' => "Isi materi puasa.\n"]]],
            'konten_html' => '<p>Isi materi puasa.</p>',
        ], $ubah);
    }

    /** Satu kategori contoh, dipakai berulang dalam satu tes. */
    private function idKategori(string $nama = 'Fiqih'): int
    {
        return Kategori::firstOrCreate(['nama' => $nama], ['slug' => strtolower($nama)])->id;
    }

    /** Unggah sampul lalu kembalikan path-nya. */
    private function unggahSampul(string $token, string $nama = 'sampul.jpg'): string
    {
        return $this->withToken($token)
            ->post('/api/unggah-sampul', ['sampul' => UploadedFile::fake()->image($nama, 800, 450)])
            ->assertCreated()
            ->json('path');
    }

    public function test_sampul_tersimpan_dan_tampil_di_daftar_materi(): void
    {
        Storage::fake('public');

        $penulis = User::factory()->create();
        $token = $this->token($penulis);

        $path = $this->unggahSampul($token);

        // KP-18.4
        $id = $this->withToken($token)
            ->postJson('/api/materi', $this->dataMateri(['sampul' => $path]))
            ->assertCreated()
            ->json('data.materi.id');

        $this->assertSame($path, Materi::findOrFail($id)->sampul);

        $respons = $this->withToken($token)->getJson('/api/materi')->assertOk();

        $this->assertStringContainsString('/storage/sampul-materi/', (string) $respons->json('data.0.sampul'));
    }

    public function test_sampul_tampil_di_kartu_artikel_publik(): void
    {
        Storage::fake('public');

        $penulis = User::factory()->create();
        $token = $this->token($penulis);

        $path = $this->unggahSampul($token);

        $materi = Materi::factory()->terbit()->create([
            'penulis_id' => $penulis->id,
            'sampul' => $path,
        ]);

        $materi->revisi()->create([
            'nomor_versi' => 1,
            'judul' => $materi->judul,
            'sampul' => $path,
            'konten' => ['ops' => [['insert' => "Isi materi.\n"]]],
            'konten_html' => '<p>Isi materi.</p>',
            'status' => 'disetujui',
        ]);

        // KP-17.1 + KP-18.4: pengunjung melihat sampul pada kartu artikel.
        $respons = $this->getJson('/api/publik/materi')->assertOk();

        $this->assertStringContainsString('/storage/sampul-materi/', (string) $respons->json('data.0.sampul'));
    }

    public function test_sampul_di_luar_folder_unggahan_ditolak(): void
    {
        $penulis = User::factory()->create();

        // Path dari folder lain (atau berkas lain di server) tidak boleh dipakai.
        $this->withToken($this->token($penulis))
            ->postJson('/api/materi', $this->dataMateri(['sampul' => 'gambar-materi/orang-lain.jpg']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('sampul');
    }

    public function test_unggahan_sampul_bukan_gambar_ditolak(): void
    {
        $penulis = User::factory()->create();

        // KNF-03
        $this->withToken($this->token($penulis))
            ->post('/api/unggah-sampul', [
                'sampul' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('sampul');
    }

    public function test_sampul_yang_diganti_menghapus_berkas_lama(): void
    {
        Storage::fake('public');

        $penulis = User::factory()->create();
        $token = $this->token($penulis);

        $sampulLama = $this->unggahSampul($token, 'lama.jpg');

        $id = $this->withToken($token)
            ->postJson('/api/materi', $this->dataMateri(['sampul' => $sampulLama]))
            ->assertCreated()
            ->json('data.materi.id');

        $sampulBaru = $this->unggahSampul($token, 'baru.jpg');

        $this->withToken($token)
            ->putJson("/api/materi/{$id}", $this->dataMateri(['sampul' => $sampulBaru]))
            ->assertOk();

        $this->assertSame($sampulBaru, Materi::findOrFail($id)->sampul);
        Storage::disk('public')->assertMissing($sampulLama);
        Storage::disk('public')->assertExists($sampulBaru);
    }

    public function test_sampul_revisi_materi_terbit_berubah_tanpa_mengubah_sampul_publik(): void
    {
        Storage::fake('public');

        $penulis = User::factory()->create();
        $token = $this->token($penulis);

        $sampulAwal = $this->unggahSampul($token, 'awal.jpg');

        $id = $this->withToken($token)
            ->postJson('/api/materi', $this->dataMateri(['sampul' => $sampulAwal]))
            ->assertCreated()
            ->json('data.materi.id');

        // Materi sedang terbit; revisi terakhir masih draf.
        Materi::findOrFail($id)->update([
            'status' => Materi::STATUS_TERBIT,
            'diterbitkan_pada' => now(),
        ]);

        $sampulPengganti = $this->unggahSampul($token, 'pengganti.jpg');

        $this->withToken($token)
            ->putJson("/api/materi/{$id}", $this->dataMateri(['sampul' => $sampulPengganti]))
            ->assertOk();

        // KP-18.4 (Draf 2.3): sampul revisi berganti, sampul versi publik tetap.
        $materi = Materi::findOrFail($id);
        $this->assertSame($sampulAwal, $materi->sampul);
        $this->assertSame($sampulPengganti, $materi->revisiTerakhir()->first()->sampul);

        // Berkas sampul publik tidak dihapus karena masih dipakai.
        Storage::disk('public')->assertExists($sampulAwal);
        Storage::disk('public')->assertExists($sampulPengganti);
    }
}
