<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Materi (induk tulisan) — PRD §10.
     * Isi tulisan disimpan per versi di tabel `revisi_materi`.
     */
    public function up(): void
    {
        Schema::create('materi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penulis_id')->index()->constrained('users')->restrictOnDelete();
            $table->string('judul');
            $table->string('slug')->unique();
            $table->text('ringkasan')->nullable();
            $table->string('status')->default('draf'); // draf | diajukan | terbit | ditolak (PRD §8.2)
            $table->timestamp('diterbitkan_pada')->nullable(); // diisi saat pertama kali terbit (KP-11.3)
            $table->timestamps();

            $table->index('status');
        });

        // Indeks GIN untuk pencarian judul materi (PRD §10, KP-16.1).
        // Bentuk dua argumen `to_tsvector('simple', judul)` bersifat immutable,
        // sehingga boleh dipakai sebagai indeks ekspresi.
        DB::statement("CREATE INDEX materi_judul_fts_index ON materi USING GIN (to_tsvector('simple', judul))");
    }

    public function down(): void
    {
        // Indeks ikut terhapus bersama tabelnya
        Schema::dropIfExists('materi');
    }
};
