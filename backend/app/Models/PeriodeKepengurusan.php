<?php

namespace App\Models;

use Database\Factories\PeriodeKepengurusanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Periode kepengurusan
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

    // Periode aktif
    public static function aktif(): ?self
    {
        return self::where('is_aktif', true)->first();
    }

    // Cek periode akan berganti
    public function akanBerganti(): bool
    {
        return $this->is_aktif && $this->tahun_mulai < (int) now()->year;
    }

    // Periode saat ini
    public static function saatIni(User $admin): self
    {
        $tahun = (int) now()->year;

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

    // Periode sebelumnya
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
