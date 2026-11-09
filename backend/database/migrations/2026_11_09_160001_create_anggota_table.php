<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data keanggotaan (pendaftaran, verifikasi, dan data pribadi) — PRD §10.
     */
    public function up(): void
    {
        Schema::create('anggota', function (Blueprint $table) {
            $table->id();
            // Satu akun hanya boleh punya satu baris data keanggotaan
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('no_hp', 20);
            $table->text('alamat');
            $table->text('alasan_bergabung');
            $table->string('foto')->nullable(); // path di disk "public"; foto opsional (PRD §16 no. 1)
            $table->date('tanggal_bergabung')->nullable(); // diisi admin saat menyetujui (KP-05.3)
            $table->text('catatan_verifikasi')->nullable(); // alasan penolakan (KP-05.4)
            $table->timestamp('diverifikasi_pada')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anggota');
    }
};
