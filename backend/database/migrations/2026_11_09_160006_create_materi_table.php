<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Materi (induk tulisan)
    public function up(): void
    {
        Schema::create('materi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penulis_id')->index()->constrained('users')->restrictOnDelete();
            $table->string('judul');
            $table->string('slug')->unique();
            $table->text('ringkasan')->nullable();
            $table->string('status')->default('draf');
            $table->timestamp('diterbitkan_pada')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        DB::statement("CREATE INDEX materi_judul_fts_index ON materi USING GIN (to_tsvector('simple', judul))");
    }

    public function down(): void
    {
        Schema::dropIfExists('materi');
    }
};
