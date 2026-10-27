<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Keberadaan pengungsi (dari aplikasi offline, pengungsi.csv):
 * di_posko / pulang / pindah / dirujuk. Yang sudah keluar TIDAK dihapus
 * (riwayatnya tetap ada), tapi tidak dihitung untuk kapasitas, kebutuhan,
 * dan komposisi. Data lama = di_posko.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengungsis', function (Blueprint $table) {
            $table->string('status_keberadaan', 10)->default('di_posko')->after('dicatat_oleh');
            $table->date('keluar_pada')->nullable()->after('status_keberadaan');
            $table->string('keterangan_keluar')->nullable()->after('keluar_pada');
            $table->index(['lokasi_id', 'status_keberadaan']);
        });
    }

    public function down(): void
    {
        Schema::table('pengungsis', function (Blueprint $table) {
            $table->dropIndex(['lokasi_id', 'status_keberadaan']);
            $table->dropColumn(['status_keberadaan', 'keluar_pada', 'keterangan_keluar']);
        });
    }
};
