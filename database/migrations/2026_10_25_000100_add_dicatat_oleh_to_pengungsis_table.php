<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nama petugas yang mencatat pengungsi di aplikasi offline (pengungsi.csv →
 * dicatat_oleh), supaya pusat tahu siapa yang bisa ditanya tentang data itu.
 * Kosong untuk data yang dibuat langsung di website.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengungsis', function (Blueprint $table) {
            $table->string('dicatat_oleh')->nullable()->after('riwayat_penyakit');
        });
    }

    public function down(): void
    {
        Schema::table('pengungsis', function (Blueprint $table) {
            $table->dropColumn('dicatat_oleh');
        });
    }
};
