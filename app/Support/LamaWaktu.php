<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Lama waktu sejak suatu tanggal dalam bahasa sehari-hari (dihitung per tanggal
 * kalender WIB), misal untuk "akun relawan dibuat 3 bulan lalu".
 * Sama dengan DateFormatter.lamaSejak() di aplikasi.
 */
class LamaWaktu
{
    public static function sejak(?CarbonInterface $waktu, ?CarbonInterface $sekarang = null): string
    {
        if (! $waktu) {
            return '-';
        }
        $dari = $waktu->copy()->timezone(config('app.timezone'))->startOfDay();
        $sampai = ($sekarang ?? now())->copy()->timezone(config('app.timezone'))->startOfDay();
        $hari = (int) $dari->diffInDays($sampai, false);

        if ($hari <= 0) return 'hari ini';
        if ($hari === 1) return 'kemarin';
        if ($hari < 30) return "{$hari} hari lalu";

        $bulan = (int) $dari->diffInMonths($sampai);
        if ($bulan < 12) return max(1, $bulan) . ' bulan lalu';

        $tahun = intdiv($bulan, 12);
        $sisa = $bulan % 12;
        return $sisa ? "{$tahun} tahun {$sisa} bulan lalu" : "{$tahun} tahun lalu";
    }
}
