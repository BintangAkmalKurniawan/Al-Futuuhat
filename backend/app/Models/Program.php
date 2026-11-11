<?php

namespace App\Models;

use Database\Factories\ProgramFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Program: kegiatan rutin Al-Futuuhat (KF-21) — PRD §10.
 * Hanya admin yang mengelola; pengunjung dan anggota hanya membaca.
 */
class Program extends Model
{
    /** @use HasFactory<ProgramFactory> */
    use HasFactory;

    public const STATUS_AKTIF = 'aktif';

    public const STATUS_SELESAI = 'selesai';

    protected $table = 'program';

    protected $fillable = [
        'nama',
        'slug',
        'ringkasan',
        'jadwal',
        'sampul',
        'deskripsi',
        'status',
    ];

    /** PIC program: satu atau lebih anggota (KP-21.2). */
    public function pic(): BelongsToMany
    {
        return $this->belongsToMany(Anggota::class, 'pic_program')->withTimestamps();
    }

    /** Kumpulan foto dan video program ini (KP-21.3). */
    public function dokumentasi(): HasMany
    {
        return $this->hasMany(DokumentasiProgram::class);
    }

    /**
     * Tambahkan `jumlah_foto` dan `jumlah_video` (video MP4 + YouTube) dengan
     * subquery hitung, agar daftar tidak memuat seluruh dokumentasi (KNF-09).
     */
    public function scopeDenganJumlahDokumentasi(Builder $query): Builder
    {
        return $query->withCount([
            'dokumentasi as jumlah_foto' => fn ($sub) => $sub->where('jenis', DokumentasiProgram::JENIS_FOTO),
            'dokumentasi as jumlah_video' => fn ($sub) => $sub->whereIn('jenis', [
                DokumentasiProgram::JENIS_VIDEO,
                DokumentasiProgram::JENIS_YOUTUBE,
            ]),
        ]);
    }

    /**
     * Urutan tampil (KP-21.5): program aktif lebih dulu, lalu menurut nama.
     */
    public function scopeUrutanTampil(Builder $query): Builder
    {
        return $query
            ->orderByRaw('case when status = ? then 0 else 1 end', [self::STATUS_AKTIF])
            ->orderBy('nama');
    }

    /**
     * Path seluruh berkas milik program: sampul dan berkas dokumentasi (KP-21.4).
     * Dikumpulkan sebelum program dihapus, karena baris dokumentasi ikut
     * terhapus lewat cascade sehingga path-nya tidak dapat dibaca lagi.
     *
     * @return array<int, string>
     */
    public function semuaBerkas(): array
    {
        $berkas = $this->dokumentasi()->whereNotNull('path')->pluck('path')->all();

        if ($this->sampul) {
            $berkas[] = $this->sampul;
        }

        return $berkas;
    }

    /**
     * Slug unik dari nama program (kolom `slug` bersifat unik).
     */
    public static function slugUnik(string $nama, ?int $abaikanId = null): string
    {
        $dasar = Str::slug($nama) ?: 'program';
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
}
