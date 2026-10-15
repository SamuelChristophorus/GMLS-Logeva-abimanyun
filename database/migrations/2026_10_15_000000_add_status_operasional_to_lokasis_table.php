<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan status operasional pada lokasi TEA.
     *  - siaga      : lokasi sudah terdaftar/disetujui, belum menampung pengungsi
     *  - beroperasi : posko sedang aktif menampung pengungsi
     *  - selesai    : operasi posko sudah ditutup
     */
    public function up(): void
    {
        Schema::table('lokasis', function (Blueprint $table) {
            $table->enum('status_operasional', ['siaga', 'beroperasi', 'selesai'])
                  ->default('siaga')
                  ->after('longitude');

            $table->timestamp('mulai_beroperasi_at')->nullable()->after('status_operasional');
            $table->timestamp('selesai_beroperasi_at')->nullable()->after('mulai_beroperasi_at');
        });
    }

    public function down(): void
    {
        Schema::table('lokasis', function (Blueprint $table) {
            $table->dropColumn([
                'status_operasional',
                'mulai_beroperasi_at',
                'selesai_beroperasi_at',
            ]);
        });
    }
};
