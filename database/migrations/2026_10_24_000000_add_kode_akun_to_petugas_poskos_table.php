<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Satu posko bisa memakai beberapa HP (HP Induk + HP Anggota) yang akunnya
 * disinkronkan, jadi petugas yang sama ikut di paket dari setiap HP.
 * kode_akun (kode unik akun dari aplikasi, sama di semua HP) dipakai untuk
 * menampilkan setiap petugas SATU kali. Kosong untuk paket versi lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('petugas_poskos', function (Blueprint $table) {
            $table->string('kode_akun', 36)->nullable()->after('kode_perangkat');
        });
    }

    public function down(): void
    {
        Schema::table('petugas_poskos', function (Blueprint $table) {
            $table->dropColumn('kode_akun');
        });
    }
};
