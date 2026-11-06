<?php

namespace App\Exports;

use App\Models\Lokasi;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Carbon\Carbon;

class LokasiExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle, WithCustomStartCell
{
    protected $timezone;

    public function __construct()
    {
        $this->timezone = 'Asia/Jakarta';
    }

    public function collection()
    {
        return Lokasi::with([
                'desa:id,nama_desa,alamat_desa',
                'latestStatus:id,lokasi_id,status,catatan',
                'sphereLokasi:id,lokasi_id,air_hidup,air_kebersihan,air_memasak,toilet_pendek,toilet_panjang,kalori,protein,lemak'
            ])
            ->get();
    }

    public function title(): string
    {
        return 'Data Lokasi Pengungsian';
    }

    public function startCell(): string
    {
        return 'A3';
    }

    public function headings(): array
    {
        return [
            'No', // New numbering column
            'ID Lokasi',
            'Nama Lokasi',
            'Alamat Lokasi',
            'Luas Lokasi (m²)',
            'Kapasitas Pengungsi',
            'Latitude',
            'Longitude',
            'Nama Desa',
            'Alamat Desa',
            'Status',
            'Catatan Status',
            'Air Hidup',
            'Air Kebersihan',
            'Air Memasak',
            'Toilet Pendek',
            'Toilet Panjang',
            'Kalori',
            'Protein',
            'Lemak',
            'Ketinggian (m dpl)'
        ];
    }

    public function map($lokasi): array
    {
        static $number = 1;
        
        return [
            $number++, // Auto-incrementing number
            $lokasi->id,
            $lokasi->nama_lokasi,
            $lokasi->alamat_lokasi,
            $lokasi->luas_lokasi,
            $lokasi->kapasitas_pengungsi,
            $lokasi->latitude,
            $lokasi->longitude,
            optional($lokasi->desa)->nama_desa ?? '-',
            optional($lokasi->desa)->alamat_desa ?? '-',
            optional($lokasi->latestStatus)->status ?? '-',
            optional($lokasi->latestStatus)->catatan ?? '-',
            optional($lokasi->sphereLokasi)->air_hidup ?? '-',
            optional($lokasi->sphereLokasi)->air_kebersihan ?? '-',
            optional($lokasi->sphereLokasi)->air_memasak ?? '-',
            optional($lokasi->sphereLokasi)->toilet_pendek ?? '-',
            optional($lokasi->sphereLokasi)->toilet_panjang ?? '-',
            optional($lokasi->sphereLokasi)->kalori ?? '-',
            optional($lokasi->sphereLokasi)->protein ?? '-',
            optional($lokasi->sphereLokasi)->lemak ?? '-',
            $lokasi->ketinggian_mdpl ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $printDate = Carbon::now($this->timezone)->translatedFormat('d F Y H:i');
        
        // Set title
        $sheet->mergeCells('A1:U1');
        $sheet->setCellValue('A1', 'DATA LOKASI PENGUNGSIAN KECAMATAN PANGGARANGAN OLEH GMLS');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2F75B5']
            ]
        ]);

        // Set subtitle with export date
        $sheet->mergeCells('A2:U2');
        $sheet->setCellValue('A2', 'Dicetak pada: ' . $printDate . ' (WIB)');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => [
                'italic' => true,
                'size' => 10,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
            ]
        ]);

        // Header style
        $sheet->getStyle('A3:U3')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ],
            'alignment' => [
                'wrapText' => true,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);

        // Data rows style
        $lastRow = $sheet->getHighestRow();
        $dataRange = 'A4:U' . $lastRow;
        
        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D9D9D9']
                ]
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);

        // Alternating row colors
        for ($i = 4; $i <= $lastRow; $i++) {
            $fillColor = $i % 2 == 0 ? 'E9E9E9' : 'FFFFFF';
            $sheet->getStyle('A' . $i . ':U' . $i)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB($fillColor);
        }

        // Number formatting for numeric columns
        $sheet->getStyle('E4:E' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('F4:F' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(8);  // No column
        $sheet->getColumnDimension('U')->setWidth(18); // Ketinggian (m dpl)
        $sheet->getColumnDimension('B')->setWidth(10); // ID
        $sheet->getColumnDimension('C')->setWidth(25); // Nama Lokasi
        $sheet->getColumnDimension('D')->setWidth(30); // Alamat
        $sheet->getColumnDimension('E')->setWidth(15); // Luas
        $sheet->getColumnDimension('F')->setWidth(20); // Kapasitas
        $sheet->getColumnDimension('G')->setWidth(15); // Latitude
        $sheet->getColumnDimension('H')->setWidth(15); // Longitude
        $sheet->getColumnDimension('I')->setWidth(20); // Nama Desa
        $sheet->getColumnDimension('J')->setWidth(30); // Alamat Desa
        $sheet->getColumnDimension('K')->setWidth(15); // Status
        $sheet->getColumnDimension('L')->setWidth(25); // Catatan

        // Freeze header row
        $sheet->freezePane('A4');

        return [];
    }
}