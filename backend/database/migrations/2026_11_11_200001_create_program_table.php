<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Program: kegiatan rutin Al-Futuuhat yang dikelola admin (KF-21).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 150)->unique();
            $table->string('slug')->unique();
            $table->string('ringkasan', 300)->nullable();
            $table->string('jadwal', 150)->nullable(); // teks bebas, mis. "Setiap Jumat malam"
            $table->string('sampul')->nullable(); // path gambar di disk `public`
            $table->text('deskripsi'); // HTML Quill yang sudah disanitasi
            $table->string('status')->default('aktif'); // aktif | selesai
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program');
    }
};
