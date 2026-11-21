<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Posko WAJIB punya satu relawan penanggung jawab (lokasis.relawan_id tetap
 * tidak boleh kosong). Yang diubah hanya aturan hapus: dulu akun relawan
 * dihapus permanen -> posko beserta data pengungsinya IKUT terhapus (cascade).
 * Sekarang ditolak (restrict): ganti penanggung jawab posko dulu.
 */
return new class extends Migration
{
    public function up(): void
    {
        $kosong = DB::table('lokasis')->whereNull('relawan_id')->count();
        if ($kosong > 0) {
            throw new RuntimeException("{$kosong} posko belum punya relawan penanggung jawab. "
                . 'Isi dulu lewat Edit Posko, lalu jalankan migrate lagi.');
        }

        Schema::table('lokasis', function (Blueprint $table) {
            $table->dropForeign(['relawan_id']);
        });
        Schema::table('lokasis', function (Blueprint $table) {
            $table->foreignId('relawan_id')->nullable(false)->change();
            $table->foreign('relawan_id')->references('id')->on('relawans')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lokasis', function (Blueprint $table) {
            $table->dropForeign(['relawan_id']);
        });
        Schema::table('lokasis', function (Blueprint $table) {
            $table->foreign('relawan_id')->references('id')->on('relawans')->cascadeOnDelete();
        });
    }
};
