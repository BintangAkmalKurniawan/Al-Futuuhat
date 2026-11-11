<?php

use App\Http\Controllers\Admin\AnggotaController;
use App\Http\Controllers\Admin\DasborController as AdminDasborController;
use App\Http\Controllers\Admin\StatistikController as AdminStatistikController;
use App\Http\Controllers\Admin\DivisiController as AdminDivisiController;
use App\Http\Controllers\Admin\DokumentasiProgramController as AdminDokumentasiProgramController;
use App\Http\Controllers\Admin\JabatanController as AdminJabatanController;
use App\Http\Controllers\Admin\KategoriController as AdminKategoriController;
use App\Http\Controllers\Admin\KepengurusanController;
use App\Http\Controllers\Admin\PenarikanMateriController as AdminPenarikanMateriController;
use App\Http\Controllers\Admin\PendaftaranController;
use App\Http\Controllers\Admin\ProgramController as AdminProgramController;
use App\Http\Controllers\Admin\VerifikasiMateriController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\DasborController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\KomentarController;
use App\Http\Controllers\MateriController;
use App\Http\Controllers\MateriPublikController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\PenarikanMateriController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\ProgramPublikController;
use App\Http\Controllers\StrukturController;
use App\Http\Controllers\UnggahGambarController;
use App\Http\Controllers\UnggahSampulController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute API — sesuai PRD §11
|--------------------------------------------------------------------------
| P   = publik
| A   = anggota aktif (termasuk admin)
| Adm = admin
*/

// ---------------------------------------------------------------- Publik (P)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/divisi', [StrukturController::class, 'divisi']);
// Draf 2.5 (KP-01.2): pengunjung hanya melihat struktur periode saat ini.
Route::get('/publik/struktur', [StrukturController::class, 'publik']);

// Publikasi untuk pengunjung (KF-17): hanya materi terbit, isi versi publik.
Route::get('/publik/materi', [MateriPublikController::class, 'index']);
Route::get('/publik/materi/{materi:slug}', [MateriPublikController::class, 'show']);

// Konten beranda (KF-18): poster + cerita Al-Futuuhat.
Route::get('/publik/beranda', [BerandaController::class, 'publik']);

// Kategori materi (KF-19): daftar penyaring, dibaca anggota maupun pengunjung.
Route::get('/kategori', [KategoriController::class, 'index']);

// Program kegiatan rutin (KF-21): penjelasan + dokumentasi, hanya baca.
Route::get('/publik/program', [ProgramPublikController::class, 'index']);
Route::get('/publik/program/{program:slug}', [ProgramPublikController::class, 'show']);
Route::get('/publik/program/{program:slug}/dokumentasi', [ProgramPublikController::class, 'dokumentasi']);

