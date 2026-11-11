<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Gambar sampul materi (KF-18): dipakai kartu artikel publik dan daftar
     * materi di dalam aplikasi. Isinya path di disk `public`, bukan URL.
     */
    public function up(): void
    {
        Schema::table('materi', function (Blueprint $table) {
            $table->string('sampul')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('materi', function (Blueprint $table) {
            $table->dropColumn('sampul');
        });
    }
};
