<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Catatan keputusan admin atas sebuah revisi
    public function up(): void
    {
        Schema::create('verifikasi_materi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('revisi_id')->index()->constrained('revisi_materi')->cascadeOnDelete();
            $table->foreignId('admin_id')->index()->constrained('users')->restrictOnDelete();
            $table->string('keputusan');
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
