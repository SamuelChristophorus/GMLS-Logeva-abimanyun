<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom untuk menggabungkan PAKET DATA POSKO dari aplikasi offline
 * (docs/SKEMA-DATABASE-UNTUK-APLIKASI.md §8) tanpa data dobel.
 *
 *  - kode (UUID dari aplikasi) di lokasis, pengungsis, stok_masuks, stok_keluars:
 *    data yang kodenya sudah ada = sudah pernah diimpor. Kosong untuk data
 *    yang dibuat langsung di website.
 *  - pengungsis.diubah_pada_perangkat: versi data di HP saat terakhir diimpor,
 *    supaya paket lama tidak menimpa data yang lebih baru.
 *  - paket_penghapusans: daftar kode yang sudah dihapus, supaya paket lama
 *    yang diimpor belakangan tidak memunculkan lagi pengungsi yang sudah dihapus.
 *
 * Hanya MENAMBAH kolom/tabel; data lama tidak diubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lokasis', function (Blueprint $table) {
            $table->string('kode', 36)->nullable()->unique()->after('id');
        });

        Schema::table('pengungsis', function (Blueprint $table) {
            $table->string('kode', 36)->nullable()->unique()->after('id');
            $table->timestamp('diubah_pada_perangkat')->nullable()->after('riwayat_penyakit');
        });

        Schema::table('stok_masuks', function (Blueprint $table) {
            $table->string('kode', 36)->nullable()->unique()->after('id');
        });

        Schema::table('stok_keluars', function (Blueprint $table) {
            $table->string('kode', 36)->nullable()->unique()->after('id');
        });

        Schema::create('paket_penghapusans', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 36)->unique();
            $table->string('jenis', 20); // 'pengungsi'
            $table->timestamp('dihapus_pada')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paket_penghapusans');

        foreach (['stok_keluars', 'stok_masuks', 'lokasis'] as $tabel) {
            Schema::table($tabel, function (Blueprint $table) {
                $table->dropUnique(['kode']);
                $table->dropColumn('kode');
            });
        }

        Schema::table('pengungsis', function (Blueprint $table) {
            $table->dropUnique(['kode']);
            $table->dropColumn(['kode', 'diubah_pada_perangkat']);
        });
    }
};
