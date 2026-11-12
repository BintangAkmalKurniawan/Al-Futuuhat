<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Konten beranda yang diatur admin
    public function up(): void
    {
        Schema::create('pengaturan_beranda', function (Blueprint $table) {
            $table->id();
            $table->string('poster')->nullable();
            $table->string('cerita_judul')->nullable();
            $table->text('cerita_isi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan_beranda');
    }
};
