<?php

namespace App\Models;

use Database\Factories\MateriFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Materi (induk tulisan). Isi setiap versi disimpan di `revisi_materi` — PRD §10.
 */
class Materi extends Model
{
    /** @use HasFactory<MateriFactory> */
    use HasFactory;

    // Status materi (PRD §8.2)
    public const STATUS_DRAF = 'draf';

    public const STATUS_DIAJUKAN = 'diajukan';

    public const STATUS_TERBIT = 'terbit';

    public const STATUS_DITOLAK = 'ditolak';

    protected $table = 'materi';

    protected $fillable = ['penulis_id', 'judul', 'slug', 'ringkasan', 'sampul', 'status', 'diterbitkan_pada'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'diterbitkan_pada' => 'datetime',
        ];
    }

    public function penulis(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penulis_id');
    }

    /** Semua versi, terbaru lebih dulu (halaman riwayat versi, KF-13). */
    public function revisi(): HasMany
    {
        return $this->hasMany(RevisiMateri::class)->orderByDesc('nomor_versi');
    }

    /** Versi dengan nomor terbesar, apa pun statusnya (dipakai saat mengubah/mengajukan). */
    public function revisiTerakhir(): HasOne
    {
        return $this->hasOne(RevisiMateri::class)->latestOfMany('nomor_versi');
    }

    /**
     * Versi publik terakhir: revisi `disetujui` dengan nomor terbesar (PRD §8.2).
     * Tetap ada walaupun materi sudah ditarik dari publik (KP-10.7, KP-20.5);
     * apakah versi ini sedang tampil untuk umum ditentukan oleh `status = terbit`.
     */
    public function versiTerbit(): HasOne
    {
        // Batas "disetujui" harus ikut ke subquery MAX(nomor_versi); kalau hanya
        // dipasang di query luar, revisi draf/diajukan yang lebih baru membuat
        // hasilnya kosong walau versi terbit lama masih ada.
        return $this->hasOne(RevisiMateri::class)->ofMany(
            ['nomor_versi' => 'max'],
            fn (Builder $query) => $query->where('status', RevisiMateri::STATUS_DISETUJUI),
        );
    }

    public function komentar(): HasMany
    {
        return $this->hasMany(Komentar::class);
    }

    /**
     * Kategori versi publik (KF-19). Area anggota memakai kategori revisi
     * terakhir (`revisiTerakhir.kategori`), lihat KP-19.3.
     */
    public function kategori(): BelongsToMany
    {
        return $this->belongsToMany(Kategori::class, 'kategori_materi')->withTimestamps();
    }

    /** Semua pengajuan penarikan dari publik (KF-20). */
    public function penarikan(): HasMany
    {
        return $this->hasMany(PenarikanMateri::class);
    }

    /** Pengajuan penarikan yang masih menunggu keputusan admin; paling banyak satu (PRD §8.3). */
    public function penarikanMenunggu(): HasOne
    {
        return $this->hasOne(PenarikanMateri::class)->where('status', PenarikanMateri::STATUS_DIAJUKAN);
    }

    /**
     * Samakan data versi publik di tabel `materi` (judul, slug, ringkasan,
     * sampul, kategori) dengan satu revisi (PRD §8.2). Dipakai saat:
     * - draf disimpan selama materi tidak terbit (KP-09.1),
     * - admin menyetujui publikasi (KP-11.3),
     * - admin menyetujui penarikan (KP-20.5).
     * Pemanggil bertanggung jawab membungkusnya dalam transaksi.
     */
    public function samakanDenganRevisi(RevisiMateri $revisi): void
    {
        $this->update([
            'judul' => $revisi->judul,
            // Slug hanya dibuat ulang bila judulnya berubah, supaya tautan tidak berganti tanpa perlu.
            'slug' => $revisi->judul === $this->judul ? $this->slug : self::slugUnik($revisi->judul, $this->id),
            'ringkasan' => $revisi->ringkasan,
            'sampul' => $revisi->sampul,
        ]);

        $this->kategori()->sync($revisi->kategori()->pluck('kategori.id')->all());
    }

    /**
     * Tambah 1 pada jumlah dibaca (KF-25). Memakai query langsung, bukan
     * $this->increment(), agar kolom updated_at materi tidak ikut berubah.
     */
    public function catatDibaca(): void
    {
        DB::table($this->getTable())->where('id', $this->id)->increment('jumlah_dibaca');
    }

    /**
     * Slug unik dari judul (kolom `slug` bersifat unik).
     */
    public static function slugUnik(string $judul, ?int $abaikanId = null): string
    {
        $dasar = Str::slug($judul) ?: 'materi';
        $slug = $dasar;
        $nomor = 1;

        while (self::where('slug', $slug)
            ->when($abaikanId !== null, fn ($query) => $query->where('id', '!=', $abaikanId))
            ->exists()
        ) {
            $slug = $dasar.'-'.(++$nomor);
        }

        return $slug;
    }

    /**
     * Perkiraan waktu baca dalam menit (200 kata per menit) dari isi versi yang
     * ditampilkan: revisi terakhir bila ada, jika tidak versi terbit. Hanya
     * dihitung bila relasinya sudah dimuat, supaya tidak memicu query tambahan
     * di dalam Resource (KNF-09). Dipakai oleh daftar materi (DESIGN.md §9).
     */
    public function menitBaca(): ?int
    {
        $html = $this->relationLoaded('revisiTerakhir') ? $this->revisiTerakhir?->konten_html : null;

        if ($html === null && $this->relationLoaded('versiTerbit')) {
            $html = $this->versiTerbit?->konten_html;
        }

        if ($html === null) {
            return null;
        }

        $teks = trim((string) preg_replace('/\s+/u', ' ', strip_tags($html)));
        $jumlahKata = count(preg_split('/\s+/u', $teks, -1, PREG_SPLIT_NO_EMPTY) ?: []);

        return $jumlahKata === 0 ? null : max(1, (int) ceil($jumlahKata / 200));
    }
}
