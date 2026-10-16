<?php

namespace App\Http\Controllers\Admin;

use App\Exports\DesaExport;
use App\Exports\Template\ImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\Support\SpreadsheetReader;
use App\Models\Desa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use RealRashid\SweetAlert\Facades\Alert as Swal;

/**
 * Import & Export Data Desa (khusus admin).
 * Alur import: upload -> pratinjau -> konfirmasi -> simpan.
 * Data dicocokkan berdasarkan NAMA DESA: jika sudah ada -> diperbarui, jika belum -> ditambahkan.
 */
class DesaImportController extends Controller
{
    private const SESSION = 'import.desa';

    /** Kolom yang dibaca dari file beserta nama-nama alternatifnya */
    private const KOLOM = [
        'nama_desa'   => ['nama desa', 'desa', 'nama'],
        'alamat_desa' => ['alamat desa', 'alamat', 'alamat lengkap'],
    ];

    public function template()
    {
        return Excel::download(new ImportTemplateExport(
            'Data Desa',
            [
                ['Nama Desa',   'wajib', 'Nama desa, contoh: Panggarangan. Dipakai untuk mencocokkan data yang sudah ada.', 30],
                ['Alamat Desa', 'wajib', 'Alamat lengkap pusat desa.', 70],
            ],
            [
                ['Panggarangan', 'Jl. Raya Panggarangan, Kec. Panggarangan, Kab. Lebak, Banten'],
                ['Hegarmanah',   'Kp. Hegarmanah, Kec. Panggarangan, Kab. Lebak, Banten'],
            ],
            ['Penulisan "Desa Panggarangan" dan "Panggarangan" dianggap desa yang sama.']
        ), 'template_import_desa.xlsx');
    }

    public function export()
    {
        return Excel::download(new DesaExport, 'data_desa.xlsx');
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:5120',
        ], [
            'file.required' => 'Pilih file yang akan di-import.',
            'file.mimes'    => 'Format file harus .xlsx, .xls, atau .csv.',
            'file.max'      => 'Ukuran file maksimal 5 MB.',
        ]);

        $hasil = SpreadsheetReader::read($request->file('file'), self::KOLOM);

        if ($hasil['missing']) {
            Swal::toast('Kolom "Nama Desa" dan "Alamat Desa" tidak ditemukan. Gunakan template yang disediakan.', 'error')->timerProgressBar();
            return redirect()->route('admin.desa.index');
        }

        if (! $hasil['rows']) {
            Swal::toast('File tidak berisi data.', 'error')->timerProgressBar();
            return redirect()->route('admin.desa.index');
        }

        $existing = Desa::all()->keyBy(fn ($d) => $this->kunci($d->nama_desa));
        $dipakai  = [];   // kunci => nomor baris pertama
        $baris    = [];
        $valid    = [];

        foreach ($hasil['rows'] as $r) {
            $nama   = trim((string) ($r['nama_desa'] ?? ''));
            $alamat = trim((string) ($r['alamat_desa'] ?? ''));
            $kunci  = $this->kunci($nama);
            $error  = [];

            if ($nama === '') {
                $error[] = 'Nama desa kosong';
            } elseif (mb_strlen($nama) > 255) {
                $error[] = 'Nama desa terlalu panjang (maks. 255 karakter)';
            }
            if ($alamat === '') {
                $error[] = 'Alamat desa kosong';
            }
            if ($nama !== '' && isset($dipakai[$kunci])) {
                $error[] = "Nama desa sama dengan baris {$dipakai[$kunci]}";
            } elseif ($nama !== '') {
                $dipakai[$kunci] = $r['_row'];
            }

            $lama = $existing->get($kunci);

            if ($error) {
                $status = 'error';
            } elseif (! $lama) {
                $status = 'tambah';
            } elseif ($lama->alamat_desa !== $alamat) {
                $status = 'perbarui';
            } else {
                $status = 'sama';
            }

            $baris[] = [
                'row'         => $r['_row'],
                'nama_desa'   => $nama,
                'alamat_desa' => $alamat,
                'alamat_lama' => $lama?->alamat_desa,
                'status'      => $status,
                'error'       => $error,
            ];

            if (in_array($status, ['tambah', 'perbarui'], true)) {
                $valid[] = ['nama_desa' => $nama, 'alamat_desa' => $alamat];
            }
        }

        session([self::SESSION => $valid]);

        $ringkasan = collect($baris)->countBy('status');

        return view('pages.admin.desa.import-preview', [
            'baris'     => $baris,
            'ringkasan' => $ringkasan,
            'namaFile'  => $request->file('file')->getClientOriginalName(),
            'jumlahValid' => count($valid),
        ]);
    }

    public function confirm()
    {
        $data = session(self::SESSION);

        if (! $data) {
            Swal::toast('Tidak ada data untuk di-import. Silakan upload ulang file.', 'error')->timerProgressBar();
            return redirect()->route('admin.desa.index');
        }

        $tambah = 0;
        $perbarui = 0;

        DB::transaction(function () use ($data, &$tambah, &$perbarui) {
            $existing = Desa::all()->keyBy(fn ($d) => $this->kunci($d->nama_desa));

            foreach ($data as $row) {
                $lama = $existing->get($this->kunci($row['nama_desa']));

                if ($lama) {
                    if ($lama->alamat_desa !== $row['alamat_desa']) {
                        $lama->update(['alamat_desa' => $row['alamat_desa']]);
                        $perbarui++;
                    }
                } else {
                    $baru = Desa::create($row);
                    $existing->put($this->kunci($baru->nama_desa), $baru);
                    $tambah++;
                }
            }
        });

        session()->forget(self::SESSION);

        Swal::toast("Import selesai: {$tambah} desa ditambahkan, {$perbarui} diperbarui", 'success')->timerProgressBar();

        return redirect()->route('admin.desa.index');
    }

    /** "Desa  Panggarangan " / "panggarangan" -> "panggarangan" */
    private function kunci(?string $nama): string
    {
        $nama = mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $nama)));
        return preg_replace('/^desa\s+/u', '', $nama);
    }
}
