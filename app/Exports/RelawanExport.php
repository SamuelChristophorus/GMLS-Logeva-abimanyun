<?php

namespace App\Exports;

use App\Exports\Concerns\GayaExportGmls;
use App\Models\Lokasi;
use App\Models\Relawan;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Export Data Relawan. File hasil export bisa langsung di-import kembali (memperbarui nama). */
class RelawanExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithCustomStartCell
{
    use GayaExportGmls;

    private int $nomor = 1;
    private $jumlahPosko;

    public function collection()
    {
        $this->jumlahPosko = Lokasi::selectRaw('relawan_id, COUNT(*) as jumlah')
            ->groupBy('relawan_id')
            ->pluck('jumlah', 'relawan_id');

        return Relawan::with('user')->get()
            ->filter(fn ($r) => $r->user)
            ->sortBy(fn ($r) => mb_strtolower($r->user->name))
            ->values();
    }

    public function title(): string
    {
        return 'Data Relawan';
    }

    public function headings(): array
    {
        return ['No', 'Nama Lengkap', 'Email', 'Status Akun', 'Jumlah Posko', 'Terdaftar Sejak'];
    }

    public function map($relawan): array
    {
        $status = [
            'approved'  => 'Disetujui',
            'pending'   => 'Menunggu',
            'rejected'  => 'Ditolak',
            'suspended' => 'Ditangguhkan',
        ];

        return [
            $this->nomor++,
            $relawan->user->name,
            $relawan->user->email,
            $status[$relawan->user->status ?? ''] ?? ($relawan->user->status ?? '-'),
            $this->jumlahPosko[$relawan->id] ?? 0,
            optional($relawan->created_at)->timezone('Asia/Jakarta')?->format('d-m-Y'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $this->terapkanGaya($sheet, 'DATA RELAWAN - GMLS LOGEVA', [6, 30, 34, 16, 14, 16]);
        return [];
    }
}
