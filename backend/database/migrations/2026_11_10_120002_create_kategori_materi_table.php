<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot kategori per materi (KF-19, KP-19.2) — banyak kategori per materi.
     */
    public function up(): void
    {
        Schema::create('kategori_materi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('materi_id')->constrained('materi')->cascadeOnDelete();
            $table->foreignId('kategori_id')->constrained('kategori')->cascadeOnDelete();
            $table->timestamps();

            // Satu kategori tidak boleh terpasang dua kali pada materi yang sama.
            $table->unique(['materi_id', 'kategori_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kategori_materi');
    }
};
