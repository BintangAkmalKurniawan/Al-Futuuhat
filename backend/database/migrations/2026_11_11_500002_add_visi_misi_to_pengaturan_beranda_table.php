<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Visi dan misi
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaturan_beranda', function (Blueprint $table) {
            $table->text('visi')->nullable();
            $table->text('misi')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pengaturan_beranda', function (Blueprint $table) {
            $table->dropColumn(['visi', 'misi']);
        });
    }
};
