<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Kategori per versi
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori_revisi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('revisi_id')->constrained('revisi_materi')->cascadeOnDelete();
            $table->foreignId('kategori_id')->constrained('kategori')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['revisi_id', 'kategori_id']);
        });

        DB::statement(
            'INSERT INTO kategori_revisi (revisi_id, kategori_id, created_at, updated_at)
             SELECT revisi_materi.id, kategori_materi.kategori_id, NOW(), NOW()
             FROM revisi_materi
             JOIN kategori_materi ON kategori_materi.materi_id = revisi_materi.materi_id'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('kategori_revisi');
    }
};
