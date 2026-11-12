<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

// Akun pengguna
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const STATUS_MENUNGGU = 'menunggu';

    public const STATUS_AKTIF = 'aktif';

    public const STATUS_DITOLAK = 'ditolak';

    public const STATUS_NONAKTIF = 'nonaktif';

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
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Data keanggotaan
    public function anggota(): HasOne
    {
        return $this->hasOne(Anggota::class);
    }

    // Materi
    public function materi(): HasMany
    {
        return $this->hasMany(Materi::class, 'penulis_id');
    }

    // Komentar
    public function komentar(): HasMany
    {
        return $this->hasMany(Komentar::class);
    }

    // Keputusan verifikasi
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
