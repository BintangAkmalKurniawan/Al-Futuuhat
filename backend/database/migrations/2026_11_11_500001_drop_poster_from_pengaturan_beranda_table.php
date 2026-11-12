<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Hapus poster beranda
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
