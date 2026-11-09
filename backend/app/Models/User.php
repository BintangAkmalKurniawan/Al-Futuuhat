<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Akun pengguna: pengunjung yang mendaftar, anggota, dan admin — PRD §10.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    // Status akun (PRD §8.1)
    public const STATUS_MENUNGGU = 'menunggu';

    public const STATUS_AKTIF = 'aktif';

    public const STATUS_DITOLAK = 'ditolak';

    public const STATUS_NONAKTIF = 'nonaktif';

    // Peran pengguna (PRD §10)
    public const ROLE_ADMIN = 'admin';

    public const ROLE_ANGGOTA = 'anggota';

    protected $fillable = ['nama', 'email', 'password', 'role', 'status'];

    protected $hidden = ['password', 'remember_token'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /** Data keanggotaan (no HP, alamat, foto, hasil verifikasi). */
    public function anggota(): HasOne
    {
        return $this->hasOne(Anggota::class);
    }

    /** Materi yang ditulis pengguna ini. */
    public function materi(): HasMany
    {
        return $this->hasMany(Materi::class, 'penulis_id');
    }

    /** Komentar forum yang dikirim pengguna ini. */
    public function komentar(): HasMany
    {
        return $this->hasMany(Komentar::class);
    }

    /** Keputusan verifikasi materi yang dibuat pengguna ini sebagai admin. */
    public function verifikasiMateri(): HasMany
    {
        return $this->hasMany(VerifikasiMateri::class, 'admin_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isAktif(): bool
    {
        return $this->status === self::STATUS_AKTIF;
    }
}
