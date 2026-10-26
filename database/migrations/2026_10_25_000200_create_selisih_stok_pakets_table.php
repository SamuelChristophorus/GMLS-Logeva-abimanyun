<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Barang yang stoknya di HP (stok.csv) berbeda dengan stok pusat setelah sebuah
 * paket digabung. Hanya catatan untuk diselidiki; stok pusat tidak diubah.
 * Penyebab umum: catatan dibuat langsung di website, barang keluar melebihi stok
 * di HP Anggota (stok HP minus), atau HP lain belum disinkronkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('selisih_stok_pakets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('riwayat_import_paket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lokasi_id')->constrained()->cascadeOnDelete();
            $table->string('nama_item');
            $table->string('satuan', 50)->nullable();
            $table->integer('stok_hp');    // boleh minus (barang keluar di HP Anggota)
            $table->integer('stok_pusat');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('selisih_stok_pakets');
    }
};
