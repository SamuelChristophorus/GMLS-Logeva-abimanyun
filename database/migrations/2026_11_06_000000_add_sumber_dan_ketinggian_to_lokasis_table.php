<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Posko dari sumber luar (aplikasi RUinRISK):
 *  - ketinggian_mdpl : ketinggian tempat (meter di atas permukaan laut), penting untuk evakuasi tsunami.
 *  - sumber_data + id_sumber : asal data & ID di aplikasi asal, supaya import ulang file yang sama
 *    MEMPERBARUI posko, bukan menambah posko dobel. (Kolom `kode` tetap khusus UUID aplikasi offline.)
 *
 * Hanya MENAMBAH kolom; aman dijalankan ulang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lokasis', function (Blueprint $table) {
            if (! Schema::hasColumn('lokasis', 'ketinggian_mdpl')) {
                $table->decimal('ketinggian_mdpl', 7, 2)->nullable()->after('longitude');
            }
            if (! Schema::hasColumn('lokasis', 'sumber_data')) {
                $table->string('sumber_data', 30)->nullable()->after('kode');
                $table->string('id_sumber', 64)->nullable()->after('sumber_data');
                $table->unique(['sumber_data', 'id_sumber']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('lokasis', function (Blueprint $table) {
            $table->dropUnique(['sumber_data', 'id_sumber']);
            $table->dropColumn(['ketinggian_mdpl', 'sumber_data', 'id_sumber']);
        });
    }
};
