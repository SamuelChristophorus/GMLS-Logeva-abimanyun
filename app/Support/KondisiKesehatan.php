<?php

namespace App\Support;

/**
 * KONDISI KESEHATAN = PILIHAN + keterangan bebas, disimpan di satu kolom
 * (pengungsis.kondisi_kesehatan) supaya format database & Paket Data Posko
 * tidak berubah. Sama dengan aplikasi LOGEVA Client (utils/kondisi_kesehatan.dart):
 *   "Sehat", "Sakit ringan - Demam", "Sakit berat", "Perlu rujukan - Patah tulang".
 * Teks lama yang bukan pilihan (misal "Demam") tetap diterima.
 */
class KondisiKesehatan
{
    public const SEHAT = 'Sehat';
    public const PILIHAN = ['Sehat', 'Sakit ringan', 'Sakit berat', 'Perlu rujukan'];
    public const PEMISAH = ' - ';

    public static function gabung(?string $pilihan, ?string $keterangan): string
    {
        $pilihan = in_array($pilihan, self::PILIHAN, true) ? $pilihan : self::SEHAT;
        $ket = trim(preg_replace('/\s+/u', ' ', (string) $keterangan));
        // Sehat tidak memakai keterangan.
        return $pilihan === self::SEHAT || $ket === '' ? $pilihan : $pilihan . self::PEMISAH . $ket;
    }

    /** @return array{pilihan: ?string, keterangan: string} pilihan null = teks lama */
    public static function pisah(?string $teks): array
    {
        $t = trim((string) $teks);
        $kecil = mb_strtolower($t);
        if ($t === '' || in_array($kecil, ['baik', '-'], true)) {
            return ['pilihan' => self::SEHAT, 'keterangan' => ''];
        }
        foreach (self::PILIHAN as $p) {
            $pk = mb_strtolower($p);
            if ($kecil === $pk) {
                return ['pilihan' => $p, 'keterangan' => ''];
            }
            if (str_starts_with($kecil, $pk) && preg_match('/^\s*[-:,(]/u', mb_substr($t, mb_strlen($p)))) {
                $sisa = preg_replace(['/^\s*[-:,(]\s*/u', '/\)\s*$/u'], '', mb_substr($t, mb_strlen($p)));
                return ['pilihan' => $p, 'keterangan' => trim($sisa)];
            }
        }
        return ['pilihan' => null, 'keterangan' => $t];
    }

    /** Seragamkan penulisan pilihan; teks lama yang bukan pilihan dibiarkan. */
    public static function rapikan(?string $teks): ?string
    {
        if ($teks === null || trim($teks) === '') {
            return $teks;
        }
        $p = self::pisah($teks);
        return $p['pilihan'] === null ? $p['keterangan'] : self::gabung($p['pilihan'], $p['keterangan']);
    }
}
