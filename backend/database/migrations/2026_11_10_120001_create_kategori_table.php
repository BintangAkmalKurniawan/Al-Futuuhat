<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kategori materi, mis. Fiqih, Akhlak, Sejarah Islam (KF-19) — PRD §10.
     * Daftar kategori diatur admin; satu materi boleh punya beberapa kategori.
     */
    public function up(): void
    {
        Schema::create('kategori', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();
            $table->string('slug')->unique(); // dipakai penyaring di URL: ?kategori=fiqih
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kategori');
    }
};
