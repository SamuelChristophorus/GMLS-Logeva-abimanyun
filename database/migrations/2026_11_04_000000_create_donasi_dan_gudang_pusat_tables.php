<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FITUR DONASI + GUDANG PUSAT GMLS
 *
 *  - gudang_pusats   : "posko" milik pusat (GMLS) untuk menampung bantuan sebelum disalurkan.
 *                      Sengaja dipisah dari lokasis supaya tidak ikut hitungan pengungsi / status bantuan posko.
 *  - stok_gudangs    : stok barang per gudang pusat.
 *  - mutasi_gudangs  : riwayat barang masuk/keluar gudang pusat (keluar = dikirim ke posko).
 *  - donasis         : donasi dari donatur (uang / logistik) untuk GMLS pusat atau posko tertentu.
 *  - donasi_items    : rincian barang untuk donasi logistik.
 *  - penyaluran_donasis : ke mana donasi disalurkan (dana dipakai / barang dikirim ke posko).
 *  - donasi_jejaks   : jejak (timeline) yang bisa dilacak donatur dengan kode donasi.
 *
 * Hanya MENAMBAH tabel; data lama tidak diubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gudang_pusats', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->text('alamat')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('penanggung_jawab')->nullable();
            $table->string('kontak', 30)->nullable();
            // Rekening penerima donasi uang
            $table->string('nama_bank', 60)->nullable();
            $table->string('nomor_rekening', 40)->nullable();
            $table->string('atas_nama_rekening')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });

        Schema::create('stok_gudangs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gudang_pusat_id')->constrained()->cascadeOnDelete();
            $table->foreignId('logistik_id')->constrained('logistiks')->cascadeOnDelete();
            $table->integer('jumlah_stok')->default(0);
            $table->timestamps();
            $table->unique(['gudang_pusat_id', 'logistik_id']);
        });

        Schema::create('donasis', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();                 // kode lacak, contoh: DN-7K3QX9MA
            $table->string('nama_donatur');
            $table->boolean('anonim')->default(false);            // nama disembunyikan di laporan publik
            $table->string('kontak', 100);                        // no. HP / email (tidak ditampilkan ke publik)
            $table->string('jenis', 10);                          // uang | logistik
            $table->string('tujuan', 10);                         // pusat | posko
            $table->foreignId('lokasi_id')->nullable()->constrained('lokasis')->nullOnDelete();
            $table->foreignId('gudang_pusat_id')->nullable()->constrained()->nullOnDelete();
            // Donasi uang
            $table->unsignedBigInteger('nominal')->nullable();          // yang dilaporkan donatur
            $table->unsignedBigInteger('nominal_diterima')->nullable(); // yang dicek admin di rekening
            $table->string('bukti_transfer')->nullable();               // disimpan privat (bukan folder publik)
            // Donasi logistik
            $table->date('perkiraan_tiba')->nullable();
            $table->string('pengirim', 120)->nullable();                // nama kurir / ekspedisi / sopir
            $table->text('pesan')->nullable();                          // pesan / doa dari donatur
            // menunggu | diterima | sebagian | selesai | ditolak
            $table->string('status', 15)->default('menunggu');
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diverifikasi_pada')->nullable();
            $table->text('catatan_admin')->nullable();
            $table->timestamps();
            $table->index(['status', 'jenis']);
        });

        Schema::create('donasi_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donasi_id')->constrained()->cascadeOnDelete();
            $table->foreignId('logistik_id')->constrained('logistiks')->cascadeOnDelete();
            $table->integer('jumlah');                                   // yang dijanjikan donatur
            $table->integer('jumlah_diterima')->nullable();              // yang benar-benar sampai
            $table->timestamps();
        });

        Schema::create('penyaluran_donasis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donasi_id')->constrained()->cascadeOnDelete();
            // Tujuan: posko (lokasi_id) atau gudang pusat (dana dibelikan barang untuk stok pusat)
            $table->foreignId('lokasi_id')->nullable()->constrained('lokasis')->nullOnDelete();
            $table->foreignId('gudang_pusat_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('nominal')->nullable();           // dana yang dipakai (donasi uang)
            $table->foreignId('logistik_id')->nullable()->constrained('logistiks')->nullOnDelete();
            $table->integer('jumlah')->nullable();                       // barang yang dikirim / dibeli
            $table->date('tanggal');
            $table->text('keterangan')->nullable();
            $table->string('foto')->nullable();                          // dokumentasi (publik)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('mutasi_gudangs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gudang_pusat_id')->constrained()->cascadeOnDelete();
            $table->foreignId('logistik_id')->constrained('logistiks')->cascadeOnDelete();
            $table->string('jenis', 10);                                 // masuk | keluar
            $table->integer('jumlah');
            $table->date('tanggal');
            $table->string('sumber')->nullable();                        // asal barang masuk
            $table->foreignId('lokasi_id')->nullable()->constrained('lokasis')->nullOnDelete(); // posko tujuan (keluar)
            $table->foreignId('donasi_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('penyaluran_donasi_id')->nullable()->constrained()->nullOnDelete();
            $table->text('keterangan')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('donasi_jejaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donasi_id')->constrained()->cascadeOnDelete();
            $table->string('judul');
            $table->text('keterangan')->nullable();
            $table->string('ikon', 40)->default('fa-circle');
            $table->string('warna', 10)->default('blue');                // green | red | amber | blue | slate
            $table->foreignId('lokasi_id')->nullable()->constrained('lokasis')->nullOnDelete();
            $table->string('foto')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        // Hubungkan barang masuk posko dengan donasi asalnya (untuk lacak & laporan)
        Schema::table('stok_masuks', function (Blueprint $table) {
            $table->foreignId('donasi_id')->nullable()->after('relawan_id')->constrained()->nullOnDelete();
        });

        // Satu gudang pusat bawaan; data bisa diubah admin di menu Gudang Pusat GMLS
        DB::table('gudang_pusats')->insert([
            'nama'       => 'Posko Pusat GMLS',
            'alamat'     => 'Sekretariat Gugus Mitigasi Lebak Selatan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('stok_masuks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('donasi_id');
        });

        Schema::dropIfExists('donasi_jejaks');
        Schema::dropIfExists('mutasi_gudangs');
        Schema::dropIfExists('penyaluran_donasis');
        Schema::dropIfExists('donasi_items');
        Schema::dropIfExists('donasis');
        Schema::dropIfExists('stok_gudangs');
        Schema::dropIfExists('gudang_pusats');
    }
};
