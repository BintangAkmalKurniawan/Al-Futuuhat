<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Versi/riwayat isi materi — PRD §10, KF-12/KF-13.
     * Versi terbit = revisi berstatus `disetujui` dengan nomor_versi terbesar (PRD §8.2).
     */
    public function up(): void
    {
        Schema::create('revisi_materi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('materi_id')->index()->constrained('materi')->cascadeOnDelete();
            $table->unsignedInteger('nomor_versi');
            $table->jsonb('konten'); // Delta Quill (KP-08.4)
            $table->text('konten_html'); // HTML hasil sanitasi, dipakai untuk tampilan dan pencarian
            $table->text('catatan_revisi')->nullable();
            $table->string('status')->default('draf'); // draf | diajukan | disetujui | ditolak
            $table->timestamps();

            // Satu nomor versi hanya sekali per materi
            $table->unique(['materi_id', 'nomor_versi']);
            $table->index('status');
        });

        // Indeks GIN pencarian isi materi (PRD §10). Tag HTML dibuang lebih dulu
        // supaya yang diindeks hanya teksnya. `regexp_replace` + `to_tsvector`
        // keduanya immutable sehingga aman dipakai sebagai indeks ekspresi.
        DB::statement(
            "CREATE INDEX revisi_materi_konten_fts_index ON revisi_materi "
            ."USING GIN (to_tsvector('simple', regexp_replace(konten_html, '<[^>]+>', ' ', 'g')))"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('revisi_materi');
    }
};
