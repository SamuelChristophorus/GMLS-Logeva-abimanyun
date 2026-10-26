<?php

namespace App\Http\Controllers\Admin\Import;

use App\Exports\LogistikExport;
use App\Exports\Template\ImportTemplateExport;
use App\Imports\Support\NilaiImport as N;
use App\Models\KategoriLogistik;
use App\Models\Logistik;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Import & Export Master Logistik.
 * Dicocokkan berdasarkan NAMA ITEM + KATEGORI.
 */
class LogistikImportController extends BaseImportController
{
    protected function nama(): string    { return 'logistik'; }
    protected function judul(): string   { return 'Master Logistik'; }
    protected function satuan(): string  { return 'item'; }

    protected function kolomFile(): array
    {
        return [
            'nama_item'        => ['nama item', 'item', 'nama barang', 'nama logistik'],
            'kategori'         => ['kategori', 'kategori logistik', 'nama kategori'],
            'satuan'           => ['satuan', 'unit'],
            'kebutuhan_harian' => ['kebutuhan harian', 'kebutuhan per hari', 'kebutuhan'],
            'deskripsi'        => ['deskripsi', 'keterangan'],
        ];
    }

    protected function kolomWajib(): array
    {
        return ['nama_item', 'kategori', 'satuan'];
    }

    protected function kolomTampil(): array
    {
        return [
            'nama_item'        => 'Nama Item',
            'kategori'         => 'Kategori',
            'satuan'           => 'Satuan',
            'kebutuhan_harian' => 'Kebutuhan Harian',
            'deskripsi'        => 'Deskripsi',
        ];
    }

    public function template()
    {
        $kategori = KategoriLogistik::orderBy('nama_kategori')->pluck('nama_kategori')->implode(', ');

        return Excel::download(new ImportTemplateExport(
            'Master Logistik',
            [
                ['Nama Item',        'wajib', 'Nama barang, contoh: Beras.', 28],
                ['Kategori',         'wajib', 'Harus sama dengan kategori yang ada di sistem: ' . ($kategori ?: '-'), 20],
                ['Satuan',           'wajib', 'Contoh: kg, liter, dus, lembar.', 14],
                ['Kebutuhan Harian', 'opsional', 'Kebutuhan rata-rata per hari (angka). Boleh pakai koma desimal, contoh: 0,4', 20],
                ['Deskripsi',        'opsional', 'Keterangan tambahan.', 40],
            ],
            [
                ['Beras', 'Makanan', 'kg', '0,4', 'Beras medium'],
                ['Air Mineral 600 ml', 'Minuman', 'dus', '2', ''],
            ],
            [
                'Item dianggap sama jika Nama Item dan Kategori-nya sama. Data item tersebut akan diperbarui.',
                'Kolom opsional yang dikosongkan tidak akan menghapus data lama.',
            ]
        ), 'template_import_logistik.xlsx');
    }

    public function export()
    {
        return Excel::download(new LogistikExport, 'master_logistik.xlsx');
    }

