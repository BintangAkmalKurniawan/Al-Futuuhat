<?php

namespace App\Models;

use Database\Factories\PeriodeKepengurusanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Periode kepengurusan — PRD §10.
 *
 * Draf 2.5 (KP-07.1): periode tidak lagi dibuat manual. Satu periode per tahun
 * kalender; periode tahun berjalan dibuat otomatis oleh saatIni() ketika admin
 * mengubah susunan pengurus untuk pertama kalinya di tahun itu.
 */
class PeriodeKepengurusan extends Model
{
    /** @use HasFactory<PeriodeKepengurusanFactory> */
    use HasFactory;

    protected $table = 'periode_kepengurusan';

    protected $fillable = ['nama_periode', 'tahun_mulai', 'tahun_selesai', 'is_aktif'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_aktif' => 'boolean',
        ];
    }

    public function kepengurusan(): HasMany
    {
        return $this->hasMany(Kepengurusan::class, 'periode_id');
    }

    public function riwayat(): HasMany
    {
        return $this->hasMany(RiwayatStruktur::class, 'periode_id');
    }

    /** Periode yang sedang berlaku (tanpa membuat periode baru). */
    public static function aktif(): ?self
    {
        return self::where('is_aktif', true)->first();
    }

    /**
     * True bila periode aktif ini berasal dari tahun lalu, sehingga perubahan
     * susunan berikutnya akan memulai periode tahun berjalan.
     */
    public function akanBerganti(): bool
    {
        return $this->is_aktif && $this->tahun_mulai < (int) now()->year;
    }

    /**
     * Periode tempat perubahan susunan pengurus dicatat (KP-07.1).
     *
     * - Periode aktif masih tahun berjalan (atau lebih baru) → dipakai apa adanya.
     * - Belum ada periode aktif, atau periode aktif dari tahun lalu → dibuat periode
     *   tahun berjalan (mis. "2027"); periode lama dinonaktifkan dan susunan
     *   pengurusnya disalin sebagai titik awal; pembuatannya dicatat di riwayat.
     *
     * WAJIB dipanggil di dalam DB::transaction() agar periode baru, salinan
     * pengurus, dan perubahan yang memicunya tersimpan atau batal bersama.
     */
    public static function saatIni(User $admin): self
    {
        $tahun = (int) now()->year;

        // lockForUpdate: dua admin yang menyimpan bersamaan tidak membuat dua periode.
        $lama = self::where('is_aktif', true)->lockForUpdate()->first();

        if ($lama !== null && $lama->tahun_mulai >= $tahun) {
            return $lama;
        }

        $baru = self::create([
            'nama_periode' => (string) $tahun,
            'tahun_mulai' => $tahun,
            'tahun_selesai' => $tahun,
            'is_aktif' => true,
        ]);

        $jumlahDisalin = 0;

        if ($lama !== null) {
            $lama->update(['is_aktif' => false]);

            // Susunan periode lama menjadi titik awal periode baru.
            foreach ($lama->kepengurusan()->get() as $baris) {
                $baru->kepengurusan()->create([
                    'anggota_id' => $baris->anggota_id,
                    'divisi_id' => $baris->divisi_id,
                    'jabatan_id' => $baris->jabatan_id,
                ]);
                $jumlahDisalin++;
            }
        }

        $baru->riwayat()->create([
            'admin_id' => $admin->id,
            'aksi' => RiwayatStruktur::AKSI_PERIODE_BARU,
            'keterangan' => $lama !== null
                ? "Periode {$baru->nama_periode} dimulai otomatis; {$jumlahDisalin} pengurus disalin dari periode {$lama->nama_periode}."
                : "Periode {$baru->nama_periode} dimulai otomatis.",
        ]);

        return $baru;
    }

    /**
     * Periode tepat sebelum periode ini (pembanding riwayat, KP-07.8).
     */
    public function sebelumnya(): ?self
    {
        return self::query()
            ->where(function ($query) {
                $query->where('tahun_mulai', '<', $this->tahun_mulai)
                    ->orWhere(fn ($sub) => $sub->where('tahun_mulai', $this->tahun_mulai)->where('id', '<', $this->id));
            })
            ->orderByDesc('tahun_mulai')
            ->orderByDesc('id')
            ->first();
    }
}
