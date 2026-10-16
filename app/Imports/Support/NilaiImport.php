<?php

namespace App\Imports\Support;

/**
 * Fungsi bantu untuk membersihkan nilai dari file Excel.
 */
class NilaiImport
{
    /** Teks dirapikan: spasi ganda dihapus, null jika kosong. */
    public static function teks($nilai): ?string
    {
        if ($nilai === null) {
            return null;
        }
        $nilai = trim(preg_replace('/\s+/u', ' ', (string) $nilai));
        return $nilai === '' ? null : $nilai;
    }

    /** Kunci pembanding nama: huruf kecil, tanpa spasi ganda. */
    public static function kunci($nilai): string
    {
        return mb_strtolower((string) self::teks($nilai));
    }

    /** Kunci nama desa: seperti kunci(), plus kata "desa" di awal dibuang. */
    public static function kunciDesa($nilai): string
    {
        return preg_replace('/^desa\s+/u', '', self::kunci($nilai));
    }

    /**
     * Angka biasa (luas, kapasitas, kebutuhan). Mendukung format Indonesia:
     * "1.250" -> 1250, "1.250,5" -> 1250.5, "2,5" -> 2.5
     * Mengembalikan null jika kosong, false jika bukan angka.
     */
    public static function angka($nilai)
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }
        if (is_int($nilai) || is_float($nilai)) {
            return $nilai;
        }

        $s = str_replace(' ', '', trim((string) $nilai));

        if (str_contains($s, '.') && str_contains($s, ',')) {
            $s = str_replace('.', '', $s);          // titik = pemisah ribuan
            $s = str_replace(',', '.', $s);         // koma = desimal
        } elseif (preg_match('/^-?\d{1,3}(\.\d{3})+$/', $s)) {
            $s = str_replace('.', '', $s);          // "1.250" -> 1250
        } else {
            $s = str_replace(',', '.', $s);         // "2,5" -> 2.5
        }

        return is_numeric($s) ? (float) $s : false;
    }

    /**
     * Koordinat (latitude/longitude): koma dianggap desimal.
     * "-6,9195" -> -6.9195 ; "106.123" -> 106.123
     */
    public static function koordinat($nilai)
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }
        if (is_int($nilai) || is_float($nilai)) {
            return (float) $nilai;
        }
        $s = str_replace([' ', ','], ['', '.'], trim((string) $nilai));
        return is_numeric($s) ? (float) $s : false;
    }
}
