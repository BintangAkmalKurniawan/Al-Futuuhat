<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Susunan pengurus per periode
    public function up(): void
    {
        Schema::create('kepengurusan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anggota_id')->index()->constrained('anggota')->restrictOnDelete();
            $table->foreignId('periode_id')->index()->constrained('periode_kepengurusan')->restrictOnDelete();
            $table->foreignId('divisi_id')->nullable()->index()->constrained('divisi')->restrictOnDelete();
            $table->foreignId('jabatan_id')->index()->constrained('jabatan')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['anggota_id', 'periode_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kepengurusan');
    }
};
