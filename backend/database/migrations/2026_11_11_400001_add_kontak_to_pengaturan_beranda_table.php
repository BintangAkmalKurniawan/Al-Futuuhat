<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kontak organisasi di kaki halaman publik, diatur admin (KF-22).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaturan_beranda', function (Blueprint $table) {
            $table->string('kontak_alamat', 255)->nullable();
            $table->string('kontak_whatsapp', 20)->nullable(); // hanya angka, mis. 6281234567890
            $table->string('kontak_email', 255)->nullable();
            $table->string('kontak_instagram', 50)->nullable(); // nama akun tanpa "@"
        });
    }

    public function down(): void
    {
        Schema::table('pengaturan_beranda', function (Blueprint $table) {
            $table->dropColumn(['kontak_alamat', 'kontak_whatsapp', 'kontak_email', 'kontak_instagram']);
        });
    }
};
