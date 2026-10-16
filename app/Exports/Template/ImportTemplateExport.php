<?php

namespace App\Exports\Template;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Template import (dipakai ulang untuk Desa, Logistik, Relawan, Lokasi).
 * Sheet 1 = tempat mengisi data, Sheet 2 = petunjuk + contoh.
 */
class ImportTemplateExport implements WithMultipleSheets
{
    /**
     * @param string $judul      contoh: 'Data Desa'
     * @param array  $kolom      [['Nama Desa', 'wajib', 'Nama desa, contoh: Panggarangan'], ...]
     * @param array  $contoh     satu atau beberapa baris contoh (urutan sama dengan $kolom)
     * @param array  $catatan    catatan tambahan untuk sheet petunjuk
     */
    public function __construct(
        private string $judul,
        private array $kolom,
        private array $contoh = [],
        private array $catatan = []
    ) {}

    public function sheets(): array
    {
        return [
            new TemplateSheet($this->judul, $this->kolom),
            new PetunjukSheet($this->judul, $this->kolom, $this->contoh, $this->catatan),
        ];
    }
}
