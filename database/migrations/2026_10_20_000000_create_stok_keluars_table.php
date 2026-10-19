<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catatan barang keluar dari posko (dibagikan, dimasak, dikirim, rusak).
     * Pasangan dari stok_masuks: sisa stok = total masuk - total keluar.
     */
    public function up(): void
    {
        Schema::create('stok_keluars', function (Blueprint $table) {

            $table->id();

            $table->foreignId('lokasi_id')
                ->constrained('lokasis')
                ->cascadeOnDelete();

            $table->foreignId('logistik_id')
                ->constrained('logistiks')
                ->cascadeOnDelete();

            // Petugas yang mencatat (admin juga bisa mencatat, jadi pakai user)
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->integer('jumlah_keluar');

            // dibagikan | dapur_umum | dikirim | rusak | lainnya (lihat StokKeluar::TUJUAN)
            $table->string('tujuan', 30);

            $table->string('penerima')->nullable();

            $table->date('tanggal_keluar');

            $table->text('keterangan')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stok_keluars');
    }
};
