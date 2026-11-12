<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Periode kepengurusan
    public function up(): void
    {
        Schema::create('periode_kepengurusan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_periode');
            $table->unsignedSmallInteger('tahun_mulai');
            $table->unsignedSmallInteger('tahun_selesai');
            $table->boolean('is_aktif')->default(false);
            $table->timestamps();

            $table->index('is_aktif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periode_kepengurusan');
    }
};