    /**
     * Export CSV untuk aplikasi offline (Master Barang -> Import dari website),
     * tanpa perlu membuka Excel dulu. Baris pertama = judul kolom, pemisah koma,
     * angka desimal memakai koma ("2,5") seperti yang dibaca aplikasi & import website.
     */
    public function exportCsv()
    {
        $kategori = KategoriLogistik::pluck('nama_kategori', 'id');
        $barang = Logistik::orderBy('kategori_logistik_id')->orderBy('nama_item')->get();

        return response()->streamDownload(function () use ($kategori, $barang) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8
            fputcsv($out, ['Nama Item', 'Kategori', 'Satuan', 'Kebutuhan Harian', 'Deskripsi'], ',', '"', '');
            foreach ($barang as $b) {
                fputcsv($out, [
                    $b->nama_item,
                    $kategori[$b->kategori_logistik_id] ?? '',
                    $b->satuan,
                    $this->angkaCsv($b->kebutuhan_harian),
                    (string) $b->deskripsi,
                ], ',', '"', '');
            }
            fclose($out);
        }, 'master_logistik_' . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** 12 -> "12", 2.5 -> "2,5", kosong -> "". */
    private function angkaCsv($nilai): string
    {
        if ($nilai === null || $nilai === '') return '';
        $n = round((float) $nilai, 2);
        return $n == floor($n) ? (string) (int) $n : str_replace('.', ',', (string) $n);
    }

    protected function periksa(array $rows): array
    {
        $kategoris = KategoriLogistik::all()->keyBy(fn ($k) => N::kunci($k->nama_kategori));
        $existing  = Logistik::all()->keyBy(fn ($l) => $l->kategori_logistik_id . '|' . N::kunci($l->nama_item));
        $dipakai   = [];
        $hasil     = [];

        foreach ($rows as $r) {
            $nama      = N::teks($r['nama_item'] ?? null);
            $katNama   = N::teks($r['kategori'] ?? null);
            $satuan    = N::teks($r['satuan'] ?? null);
            $deskripsi = N::teks($r['deskripsi'] ?? null);
            $kebutuhan = N::angka($r['kebutuhan_harian'] ?? null);
            $kategori  = $katNama ? $kategoris->get(N::kunci($katNama)) : null;
            $error     = [];

            if (! $nama) {
                $error[] = 'Nama item kosong';
            } elseif (mb_strlen($nama) > 255) {
                $error[] = 'Nama item terlalu panjang';
            }
            if (! $katNama) {
                $error[] = 'Kategori kosong';
            } elseif (! $kategori) {
                $error[] = "Kategori \"{$katNama}\" tidak ada di sistem";
            }
            if (! $satuan) {
                $error[] = 'Satuan kosong';
            } elseif (mb_strlen($satuan) > 255) {
                $error[] = 'Satuan terlalu panjang';
            }
            if ($kebutuhan === false) {
                $error[] = 'Kebutuhan harian harus berupa angka';
            } elseif ($kebutuhan !== null && $kebutuhan < 0) {
                $error[] = 'Kebutuhan harian tidak boleh negatif';
            }

            $kunci = $kategori && $nama ? $kategori->id . '|' . N::kunci($nama) : null;
            if ($kunci && isset($dipakai[$kunci])) {
                $error[] = "Item sama dengan baris {$dipakai[$kunci]}";
            } elseif ($kunci) {
                $dipakai[$kunci] = $r['_row'];
            }

            $baru = [
                'satuan'           => $satuan,
                'kebutuhan_harian' => is_float($kebutuhan) || is_int($kebutuhan) ? round($kebutuhan, 2) : null,
                'deskripsi'        => $deskripsi,
            ];

            $lama   = $kunci ? $existing->get($kunci) : null;
            $ubah   = $lama ? $this->bandingkan($lama->only(array_keys($baru)), $baru) : [];
            $status = $error ? 'error' : (! $lama ? 'tambah' : ($ubah ? 'perbarui' : 'sama'));

            $hasil[] = [
                'row'    => $r['_row'],
                'status' => $status,
                'error'  => $error,
                'data'   => [
                    'nama_item'        => $nama,
                    'kategori'         => $kategori->nama_kategori ?? $katNama,
                    'satuan'           => $satuan,
                    'kebutuhan_harian' => $baru['kebutuhan_harian'],
                    'deskripsi'        => $deskripsi,
                ],
                'lama'   => $ubah,
                'simpan' => $error ? null : [
                    'id'                   => $lama?->id,
                    'kategori_logistik_id' => $kategori?->id,
                    'nama_item'            => $nama,
                ] + $baru,
            ];
        }

        return $hasil;
    }

    protected function simpan(array $data): array
    {
        $tambah = $perbarui = 0;

        foreach ($data as $row) {
            $id = $row['id'];
            unset($row['id']);

            if ($id && ($logistik = Logistik::find($id))) {
                $logistik->forceFill(array_filter($row, fn ($v) => $v !== null))->save();
                $perbarui++;
            } else {
                (new Logistik)->forceFill($row)->save();
                $tambah++;
            }
        }

        return compact('tambah', 'perbarui');
    }
}
