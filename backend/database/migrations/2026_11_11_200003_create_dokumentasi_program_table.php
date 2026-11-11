<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dokumentasi program: foto, video (berkas MP4), atau tautan YouTube (KF-21, KP-21.3).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumentasi_program', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->index()->constrained('program')->cascadeOnDelete();
            $table->string('jenis'); // foto | video | youtube
            $table->string('path')->nullable(); // berkas foto/video di disk `public`
            $table->string('youtube_id', 20)->nullable(); // ID video bila jenis = youtube
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();

            $table->index('jenis');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumentasi_program');
    }
};
