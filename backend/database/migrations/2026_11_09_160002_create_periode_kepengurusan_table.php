<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Periode kepengurusan, mis. "2026/2027" — PRD §10, KF-07.
     * Hanya satu periode boleh aktif (dijaga di controller, KP-07.1).
     */
    public function up(): void
    {
        Schema::create('periode_kepengurusan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_periode');
            $table->unsignedSmallInteger('tahun_mulai');
            $table->unsignedSmallInteger('tahun_selesai');
            $table->boolean('is_aktif')->default(false);
            $table->timestamps();

            // Beranda publik mencari periode aktif (KP-01.2)
            $table->index('is_aktif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periode_kepengurusan');
    }
};
