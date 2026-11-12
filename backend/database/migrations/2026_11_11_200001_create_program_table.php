<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Program
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 150)->unique();
            $table->string('slug')->unique();
            $table->string('ringkasan', 300)->nullable();
            $table->string('jadwal', 150)->nullable();
            $table->string('sampul')->nullable();
            $table->text('deskripsi');
            $table->string('status')->default('aktif');
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program');
    }
};
