<?php

namespace App\Exports\Template;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PetunjukSheet implements FromArray, WithStyles, WithTitle
{
    private int $barisContoh = 0;

    public function __construct(
        private string $judul,
        private array $kolom,
        private array $contoh,
        private array $catatan
    ) {}

    public function title(): string
    {
        return 'Petunjuk';
    }

    public function array(): array
    {
        $baris = [
            ['PETUNJUK IMPORT ' . strtoupper($this->judul)],
            [''],
            ['1. Isi data di sheet "Isi Data", mulai baris ke-2. Jangan mengubah atau menghapus baris judul kolom.'],
            ['2. Kolom bertanda * wajib diisi.'],
            ['3. Jika data dengan nama yang sama sudah ada di sistem, data tersebut akan DIPERBARUI (tidak dibuat ganda).'],
            ['4. Setelah upload, sistem menampilkan pratinjau terlebih dahulu. Data baru disimpan setelah Anda menekan "Konfirmasi Import".'],
            ['5. Format yang diterima: .xlsx, .xls, atau .csv (maks. 5 MB).'],
        ];
        foreach ($this->catatan as $i => $c) {
            $baris[] = [($i + 6) . '. ' . $c];
        }

        $baris[] = [''];
        $baris[] = ['KETERANGAN KOLOM'];
        $baris[] = ['Kolom', 'Wajib?', 'Keterangan'];
        foreach ($this->kolom as $k) {
            $baris[] = [$k[0], ($k[1] ?? '') === 'wajib' ? 'Ya' : 'Tidak', $k[2] ?? ''];
        }

        if ($this->contoh) {
            $baris[] = [''];
            $baris[] = ['CONTOH PENGISIAN (jangan disalin ke sheet "Isi Data" jika bukan data asli)'];
            $this->barisContoh = count($baris) + 1;
            $baris[] = array_map(fn ($k) => $k[0], $this->kolom);
            foreach ($this->contoh as $c) {
                $baris[] = $c;
            }
        }

        return $baris;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(14);
        $sheet->getColumnDimension('C')->setWidth(60);

        // Judul bagian & header tabel keterangan
        foreach ($sheet->getRowIterator() as $row) {
            $r = $row->getRowIndex();
            $nilai = (string) $sheet->getCell("A{$r}")->getValue();
            if (in_array($nilai, ['KETERANGAN KOLOM'], true) || str_starts_with($nilai, 'CONTOH PENGISIAN')) {
                $sheet->getStyle("A{$r}")->getFont()->setBold(true);
            }
            if ($nilai === 'Kolom' && $sheet->getCell("B{$r}")->getValue() === 'Wajib?') {
                $sheet->getStyle("A{$r}:C{$r}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
                ]);
            }
        }

        if ($this->barisContoh) {
            $akhir = Coordinate::stringFromColumnIndex(max(3, count($this->kolom)));
            $sheet->getStyle("A{$this->barisContoh}:{$akhir}{$this->barisContoh}")->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2EFDA']],
            ]);
        }

        return [];
    }
}
