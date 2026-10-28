<?php

use App\Models\KategoriLogistik;
use App\Services\GabungLogistikService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menyamakan Master Logistik website dengan aplikasi offline (28 Okt 2026):
 *  - tabel alias_logistiks: nama lama barang yang sudah digabung;
 *  - daftar baku (App\Support\MasterLogistikBaku) dipastikan ada, kebutuhan
 *    harian disamakan (Air Mineral 2 liter, pakaian tanpa kebutuhan harian);
 *  - "Pakaian Layak Pakai" digabung ke "Baju" (catatan & stok ikut pindah).
 * Barang lain yang sudah ada tidak dihapus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alias_logistiks', function (Blueprint $table) {
            $table->id();
            $table->string('nama_alias');
            $table->string('kunci')->unique(); // nama tanpa beda huruf/spasi
            $table->foreignId('logistik_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        // Database baru (belum ada kategori) diisi lewat seeder.
        if (KategoriLogistik::count() > 0) {
            app(GabungLogistikService::class)->standarkan();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('alias_logistiks');
    }
};
