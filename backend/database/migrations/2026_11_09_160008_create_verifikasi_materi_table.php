<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catatan keputusan admin atas sebuah revisi — PRD §10, KP-11.5.
     * Satu revisi bisa punya beberapa baris bila admin memutuskan lebih dari sekali
     * (mis. ditolak lalu diajukan ulang), karena itu bukan unique.
     */
    public function up(): void
    {
        Schema::create('verifikasi_materi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('revisi_id')->index()->constrained('revisi_materi')->cascadeOnDelete();
            $table->foreignId('admin_id')->index()->constrained('users')->restrictOnDelete();
            $table->string('keputusan'); // disetujui | ditolak
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index('keputusan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verifikasi_materi');
    }
};
