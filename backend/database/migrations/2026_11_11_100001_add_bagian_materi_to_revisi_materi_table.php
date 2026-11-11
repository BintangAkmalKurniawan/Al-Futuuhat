<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Draf 2.3 (PRD §8.2, KP-12.4): judul, ringkasan, dan sampul disimpan per versi,
 * supaya penulis dapat mengubahnya sebagai draf tanpa mengubah versi publik.
 * Kolom yang sama di tabel `materi` kini berarti "data versi publik".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('revisi_materi', function (Blueprint $table) {
            // Sementara boleh kosong agar data lama bisa diisi dulu di bawah.
            $table->string('judul')->nullable();
            $table->text('ringkasan')->nullable();
            $table->string('sampul')->nullable(); // path gambar di disk `public` (KF-18)
        });

        // Data lama: setiap versi memakai judul, ringkasan, dan sampul materinya.
        DB::statement(
            'UPDATE revisi_materi SET judul = materi.judul, ringkasan = materi.ringkasan, sampul = materi.sampul
             FROM materi WHERE revisi_materi.materi_id = materi.id'
        );

        // Setelah terisi, judul wajib ada seperti di tabel `materi`.
        Schema::table('revisi_materi', function (Blueprint $table) {
            $table->string('judul')->nullable(false)->change();
        });

        // Indeks GIN untuk pencarian internal atas judul revisi terakhir (PRD §10, KP-16.1).
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
