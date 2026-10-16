<?php

namespace App\Imports\Support;

use App\Imports\RawSheetImport;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Membaca file XLSX / XLS / CSV menjadi daftar baris berbentuk array asosiatif.
 *
 * - Baris header dicari otomatis di 10 baris pertama, jadi file template
 *   (header di baris 1) maupun file hasil Export (header di baris 3) sama-sama bisa dipakai.
 * - Nama kolom tidak peka huruf besar/kecil, spasi, atau tanda baca
 *   ("Nama Desa", "nama_desa", "NAMA  DESA" dianggap sama).
 * - CSV dari Excel Indonesia yang memakai titik koma (;) dideteksi otomatis.
 * - Hanya sheet pertama yang dibaca.
 */
class SpreadsheetReader
{
    /**
     * @param  UploadedFile $file
     * @param  array $columns  ['kunci_kolom' => ['alias 1', 'alias 2', ...], ...]
     * @param  array $required kunci kolom yang wajib ada di header
     * @return array ['rows' => [...], 'missing' => [...]]
     *               setiap row: ['_row' => nomor baris Excel, 'kunci_kolom' => nilai, ...]
     */
    public static function read(UploadedFile $file, array $columns, array $required = []): array
    {
        $isCsv = self::aturDelimiterCsv($file);

        $sheets = Excel::toArray(
            new RawSheetImport,
            $file,
            null,
            $isCsv ? \Maatwebsite\Excel\Excel::CSV : null
        );
        $raw    = $sheets[0] ?? [];

        // --- cari baris header ---
        $aliasKeKunci = [];
        foreach ($columns as $kunci => $aliases) {
            foreach (array_merge([$kunci], $aliases) as $alias) {
                $aliasKeKunci[self::normalisasi($alias)] = $kunci;
            }
        }

        $headerIndex = null;
        $peta = []; // indeks kolom => kunci
        foreach (array_slice($raw, 0, 10, true) as $i => $baris) {
            $petaSementara = [];
            foreach ((array) $baris as $kol => $nilai) {
                $n = self::normalisasi((string) $nilai);
                if ($n !== '' && isset($aliasKeKunci[$n]) && ! in_array($aliasKeKunci[$n], $petaSementara, true)) {
                    $petaSementara[$kol] = $aliasKeKunci[$n];
                }
            }
            $wajibAda = $required ?: array_keys($columns);
            if (count(array_intersect($wajibAda, $petaSementara)) === count($wajibAda)) {
                $headerIndex = $i;
                $peta = $petaSementara;
                break;
            }
        }

        if ($headerIndex === null) {
            return ['rows' => [], 'missing' => $required ?: array_keys($columns)];
        }

        // --- ambil baris data ---
        $rows = [];
        foreach ($raw as $i => $baris) {
            if ($i <= $headerIndex) {
                continue;
            }

            $data = ['_row' => $i + 1];
            $kosong = true;
            foreach ($peta as $kol => $kunci) {
                $nilai = $baris[$kol] ?? null;
                $nilai = is_string($nilai) ? trim($nilai) : $nilai;
                if ($nilai !== null && $nilai !== '') {
                    $kosong = false;
                }
                $data[$kunci] = $nilai === '' ? null : $nilai;
            }

            if (! $kosong) {
                $rows[] = $data;
            }
        }

        return ['rows' => $rows, 'missing' => []];
    }

    /** "Nama Desa", "nama_desa", "NAMA  DESA*" -> "nama desa" */
    public static function normalisasi(string $teks): string
    {
        $teks = str_replace("\xEF\xBB\xBF", '', $teks);          // BOM UTF-8
        $teks = mb_strtolower($teks);
        $teks = preg_replace('/\(.*?\)/u', ' ', $teks);           // buang keterangan dalam kurung
        $teks = preg_replace('/[^a-z0-9]+/u', ' ', $teks);
        return trim(preg_replace('/\s+/', ' ', $teks));
    }

    /** Mengatur pemisah CSV (koma / titik koma). Mengembalikan true jika file CSV. */
    private static function aturDelimiterCsv(UploadedFile $file): bool
    {
        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, ['csv', 'txt'], true)) {
            return false;
        }

        $handle = @fopen($file->getRealPath(), 'r');
        $barisPertama = $handle ? (string) fgets($handle) : '';
        if ($handle) {
            fclose($handle);
        }

        $delimiter = substr_count($barisPertama, ';') > substr_count($barisPertama, ',') ? ';' : ',';
        config(['excel.imports.csv.delimiter' => $delimiter]);

        return true;
    }
}
