<?php

namespace App\Exports;

use App\Models\Desa;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export Data Desa (gaya tampilan sama dengan LokasiExport).
 * File hasil export ini juga bisa langsung di-import kembali.
 */
class DesaExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithCustomStartCell
{
    private int $nomor = 1;

    public function collection()
    {
        return Desa::withCount('lokasi')->orderBy('nama_desa')->get();
    }

    public function title(): string
    {
        return 'Data Desa';
    }

    public function startCell(): string
    {
        return 'A3';
    }

    public function headings(): array
    {
        return ['No', 'Nama Desa', 'Alamat Desa', 'Jumlah Lokasi TEA'];
    }

    public function map($desa): array
    {
        return [
            $this->nomor++,
            $desa->nama_desa,
            $desa->alamat_desa,
            $desa->lokasi_count,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $tanggal = Carbon::now('Asia/Jakarta')->translatedFormat('d F Y H:i');

        $sheet->mergeCells('A1:D1');
        $sheet->setCellValue('A1', 'DATA DESA - GMLS LOGEVA');
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2F75B5']],
        ]);

        $sheet->mergeCells('A2:D2');
        $sheet->setCellValue('A2', 'Dicetak pada: ' . $tanggal . ' (WIB)');
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['italic' => true, 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);

        $sheet->getStyle('A3:D3')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);

        $akhir = max(3, $sheet->getHighestRow());
        if ($akhir > 3) {
            $sheet->getStyle("A4:D{$akhir}")->applyFromArray([
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D9D9D9']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ]);
            for ($i = 4; $i <= $akhir; $i++) {
                $sheet->getStyle("A{$i}:D{$i}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB($i % 2 == 0 ? 'E9E9E9' : 'FFFFFF');
            }
        }

        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(28);
        $sheet->getColumnDimension('C')->setWidth(60);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->freezePane('A4');

        return [];
    }
}
