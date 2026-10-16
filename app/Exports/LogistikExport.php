<?php

namespace App\Exports;

use App\Exports\Concerns\GayaExportGmls;
use App\Models\KategoriLogistik;
use App\Models\Logistik;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Export Master Logistik. File hasil export bisa langsung di-import kembali. */
class LogistikExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithCustomStartCell
{
    use GayaExportGmls;

    private int $nomor = 1;
    private $kategori;

    public function collection()
    {
        $this->kategori = KategoriLogistik::pluck('nama_kategori', 'id');

        return Logistik::orderBy('kategori_logistik_id')->orderBy('nama_item')->get();
    }

    public function title(): string
    {
        return 'Master Logistik';
    }

    public function headings(): array
    {
        return ['No', 'Nama Item', 'Kategori', 'Satuan', 'Kebutuhan Harian', 'Deskripsi'];
    }

    public function map($logistik): array
    {
        return [
            $this->nomor++,
            $logistik->nama_item,
            $this->kategori[$logistik->kategori_logistik_id] ?? '-',
            $logistik->satuan,
            $logistik->kebutuhan_harian,
            $logistik->deskripsi,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $this->terapkanGaya($sheet, 'MASTER LOGISTIK - GMLS LOGEVA', [6, 30, 18, 12, 18, 40]);
        return [];
    }
}
