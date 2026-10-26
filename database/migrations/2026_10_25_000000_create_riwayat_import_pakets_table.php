<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat setiap Paket Data Posko yang digabung: siapa mengimpor, kapan, dari HP
 * mana, dan berapa data yang masuk. Dipakai untuk menelusuri asal data, dan untuk
 * menampilkan "paket terakhir" per posko (posko offline tidak punya tanda lain
 * apakah datanya masih baru).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_import_pakets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // admin pengimpor
            $table->string('nama_file');
            $table->string('kode_perangkat', 36)->nullable();
            $table->string('peran_perangkat', 10)->nullable(); // induk / anggota (kosong = paket versi lama)
            $table->string('diekspor_oleh')->nullable();
            $table->dateTime('paket_dibuat_pada')->nullable(); // waktu paket dibuat di HP (WIB)
            $table->json('jumlah'); // posko, pengungsi, dihapus, masuk, keluar, barang, petugas
            $table->timestamps(); // created_at = waktu diimpor
        });

        Schema::create('lokasi_riwayat_import_paket', function (Blueprint $table) {
            $table->foreignId('lokasi_id')->constrained()->cascadeOnDelete();
            $table->foreignId('riwayat_import_paket_id')->constrained()->cascadeOnDelete();
            $table->primary(['lokasi_id', 'riwayat_import_paket_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lokasi_riwayat_import_paket');
        Schema::dropIfExists('riwayat_import_pakets');
    }
};