// -------------------------------------------------------- Anggota aktif (A)
Route::middleware(['auth:sanctum', 'aktif'])->group(function () {
    // Akun
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/dasbor', [DasborController::class, 'index']);

    // Profil sendiri (KF-24). POST karena multipart (foto).
    Route::get('/profil', [ProfilController::class, 'show']);
    Route::post('/profil', [ProfilController::class, 'update']);
    Route::put('/profil/password', [ProfilController::class, 'gantiKataSandi']);

    // Struktur organisasi per periode + riwayat perubahan (KP-01.3, KP-07.8).
    Route::get('/periode', [StrukturController::class, 'periode']);
    Route::get('/periode/{periode}/struktur', [StrukturController::class, 'struktur']);
    Route::get('/periode/{periode}/riwayat', [StrukturController::class, 'riwayat']);

    // Materi
    Route::get('/materi', [MateriController::class, 'index']);
    Route::get('/materi/{materi:slug}', [MateriController::class, 'show']);
    Route::get('/materi-saya', [MateriController::class, 'materiSaya']);
    Route::post('/materi', [MateriController::class, 'store']);
    Route::get('/materi/{materi}/edit', [MateriController::class, 'edit']);
    Route::get('/materi/{materi}/riwayat', [MateriController::class, 'riwayat']);
    Route::get('/materi/{materi}/riwayat/{revisi}', [MateriController::class, 'versi']);
    Route::put('/materi/{materi}', [MateriController::class, 'update']);
    Route::delete('/materi/{materi}', [MateriController::class, 'destroy']);
    Route::post('/materi/{materi}/ajukan', [MateriController::class, 'ajukan']);
    Route::post('/materi/{materi}/batal-ajukan', [MateriController::class, 'batalAjukan']);
    Route::post('/materi/{materi}/revisi', [MateriController::class, 'buatRevisi']);
    // Penarikan materi dari publik oleh penulis (KF-20)
    Route::post('/materi/{materi}/tarik', [PenarikanMateriController::class, 'store']);
    Route::post('/materi/{materi}/batal-tarik', [PenarikanMateriController::class, 'batal']);
    Route::post('/unggah-gambar', [UnggahGambarController::class, 'store']);
    Route::post('/unggah-sampul', [UnggahSampulController::class, 'store']);

    // Forum
    Route::get('/materi/{materi}/komentar', [KomentarController::class, 'index']);
    Route::post('/materi/{materi}/komentar', [KomentarController::class, 'store']);
    Route::get('/komentar/{komentar}/balasan', [KomentarController::class, 'balasan']);
    Route::delete('/komentar/{komentar}', [KomentarController::class, 'destroy']);

    // Notifikasi
    Route::get('/notifikasi', [NotifikasiController::class, 'index']);
    Route::patch('/notifikasi/baca-semua', [NotifikasiController::class, 'bacaSemua']);
    Route::patch('/notifikasi/{id}/baca', [NotifikasiController::class, 'baca']);

    // ------------------------------------------------------------- Admin (Adm)
    Route::middleware('admin')->group(function () {
        // Pendaftaran (KF-05)
        Route::get('/admin/pendaftaran', [PendaftaranController::class, 'index']);
        Route::get('/admin/pendaftaran/{user}', [PendaftaranController::class, 'show']);
        Route::patch('/admin/pendaftaran/{user}', [PendaftaranController::class, 'update']);

        // Data anggota (KF-06)
        Route::get('/admin/anggota', [AnggotaController::class, 'index']);
        Route::get('/admin/anggota/{user}', [AnggotaController::class, 'show']);
        Route::put('/admin/anggota/{user}', [AnggotaController::class, 'update']);
        Route::patch('/admin/anggota/{user}/status', [AnggotaController::class, 'updateStatus']);
        Route::patch('/admin/anggota/{user}/password', [AnggotaController::class, 'updatePassword']);

        // Struktur organisasi (KF-07). Draf 2.5: periode dibuat otomatis
        // (KP-07.1), jadi tidak ada lagi rute tambah/ubah/hapus/aktifkan periode.
        Route::post('/admin/divisi', [AdminDivisiController::class, 'store']);
        Route::put('/admin/divisi/{divisi}', [AdminDivisiController::class, 'update']);
        Route::delete('/admin/divisi/{divisi}', [AdminDivisiController::class, 'destroy']);

        Route::get('/admin/jabatan', [AdminJabatanController::class, 'index']);
        Route::post('/admin/jabatan', [AdminJabatanController::class, 'store']);
        Route::put('/admin/jabatan/{jabatan}', [AdminJabatanController::class, 'update']);
        Route::delete('/admin/jabatan/{jabatan}', [AdminJabatanController::class, 'destroy']);

        Route::get('/admin/kepengurusan', [KepengurusanController::class, 'index']);
        Route::post('/admin/kepengurusan', [KepengurusanController::class, 'store']);
        Route::put('/admin/kepengurusan/{kepengurusan}', [KepengurusanController::class, 'update']);
        Route::delete('/admin/kepengurusan/{kepengurusan}', [KepengurusanController::class, 'destroy']);

        // Verifikasi publikasi materi (KF-11)
        Route::get('/admin/verifikasi-materi', [VerifikasiMateriController::class, 'index']);
        Route::get('/admin/revisi/{revisi}', [VerifikasiMateriController::class, 'show']);
        Route::post('/admin/revisi/{revisi}/verifikasi', [VerifikasiMateriController::class, 'verifikasi']);

        // Verifikasi penarikan materi dari publik (KF-20)
        Route::get('/admin/penarikan-materi', [AdminPenarikanMateriController::class, 'index']);
        Route::post('/admin/penarikan/{penarikan}/verifikasi', [AdminPenarikanMateriController::class, 'verifikasi']);

        // Konten beranda (KF-18)
        Route::get('/admin/beranda', [BerandaController::class, 'tampil']);
        Route::post('/admin/beranda', [BerandaController::class, 'simpan']);

        // Kategori materi (KF-19)
        Route::get('/admin/kategori', [AdminKategoriController::class, 'index']);
        Route::post('/admin/kategori', [AdminKategoriController::class, 'store']);
        Route::put('/admin/kategori/{kategori}', [AdminKategoriController::class, 'update']);
        Route::delete('/admin/kategori/{kategori}', [AdminKategoriController::class, 'destroy']);

        // Program & dokumentasi (KF-21). Ubah memakai POST karena multipart (sampul).
        Route::get('/admin/program', [AdminProgramController::class, 'index']);
        Route::post('/admin/program', [AdminProgramController::class, 'store']);
        Route::get('/admin/program/{program}', [AdminProgramController::class, 'show']);
        Route::post('/admin/program/{program}', [AdminProgramController::class, 'update']);
        Route::delete('/admin/program/{program}', [AdminProgramController::class, 'destroy']);
        Route::post('/admin/program/{program}/dokumentasi', [AdminDokumentasiProgramController::class, 'store']);
        Route::delete('/admin/dokumentasi/{dokumentasi}', [AdminDokumentasiProgramController::class, 'destroy']);

        // Dasbor admin
        Route::get('/admin/dasbor', [AdminDasborController::class, 'index']);
        Route::get('/admin/statistik', [AdminStatistikController::class, 'index']); // KF-25
    });
});
