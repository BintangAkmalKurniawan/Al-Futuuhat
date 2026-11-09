<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Komentar forum diskusi materi — PRD §10, KF-14.
     * Balasan hanya satu tingkat: baris dengan parent_id terisi tidak boleh
     * dijadikan induk lagi (dijaga di controller, KP-14.3).
     */
    public function up(): void
    {
        Schema::create('komentar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('materi_id')->index()->constrained('materi')->cascadeOnDelete();
            $table->foreignId('user_id')->index()->constrained('users')->restrictOnDelete();
            // Menghapus komentar induk ikut menghapus balasannya (KP-14.4)
            $table->foreignId('parent_id')->nullable()->index()->constrained('komentar')->cascadeOnDelete();
            $table->text('isi')->nullable(); // isi dan/atau gambar, minimal salah satu (KP-14.2)
            $table->string('gambar')->nullable(); // path di disk "public"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('komentar');
    }
};
