<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Materi;
use App\Models\RevisiMateri;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

// Kategori pada materi
class KategoriMateriTest extends TestCase
{
    use RefreshDatabase;

    private function token(User $user): string
    {
        return $user->createToken('api')->plainTextToken;
    }

    private function kategori(string $nama): Kategori
    {
        return Kategori::firstOrCreate(['nama' => $nama], ['slug' => Str::slug($nama)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function dataMateri(array $ubah = []): array
    {
        return array_merge([
            'judul' => 'Fiqih Puasa Ramadhan',
            'ringkasan' => 'Ringkasan materi puasa.',
            'kategori' => [],
            'konten' => ['ops' => [['insert' => "Isi materi puasa.\n"]]],
            'konten_html' => '<p>Isi materi puasa.</p>',
        ], $ubah);
    }

    private function materiTerbit(User $penulis, string $judul, Kategori $kategori): Materi
    {
        $materi = Materi::factory()->terbit()->create([
            'penulis_id' => $penulis->id,
            'judul' => $judul,
        ]);

        $revisi = RevisiMateri::factory()->disetujui()->create([
            'materi_id' => $materi->id,
            'nomor_versi' => 1,
            'konten_html' => '<p>Isi '.$judul.'.</p>',
        ]);

        $materi->kategori()->attach($kategori->id);
        $revisi->kategori()->attach($kategori->id);

        return $materi;
    }

    // KP-19.2, KP-19.4
    public function test_materi_baru_menyimpan_kategori_yang_dipilih(): void
    {
        $penulis = User::factory()->create();
        $token = $this->token($penulis);
        $fiqih = $this->kategori('Fiqih');
        $akhlak = $this->kategori('Akhlak');

        $respons = $this->withToken($token)->postJson('/api/materi', $this->dataMateri([
            'kategori' => [$fiqih->id, $akhlak->id],
        ]))->assertCreated();

        $materi = Materi::findOrFail($respons->json('data.materi.id'));

        $this->assertEqualsCanonicalizing(
            [$fiqih->id, $akhlak->id],
            $materi->kategori()->pluck('kategori.id')->all()
        );
        $this->assertEqualsCanonicalizing(
            [$fiqih->id, $akhlak->id],
            $respons->json('data.kategori_ids')
        );

        $this->withToken($token)->getJson('/api/materi')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonCount(2, 'data.0.kategori');
    }

    // KP-19.2
    public function test_materi_tanpa_kategori_ditolak(): void
    {
        $penulis = User::factory()->create();
        $token = $this->token($penulis);

        $this->withToken($token)
            ->postJson('/api/materi', $this->dataMateri(['kategori' => []]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('kategori')
            ->assertJsonPath('errors.kategori.0', 'Pilih minimal satu kategori ya.');

        $tanpaKategori = $this->dataMateri();
        unset($tanpaKategori['kategori']);

        $this->withToken($token)
            ->postJson('/api/materi', $tanpaKategori)
            ->assertStatus(422)
            ->assertJsonValidationErrors('kategori');

        $this->assertDatabaseCount('materi', 0);
    }

    // KP-19.2
    public function test_kategori_tidak_dikenal_ditolak(): void
    {
        $penulis = User::factory()->create();

        $respons = $this->withToken($this->token($penulis))
            ->postJson('/api/materi', $this->dataMateri(['kategori' => [999_999]]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('kategori.0');

        $this->assertSame(['Kategorinya tidak ketemu. Coba pilih lagi ya.'], $respons->json('errors')['kategori.0']);

        $this->assertDatabaseCount('materi', 0);
    }

    // KP-19.2
    public function test_kategori_dapat_diubah_selagi_belum_pernah_terbit(): void
    {
        $penulis = User::factory()->create();
        $token = $this->token($penulis);
        $fiqih = $this->kategori('Fiqih');
        $akhlak = $this->kategori('Akhlak');

        $id = $this->withToken($token)->postJson('/api/materi', $this->dataMateri([
            'kategori' => [$fiqih->id],
        ]))->assertCreated()->json('data.materi.id');

        $this->withToken($token)->getJson("/api/materi/{$id}/edit")
            ->assertOk()
            ->assertJsonPath('data.kategori_ids', [$fiqih->id]);

        $this->withToken($token)->putJson("/api/materi/{$id}", $this->dataMateri([
            'kategori' => [$akhlak->id],
        ]))->assertOk();

        $this->assertSame(
            [$akhlak->id],
            Materi::findOrFail($id)->kategori()->pluck('kategori.id')->all()
        );
    }

    // KP-19.3
    public function test_kategori_revisi_materi_terbit_berubah_tanpa_mengubah_kategori_publik(): void
    {
        $penulis = User::factory()->create();
        $token = $this->token($penulis);
        $fiqih = $this->kategori('Fiqih');
        $akhlak = $this->kategori('Akhlak');

        $materi = $this->materiTerbit($penulis, 'Bab Puasa', $fiqih);

        $this->withToken($token)->postJson("/api/materi/{$materi->id}/revisi")->assertCreated();

        $this->withToken($token)->putJson("/api/materi/{$materi->id}", $this->dataMateri([
            'kategori' => [$akhlak->id],
        ]))->assertOk();

        $this->assertSame(
            [$fiqih->id],
            $materi->fresh()->kategori()->pluck('kategori.id')->all()
        );

        $this->getJson("/api/publik/materi/{$materi->slug}")
            ->assertOk()
            ->assertJsonPath('data.kategori.0.slug', 'fiqih');

        $this->withToken($token)->getJson("/api/materi/{$materi->id}/edit")
            ->assertOk()
            ->assertJsonPath('data.kategori_ids', [$akhlak->id]);

        $this->withToken($token)->getJson('/api/materi?kategori=akhlak')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    // KP-19.3, KP-19.4
    public function test_penyaring_kategori_pada_arsip_anggota(): void
    {
        $penulis = User::factory()->create();
        $token = $this->token($penulis);
        $fiqih = $this->kategori('Fiqih');
        $akhlak = $this->kategori('Akhlak');

        $puasa = Materi::factory()->create(['penulis_id' => $penulis->id, 'judul' => 'Bab Puasa']);
        RevisiMateri::factory()->create(['materi_id' => $puasa->id])->kategori()->attach($fiqih->id);
        $puasa->kategori()->attach($fiqih->id);

        $adab = Materi::factory()->create(['penulis_id' => $penulis->id, 'judul' => 'Adab Menuntut Ilmu']);
        RevisiMateri::factory()->create(['materi_id' => $adab->id])->kategori()->attach($akhlak->id);
        $adab->kategori()->attach($akhlak->id);

        $this->withToken($token)->getJson('/api/materi')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->withToken($token)->getJson('/api/materi?kategori=fiqih')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Bab Puasa')
            ->assertJsonPath('data.0.kategori.0.slug', 'fiqih');
    }

    // KP-19.4
    public function test_penyaring_kategori_pada_daftar_publik(): void
    {
        $penulis = User::factory()->create();
        $fiqih = $this->kategori('Fiqih');
        $akhlak = $this->kategori('Akhlak');

        $this->materiTerbit($penulis, 'Bab Puasa', $fiqih);
        $this->materiTerbit($penulis, 'Adab Menuntut Ilmu', $akhlak);

        $this->getJson('/api/publik/materi?kategori=akhlak')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Adab Menuntut Ilmu')
            ->assertJsonPath('data.0.kategori.0.slug', 'akhlak');

        $this->getJson('/api/publik/materi')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    // KP-19.4
    public function test_penyaring_kategori_digabung_dengan_pencarian_kata_kunci(): void
    {
        $penulis = User::factory()->create();
        $fiqih = $this->kategori('Fiqih');
        $akhlak = $this->kategori('Akhlak');

        $this->materiTerbit($penulis, 'Bab Puasa Ramadhan', $fiqih);
        $this->materiTerbit($penulis, 'Bab Zakat Fitrah', $fiqih);
        $this->materiTerbit($penulis, 'Adab Puasa', $akhlak);

        $this->getJson('/api/publik/materi?kategori=fiqih&q=puasa')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Bab Puasa Ramadhan');
    }

    // KP-19.4
    public function test_kategori_tampil_di_halaman_baca_anggota_dan_publik(): void
    {
        $penulis = User::factory()->create();
        $akhlak = $this->kategori('Akhlak');
        $materi = $this->materiTerbit($penulis, 'Adab Menuntut Ilmu', $akhlak);

        $this->withToken($this->token($penulis))->getJson("/api/materi/{$materi->slug}")
            ->assertOk()
            ->assertJsonPath('data.kategori.0.nama', 'Akhlak');

        $this->getJson("/api/publik/materi/{$materi->slug}")
            ->assertOk()
            ->assertJsonPath('data.kategori.0.slug', 'akhlak');
    }
}
