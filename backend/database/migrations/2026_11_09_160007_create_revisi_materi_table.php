<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Versi/riwayat isi materi
    public function up(): void
    {
        Schema::create('revisi_materi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('materi_id')->index()->constrained('materi')->cascadeOnDelete();
            $table->unsignedInteger('nomor_versi');
            $table->jsonb('konten');
            $table->text('konten_html');
            $table->text('catatan_revisi')->nullable();
            $table->string('status')->default('draf');
            $table->timestamps();

            $table->unique(['materi_id', 'nomor_versi']);
            $table->index('status');
        });

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
