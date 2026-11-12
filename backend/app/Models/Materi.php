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

// Materi
class Materi extends Model
{
    /** @use HasFactory<MateriFactory> */
    use HasFactory;

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

    // Semua versi
    public function revisi(): HasMany
    {
        return $this->hasMany(RevisiMateri::class)->orderByDesc('nomor_versi');
    }

    // Versi terakhir
    public function revisiTerakhir(): HasOne
    {
        return $this->hasOne(RevisiMateri::class)->latestOfMany('nomor_versi');
    }

    // Versi publik terakhir
    public function versiTerbit(): HasOne
    {
        return $this->hasOne(RevisiMateri::class)->ofMany(
            ['nomor_versi' => 'max'],
            fn (Builder $query) => $query->where('status', RevisiMateri::STATUS_DISETUJUI),
        );
    }

    public function komentar(): HasMany
    {
        return $this->hasMany(Komentar::class);
    }

    // Kategori versi publik
    public function kategori(): BelongsToMany
    {
        return $this->belongsToMany(Kategori::class, 'kategori_materi')->withTimestamps();
    }

    // Semua pengajuan penarikan dari publik
    public function penarikan(): HasMany
    {
        return $this->hasMany(PenarikanMateri::class);
    }

    // Pengajuan penarikan yang masih menunggu keputusan admin
    public function penarikanMenunggu(): HasOne
    {
        return $this->hasOne(PenarikanMateri::class)->where('status', PenarikanMateri::STATUS_DIAJUKAN);
    }

    // Samakan data publik dengan revisi
    public function samakanDenganRevisi(RevisiMateri $revisi): void
    {
        $this->update([
            'judul' => $revisi->judul,
            'slug' => $revisi->judul === $this->judul ? $this->slug : self::slugUnik($revisi->judul, $this->id),
            'ringkasan' => $revisi->ringkasan,
            'sampul' => $revisi->sampul,
        ]);

        $this->kategori()->sync($revisi->kategori()->pluck('kategori.id')->all());
    }

    // Catat dibaca
    public function catatDibaca(): void
    {
        DB::table($this->getTable())->where('id', $this->id)->increment('jumlah_dibaca');
    }

    // Buat slug unik
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

    // Perkiraan waktu baca
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
