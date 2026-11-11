<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Draf 2.9: fitur poster beranda dihapus. Berkas poster lama di
 * storage/app/public/beranda tidak dihapus otomatis.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaturan_beranda', function (Blueprint $table) {
            $table->dropColumn('poster');
        });
    }

    public function down(): void
    {
        Schema::table('pengaturan_beranda', function (Blueprint $table) {
            $table->string('poster')->nullable();
        });
    }
};
