<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

/**
 * Pembaca mentah: hanya mengambil isi sheet sebagai array baris (mulai baris 1).
 * Pengolahan header & validasi dilakukan oleh App\Imports\Support\SpreadsheetReader.
 */
class RawSheetImport implements ToArray
{
    public function array(array $array)
    {
        // tidak dipakai; data diambil lewat Excel::toArray()
    }
}
