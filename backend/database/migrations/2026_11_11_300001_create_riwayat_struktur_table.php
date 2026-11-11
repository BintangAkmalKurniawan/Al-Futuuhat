<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat perubahan struktur organisasi per periode (KF-07, KP-07.7).
 * Kalimat keterangan dan data sebelum/sesudah disimpan apa adanya saat
 * kejadian, sehingga riwayat tidak ikut berubah bila nama anggota,
 * divisi, atau jabatan diganti di kemudian hari.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_struktur', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periode_id')->index()->constrained('periode_kepengurusan')->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->string('aksi'); // tambah | ubah | hapus | periode_baru
            $table->text('keterangan');
            $table->jsonb('data_sebelum')->nullable(); // { nama, jabatan, divisi }
            $table->jsonb('data_sesudah')->nullable();
            $table->timestamps();

            $table->index('aksi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_struktur');
    }
};
