<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Susunan pengurus per periode — PRD §10.
     * divisi_id boleh kosong untuk pengurus inti (mis. Ketua).
     *
     * Catatan: relasi ke periode/divisi/jabatan/anggota memakai RESTRICT,
     * sehingga penghapusan data yang masih dipakai ditolak oleh basis data
     * (KP-07.5) dan pesannya dibuat di controller, bukan error 500.
     */
    public function up(): void
    {
        Schema::create('kepengurusan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anggota_id')->index()->constrained('anggota')->restrictOnDelete();
            $table->foreignId('periode_id')->index()->constrained('periode_kepengurusan')->restrictOnDelete();
            $table->foreignId('divisi_id')->nullable()->index()->constrained('divisi')->restrictOnDelete();
            $table->foreignId('jabatan_id')->index()->constrained('jabatan')->restrictOnDelete();
            $table->timestamps();

            // Satu anggota hanya boleh tercatat sekali pada periode yang sama (KP-07.4)
            $table->unique(['anggota_id', 'periode_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kepengurusan');
    }
};
