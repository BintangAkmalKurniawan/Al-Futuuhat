<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Dokumentasi program
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumentasi_program', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->index()->constrained('program')->cascadeOnDelete();
            $table->string('jenis');
            $table->string('path')->nullable();
            $table->string('youtube_id', 20)->nullable();
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
