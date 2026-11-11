<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Konten beranda yang diatur admin: poster dan cerita Al-Futuuhat (KF-18).
     * Tabel ini hanya dipakai satu baris pengaturan.
     */
    public function up(): void
    {
        Schema::create('pengaturan_beranda', function (Blueprint $table) {
            $table->id();
            $table->string('poster')->nullable(); // path di disk `public`
            $table->string('cerita_judul')->nullable();
            $table->text('cerita_isi')->nullable(); // HTML dari editor Quill
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan_beranda');
    }
};
