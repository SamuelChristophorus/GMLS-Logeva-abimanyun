<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Zona waktu aplikasi diganti dari UTC ke WIB (config/app.php -> APP_TIMEZONE, default Asia/Jakarta).
 *
 * Laravel menyimpan jam sesuai zona waktu aplikasi, jadi semua jam LAMA (tersimpan sebagai UTC)
 * harus digeser sekali (+7 jam untuk WIB) agar tetap menunjukkan waktu yang benar.
 *
 * - Yang digeser : semua kolom TIMESTAMP / DATETIME (created_at, updated_at, mulai_beroperasi_at, ...).
 * - Tidak disentuh: kolom DATE (tanggal_lahir, tanggal_masuk, tanggal_keluar) karena diisi manual.
 * - Tidak melakukan apa-apa jika zona waktu aplikasi masih UTC.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->geser(+1);
    }

    public function down(): void
    {
        $this->geser(-1);
    }

    private function geser(int $arah): void
    {
        $menit = Carbon::now(config('app.timezone'))->utcOffset() * $arah;

        if ($menit === 0 || ! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        $kolom = collect(DB::select(
            "SELECT TABLE_NAME AS tabel, COLUMN_NAME AS kolom
               FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE()
                AND DATA_TYPE IN ('timestamp', 'datetime')
                AND TABLE_NAME <> 'migrations'"
        ))->groupBy('tabel');

        DB::transaction(function () use ($kolom, $menit) {
            foreach ($kolom as $tabel => $daftar) {
                // Semua kolom jam satu tabel digeser dalam satu UPDATE
                $set = $daftar
                    ->map(fn ($k) => "`{$k->kolom}` = `{$k->kolom}` + INTERVAL {$menit} MINUTE")
                    ->implode(', ');

                DB::statement("UPDATE `{$tabel}` SET {$set}");
            }
        });
    }
};
