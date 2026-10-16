<?php

namespace App\Exports\Concerns;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Gaya tampilan export GMLS (sama dengan LokasiExport):
 * judul biru di baris 1, tanggal cetak di baris 2, header di baris 3, baris belang.
 * Pakai bersama WithCustomStartCell -> startCell() = 'A3'.
 */
trait GayaExportGmls
{
    public function startCell(): string
    {
        return 'A3';
    }

    protected function terapkanGaya(Worksheet $sheet, string $judul, array $lebarKolom): void
    {
        $akhir   = Coordinate::stringFromColumnIndex(count($lebarKolom));
        $tanggal = Carbon::now('Asia/Jakarta')->translatedFormat('d F Y H:i');

        $sheet->mergeCells("A1:{$akhir}1");
        $sheet->setCellValue('A1', $judul);
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2F75B5']],
        ]);

        $sheet->mergeCells("A2:{$akhir}2");
        $sheet->setCellValue('A2', 'Dicetak pada: ' . $tanggal . ' (WIB)');
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['italic' => true, 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);

        $sheet->getStyle("A3:{$akhir}3")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);

        $baris = $sheet->getHighestRow();
        if ($baris > 3) {
            $sheet->getStyle("A4:{$akhir}{$baris}")->applyFromArray([
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D9D9D9']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ]);
            for ($i = 4; $i <= $baris; $i++) {
                $sheet->getStyle("A{$i}:{$akhir}{$i}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB($i % 2 == 0 ? 'E9E9E9' : 'FFFFFF');
            }
        }

        foreach ($lebarKolom as $i => $lebar) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i + 1))->setWidth($lebar);
        }

        $sheet->freezePane('A4');
    }
}
