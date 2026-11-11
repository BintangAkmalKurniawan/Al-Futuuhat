<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot PIC (penanggung jawab) program — satu program boleh memiliki
 * lebih dari satu PIC, dipilih dari tabel `anggota` (KF-21, KP-21.2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pic_program', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('program')->cascadeOnDelete();
            $table->foreignId('anggota_id')->index()->constrained('anggota')->cascadeOnDelete();
            $table->timestamps();

            // Satu anggota tidak boleh tercatat dua kali sebagai PIC program yang sama.
            $table->unique(['program_id', 'anggota_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pic_program');
    }
};
