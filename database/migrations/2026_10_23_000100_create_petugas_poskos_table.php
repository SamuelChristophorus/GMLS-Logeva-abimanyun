<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Petugas lapangan dari aplikasi offline (petugas.csv di Paket Data Posko):
 * admin lokal = penanggung jawab (supervisor), relawan lokal = petugas posko.
 * Hanya nama & kontak, supaya pusat tahu siapa yang bisa dihubungi.
 * Ini BUKAN akun website (tidak bisa login); PIN/password tidak pernah dikirim.
 *
 * Satu kumpulan baris per (lokasi, kode_perangkat), diganti utuh setiap kali paket
 * yang lebih baru dari perangkat itu diimpor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('petugas_poskos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lokasi_id')->constrained()->cascadeOnDelete();
            $table->string('kode_perangkat', 36);
            $table->string('peran', 10); // admin / relawan
            $table->string('nama');
            $table->string('kontak')->nullable();
            $table->timestamp('paket_dibuat_pada')->nullable();
            $table->timestamps();

            $table->index(['lokasi_id', 'kode_perangkat']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petugas_poskos');
    }
};
