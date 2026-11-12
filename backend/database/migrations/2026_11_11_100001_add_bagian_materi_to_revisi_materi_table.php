<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Bagian materi per versi
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('revisi_materi', function (Blueprint $table) {
            $table->string('judul')->nullable();
            $table->text('ringkasan')->nullable();
            $table->string('sampul')->nullable();
        });

        DB::statement(
            'UPDATE revisi_materi SET judul = materi.judul, ringkasan = materi.ringkasan, sampul = materi.sampul
             FROM materi WHERE revisi_materi.materi_id = materi.id'
        );

        Schema::table('revisi_materi', function (Blueprint $table) {
            $table->string('judul')->nullable(false)->change();
        });

        DB::statement("CREATE INDEX revisi_materi_judul_fts_index ON revisi_materi USING GIN (to_tsvector('simple', judul))");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS revisi_materi_judul_fts_index');

        Schema::table('revisi_materi', function (Blueprint $table) {
            $table->dropColumn(['judul', 'ringkasan', 'sampul']);
        });
    }
};
