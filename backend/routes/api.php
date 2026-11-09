<?php

use App\Http\Controllers\Admin\AnggotaController;
use App\Http\Controllers\Admin\DasborController as AdminDasborController;
use App\Http\Controllers\Admin\DivisiController as AdminDivisiController;
use App\Http\Controllers\Admin\JabatanController as AdminJabatanController;
use App\Http\Controllers\Admin\KepengurusanController;
use App\Http\Controllers\Admin\PendaftaranController;
use App\Http\Controllers\Admin\PeriodeController as AdminPeriodeController;
use App\Http\Controllers\Admin\VerifikasiMateriController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DasborController;
use App\Http\Controllers\KomentarController;
use App\Http\Controllers\MateriController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\StrukturController;
use App\Http\Controllers\UnggahGambarController;
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
Route::get('/periode', [StrukturController::class, 'periode']);
Route::get('/periode/{periode}/struktur', [StrukturController::class, 'struktur']);

// -------------------------------------------------------- Anggota aktif (A)
Route::middleware(['auth:sanctum', 'aktif'])->group(function () {
    // Akun
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/dasbor', [DasborController::class, 'index']);

    // Materi
    Route::get('/materi', [MateriController::class, 'index']);
    Route::get('/materi-saya', [MateriController::class, 'materiSaya']);
    Route::post('/materi', [MateriController::class, 'store']);
    Route::get('/materi/{materi}/edit', [MateriController::class, 'edit']);
    Route::get('/materi/{materi}/riwayat', [MateriController::class, 'riwayat']);
    Route::put('/materi/{materi}', [MateriController::class, 'update']);
    Route::delete('/materi/{materi}', [MateriController::class, 'destroy']);
    Route::post('/materi/{materi}/ajukan', [MateriController::class, 'ajukan']);
    Route::post('/materi/{materi}/revisi', [MateriController::class, 'buatRevisi']);
    // Dibaca berdasarkan slug; diletakkan setelah rute /materi/{materi}/... agar tidak bentrok.
    Route::get('/materi/{materi:slug}', [MateriController::class, 'show']);
    Route::post('/unggah-gambar', [UnggahGambarController::class, 'store']);

    // Forum
    Route::get('/materi/{materi}/komentar', [KomentarController::class, 'index']);
    Route::post('/materi/{materi}/komentar', [KomentarController::class, 'store']);
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

        // Struktur organisasi (KF-07)
        Route::post('/admin/periode', [AdminPeriodeController::class, 'store']);
        Route::put('/admin/periode/{periode}', [AdminPeriodeController::class, 'update']);
        Route::delete('/admin/periode/{periode}', [AdminPeriodeController::class, 'destroy']);
        Route::patch('/admin/periode/{periode}/aktifkan', [AdminPeriodeController::class, 'aktifkan']);

        Route::post('/admin/divisi', [AdminDivisiController::class, 'store']);
        Route::put('/admin/divisi/{divisi}', [AdminDivisiController::class, 'update']);
        Route::delete('/admin/divisi/{divisi}', [AdminDivisiController::class, 'destroy']);

        Route::get('/admin/jabatan', [AdminJabatanController::class, 'index']);
        Route::post('/admin/jabatan', [AdminJabatanController::class, 'store']);
        Route::put('/admin/jabatan/{jabatan}', [AdminJabatanController::class, 'update']);
        Route::delete('/admin/jabatan/{jabatan}', [AdminJabatanController::class, 'destroy']);

        Route::post('/admin/kepengurusan', [KepengurusanController::class, 'store']);
        Route::put('/admin/kepengurusan/{kepengurusan}', [KepengurusanController::class, 'update']);
        Route::delete('/admin/kepengurusan/{kepengurusan}', [KepengurusanController::class, 'destroy']);

        // Verifikasi publikasi materi (KF-11)
        Route::get('/admin/verifikasi-materi', [VerifikasiMateriController::class, 'index']);
        Route::get('/admin/revisi/{revisi}', [VerifikasiMateriController::class, 'show']);
        Route::post('/admin/revisi/{revisi}/verifikasi', [VerifikasiMateriController::class, 'verifikasi']);

        // Dasbor admin
        Route::get('/admin/dasbor', [AdminDasborController::class, 'index']);
    });
});
