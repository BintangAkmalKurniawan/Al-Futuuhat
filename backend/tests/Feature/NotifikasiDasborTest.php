<?php

namespace Tests\Feature;

use App\Models\Materi;
use App\Models\RevisiMateri;
use App\Models\User;
use App\Notifications\MateriDiajukan;
use App\Notifications\RevisiDiverifikasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F10 — Notifikasi Dalam Aplikasi & Dasbor (PRD §8.3).
 */
class NotifikasiDasborTest extends TestCase
{
    use RefreshDatabase;

    private function token(User $user): string
    {
        return $user->createToken('api')->plainTextToken;
    }

    /** Kirim satu notifikasi contoh ke pengguna. */
    private function kirimNotifikasi(User $penerima, string $tipe = 'materi_diajukan'): void
    {
        $materi = Materi::factory()->create(['judul' => 'Adab Ilmu']);
        $revisi = RevisiMateri::factory()->create(['materi_id' => $materi->id]);
        $penulis = User::factory()->create();

        $penerima->notify($tipe === 'materi_diajukan'
            ? new MateriDiajukan($materi, $revisi, $penulis)
            : new RevisiDiverifikasi($materi, $revisi, 'disetujui', null));
    }

    public function test_daftar_notifikasi_terbaru_di_atas_dan_hanya_milik_sendiri(): void
    {
        $user = User::factory()->create();
        $orangLain = User::factory()->create();

        $this->kirimNotifikasi($user, 'materi_diajukan');
        $lama = $user->notifications()->first();
        $lama->forceFill(['created_at' => now()->subDay()])->save();
        $this->kirimNotifikasi($user, 'revisi_diverifikasi');
        $this->kirimNotifikasi($orangLain);

        // PRD §8.3, ROADMAP F10
        $this->withToken($this->token($user))->getJson('/api/notifikasi')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.data.tipe', 'revisi_diverifikasi')
            ->assertJsonPath('data.1.data.tipe', 'materi_diajukan')
            ->assertJsonPath('data.0.dibaca', false);
    }

    public function test_menandai_satu_notifikasi_sudah_dibaca(): void
    {
        $user = User::factory()->create();
        $this->kirimNotifikasi($user);
        $id = $user->notifications()->first()->id;

        // PRD §8.3, ROADMAP F10
        $this->withToken($this->token($user))
            ->patchJson("/api/notifikasi/{$id}/baca")
            ->assertOk()
            ->assertJsonPath('data.dibaca', true);

        $this->assertNotNull($user->notifications()->whereKey($id)->first()->read_at);
    }

    public function test_tidak_dapat_menandai_notifikasi_milik_orang_lain(): void
    {
        $user = User::factory()->create();
        $orangLain = User::factory()->create();
        $this->kirimNotifikasi($orangLain);
        $id = $orangLain->notifications()->first()->id;

        // PRD §8.3, ROADMAP F10
        $this->withToken($this->token($user))
            ->patchJson("/api/notifikasi/{$id}/baca")
            ->assertStatus(404);
    }

    public function test_baca_semua_menandai_seluruh_notifikasi_sekaligus(): void
    {
        $user = User::factory()->create();
        $this->kirimNotifikasi($user);
        $this->kirimNotifikasi($user, 'revisi_diverifikasi');

        $token = $this->token($user);

        // PRD §8.3, ROADMAP F10
        $this->withToken($token)->patchJson('/api/notifikasi/baca-semua')
            ->assertOk()
            ->assertJsonPath('jumlah', 2);

        $this->assertSame(0, $user->notifications()->whereNull('read_at')->count());

        // Tidak ada lagi yang tersisa saat dipanggil ulang.
        $this->withToken($token)->patchJson('/api/notifikasi/baca-semua')
            ->assertOk()
            ->assertJsonPath('jumlah', 0);
    }

    public function test_dasbor_anggota_menghitung_materi_per_status_dan_notifikasi(): void
    {
        $user = User::factory()->create();
        $orangLain = User::factory()->create();

        Materi::factory()->create(['penulis_id' => $user->id]); // draf
        Materi::factory()->diajukan()->create(['penulis_id' => $user->id]);
        Materi::factory()->terbit()->create(['penulis_id' => $user->id]);
        Materi::factory()->create(['penulis_id' => $user->id, 'status' => Materi::STATUS_DITOLAK]);
        Materi::factory()->terbit()->create(['penulis_id' => $orangLain->id]); // tidak dihitung

        $this->kirimNotifikasi($user);

        // ROADMAP F10 (dasbor anggota)
        $this->withToken($this->token($user))->getJson('/api/dasbor')
            ->assertOk()
            ->assertJsonPath('data.materi.draf', 1)
            ->assertJsonPath('data.materi.diajukan', 1)
            ->assertJsonPath('data.materi.terbit', 1)
            ->assertJsonPath('data.materi.ditolak', 1)
            ->assertJsonPath('data.materi.total', 4)
            ->assertJsonPath('data.notifikasi_belum_dibaca', 1);
    }

    public function test_dasbor_admin_menghitung_antrean_kerja(): void
    {
        $admin = User::factory()->admin()->create();

        User::factory()->menunggu()->count(2)->create();
        User::factory()->nonaktif()->create();
        $anggota = User::factory()->create(); // satu anggota aktif tambahan

        // Semua materi diberi penulis yang sudah ada agar tidak menambah pengguna aktif.
        $materi = Materi::factory()->create(['penulis_id' => $anggota->id]);
        // Nomor versi harus berbeda untuk materi yang sama.
        RevisiMateri::factory()->diajukan()->create(['materi_id' => $materi->id, 'nomor_versi' => 1]);
        RevisiMateri::factory()->diajukan()->create(['materi_id' => $materi->id, 'nomor_versi' => 2]);
        RevisiMateri::factory()->create(['materi_id' => $materi->id, 'nomor_versi' => 3]); // draf

        Materi::factory()->terbit()->count(3)->create(['penulis_id' => $anggota->id]);

        // ROADMAP F10 (dasbor admin)
        $this->withToken($this->token($admin))->getJson('/api/admin/dasbor')
            ->assertOk()
            ->assertJsonPath('data.pendaftar_menunggu', 2)
            ->assertJsonPath('data.revisi_diajukan', 2)
            ->assertJsonPath('data.anggota_aktif', 2) // admin + satu anggota aktif
            ->assertJsonPath('data.materi_terbit', 3);
    }

    public function test_anggota_biasa_tidak_dapat_membuka_dasbor_admin(): void
    {
        // KP-04.5
        $this->withToken($this->token(User::factory()->create()))
            ->getJson('/api/admin/dasbor')
            ->assertStatus(403);
    }

    public function test_tamu_tidak_dapat_membuka_notifikasi(): void
    {
        $this->getJson('/api/notifikasi')->assertStatus(401);
    }
}