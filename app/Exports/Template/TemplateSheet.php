<?php

namespace App\Exports\Template;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TemplateSheet implements FromArray, WithStyles, WithTitle
{
    public function __construct(private string $judul, private array $kolom) {}

    public function title(): string
    {
        return 'Isi Data';
    }

    public function array(): array
    {
        // Baris 1 = header. Kolom wajib diberi tanda *.
        return [array_map(
            fn ($k) => $k[0] . (($k[1] ?? '') === 'wajib' ? ' *' : ''),
            $this->kolom
        )];
    }

    public function styles(Worksheet $sheet)
    {
        $akhir = Coordinate::stringFromColumnIndex(count($this->kolom));

        $sheet->getStyle("A1:{$akhir}1")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        foreach ($this->kolom as $i => $k) {
            $huruf = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->getColumnDimension($huruf)->setWidth($k[3] ?? 25);
            // Semua sel diperlakukan sebagai teks agar koordinat/nomor tidak berubah format
            $sheet->getStyle("{$huruf}2:{$huruf}1000")->getNumberFormat()->setFormatCode('@');
        }

        $sheet->freezePane('A2');

        return [];
    }
}
