<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pengajuan penarikan materi dari publik (KF-20).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penarikan_materi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('materi_id')->index()->constrained('materi')->cascadeOnDelete();
            $table->text('alasan'); // wajib diisi penulis (KP-20.1)
            $table->string('status')->default('diajukan'); // diajukan | disetujui | ditolak | dibatalkan
            // Diisi saat admin memutuskan (KP-20.5, KP-20.6).
            $table->foreignId('admin_id')->nullable()->index()->constrained('users')->restrictOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamp('diputuskan_pada')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penarikan_materi');
    }
};
