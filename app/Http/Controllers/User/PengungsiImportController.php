<?php

namespace App\Http\Controllers\User;

use App\Exports\Template\ImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\Support\NilaiImport as N;
use App\Imports\Support\SpreadsheetReader;
use App\Models\Lokasi;
use App\Models\Pengungsi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RealRashid\SweetAlert\Facades\Alert as Swal;

/**
 * Import data pengungsi per posko, alurnya sama dengan import di panel admin:
 *   upload -> baca file -> periksa tiap baris -> pratinjau -> konfirmasi -> simpan.
 *
 * Pengungsi dianggap sama jika NAMA + TANGGAL LAHIR-nya sama di posko yang sama;
 * data pengungsi tersebut diperbarui, bukan dibuat ganda.
 */
class PengungsiImportController extends Controller
{
    private const JENIS_KELAMIN = [
        'Laki-laki' => ['laki-laki', 'laki laki', 'lakilaki', 'laki', 'l', 'lk', 'pria', 'male'],
        'Perempuan' => ['perempuan', 'p', 'pr', 'wanita', 'female'],
    ];

    private const KELOMPOK_RENTAN = ['Tidak', 'Ibu Hamil', 'Lansia', 'Bayi', 'Disabilitas', 'Sakit'];

    private const KOLOM_FILE = [
        'nama'              => ['nama', 'nama pengungsi', 'nama lengkap'],
        'asal'              => ['asal', 'alamat asal', 'asal desa'],
        'tanggal_lahir'     => ['tanggal lahir', 'tgl lahir', 'tanggal_lahir'],
        'nomor_kk'          => ['nomor kk', 'no kk', 'nomor kartu keluarga', 'kk'],
        'usia'              => ['usia', 'umur'],
        'jenis_kelamin'     => ['jenis kelamin', 'jk', 'kelamin'],
        'kondisi_kesehatan' => ['kondisi kesehatan', 'kondisi'],
        'kelompok_rentan'   => ['kelompok rentan', 'rentan'],
        'riwayat_penyakit'  => ['riwayat penyakit', 'penyakit'],
    ];

    private const KOLOM_TAMPIL = [
        'nama'              => 'Nama',
        'asal'              => 'Asal',
        'tanggal_lahir'     => 'Tanggal Lahir',
        'nomor_kk'          => 'Nomor KK',
        'usia'              => 'Usia',
        'jenis_kelamin'     => 'Jenis Kelamin',
        'kondisi_kesehatan' => 'Kondisi Kesehatan',
        'kelompok_rentan'   => 'Kelompok Rentan',
        'riwayat_penyakit'  => 'Riwayat Penyakit',
    ];

    public function template(Lokasi $lokasi)
    {
        return Excel::download(new ImportTemplateExport(
            'Data Pengungsi',
            [
                ['Nama',              'wajib', 'Nama lengkap pengungsi.', 28],
                ['Asal',              'wajib', 'Asal desa / alamat sebelum mengungsi.', 24],
                ['Tanggal Lahir',     'wajib', 'Format TAHUN-BULAN-TANGGAL, contoh: 1990-07-25. Format 25-07-1990 atau 25/07/1990 juga diterima.', 16],
                ['Nomor KK',          'opsional', 'Boleh dikosongkan jika tidak diketahui. Jika diisi: 16 digit angka. Kolom ini sudah diatur sebagai teks agar digit terakhir tidak hilang.', 22],
                ['Usia',              'opsional', 'Boleh dikosongkan: dihitung otomatis dari tanggal lahir.', 8],
                ['Jenis Kelamin',     'wajib', 'Laki-laki atau Perempuan (L / P juga diterima).', 16],
                ['Kondisi Kesehatan', 'opsional', 'Contoh: Sehat, Demam, Luka ringan. Kosong = Sehat.', 20],
                ['Kelompok Rentan',   'opsional', 'Salah satu: ' . implode(', ', self::KELOMPOK_RENTAN) . '. Kosong = Tidak.', 18],
                ['Riwayat Penyakit',  'opsional', 'Contoh: Hipertensi, Diabetes.', 24],
            ],
            [
                ['Siti Aminah',  'Desa Cikaret',  '1958-03-12', '3602011203580001', '68', 'Perempuan', 'Sehat',        'Lansia',    'Hipertensi'],
                ['Budi Santoso', 'Desa Cikaret',  '1990-07-25', '3602012507900002', '36', 'Laki-laki', 'Sehat',        'Tidak',     ''],
                ['Ahmad Fauzi',  'Desa Sukamaju', '2024-01-15', '3602011501240004', '',   'L',         'Demam ringan', 'Bayi',      ''],
            ],
            [
                'Pengungsi dianggap sama jika Nama dan Tanggal Lahir-nya sama di posko ini. Data pengungsi tersebut akan diperbarui.',
                'File hasil tombol Export Excel di halaman pengungsi juga bisa di-import kembali.',
                'Foto pengungsi tidak bisa di-import; tambahkan lewat tombol Edit setelah data tersimpan.',
            ]
        ), 'template_import_pengungsi.xlsx');
    }

    public function preview(Request $request, Lokasi $lokasi)
    {
        $this->pastikanBolehKelola($request, $lokasi);

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:5120',
        ], [
            'file.required' => 'Pilih file yang akan di-import.',
            'file.mimes'    => 'Format file harus .xlsx, .xls, atau .csv.',
            'file.max'      => 'Ukuran file maksimal 5 MB.',
        ]);

        if ($validator->fails()) {
            return $this->kembali($lokasi, $validator->errors()->first(), 'error');
        }

        $hasil = SpreadsheetReader::read($request->file('file'), self::KOLOM_FILE, ['nama', 'tanggal_lahir', 'jenis_kelamin']);

        if ($hasil['missing']) {
            $label = collect($hasil['missing'])->map(fn ($k) => '"' . self::KOLOM_TAMPIL[$k] . '"')->implode(', ');
            return $this->kembali($lokasi, "Kolom {$label} tidak ditemukan. Gunakan template yang disediakan.", 'error');
        }

        if (! $hasil['rows']) {
            return $this->kembali($lokasi, 'File tidak berisi data.', 'error');
        }

        $baris = $this->periksa($lokasi, $hasil['rows']);
        $valid = collect($baris)->whereIn('status', ['tambah', 'perbarui'])->pluck('simpan')->values()->all();

        session([$this->kunciSession($lokasi) => $valid]);

        $peringatan = [];
        $tambah     = collect($baris)->where('status', 'tambah')->count();
        $setelah    = $lokasi->pengungsi()->count() + $tambah;
        if ($lokasi->kapasitas_pengungsi && $setelah > $lokasi->kapasitas_pengungsi) {
            $peringatan[] = "Setelah import, jumlah pengungsi menjadi {$setelah} orang dan melebihi kapasitas posko ({$lokasi->kapasitas_pengungsi} orang).";
        }

        return view('pages.admin.import.preview', [
            'layout'       => 'layouts.dashboard',
            'judul'        => 'Pengungsi · ' . $lokasi->nama_lokasi,
            'kolom'        => self::KOLOM_TAMPIL,
            'baris'        => $baris,
            'ringkasan'    => collect($baris)->countBy('status'),
            'jumlahValid'  => count($valid),
            'namaFile'     => $request->file('file')->getClientOriginalName(),
            'peringatan'   => $peringatan,
            'routeKembali' => route('pengungsi.index', $lokasi->id),
            'routeSimpan'  => route('pengungsi.import.confirm', $lokasi->id),
        ]);
    }

    public function confirm(Request $request, Lokasi $lokasi)
    {
        $this->pastikanBolehKelola($request, $lokasi);

        $data = session($this->kunciSession($lokasi));

        if (! $data) {
            return $this->kembali($lokasi, 'Tidak ada data untuk di-import. Silakan upload ulang file.', 'error');
        }

        // Semua atau tidak sama sekali: tidak ada lagi import yang tersimpan setengah
        $hasil = DB::transaction(function () use ($lokasi, $data) {
            $tambah = $perbarui = 0;
            foreach ($data as $row) {
                $id = $row['id'];
                unset($row['id']);

                if ($id && ($p = Pengungsi::where('lokasi_id', $lokasi->id)->find($id))) {
                    $p->update($row);
                    $perbarui++;
                } else {
                    Pengungsi::create($row + ['lokasi_id' => $lokasi->id]);
                    $tambah++;
                }
            }
            return compact('tambah', 'perbarui');
        });

        session()->forget($this->kunciSession($lokasi));

        return $this->kembali($lokasi, "Import selesai: {$hasil['tambah']} pengungsi ditambahkan, {$hasil['perbarui']} diperbarui", 'success');
    }

    /* ---------------------------------------------------------------- */

    private function periksa(Lokasi $lokasi, array $rows): array
    {
        $existing = Pengungsi::where('lokasi_id', $lokasi->id)->get()
            ->keyBy(fn ($p) => N::kunci($p->nama) . '|' . ($p->tanggal_lahir ? Carbon::parse($p->tanggal_lahir)->toDateString() : ''));
        $dipakai = [];
        $hasil   = [];

        foreach ($rows as $r) {
            $error = [];

            $nama    = N::teks($r['nama'] ?? null);
            $asal    = N::teks($r['asal'] ?? null);
            $kondisi = N::teks($r['kondisi_kesehatan'] ?? null);
            $riwayat = N::teks($r['riwayat_penyakit'] ?? null);

            if (! $nama) {
                $error[] = 'Nama kosong';
            } elseif (mb_strlen($nama) > 255) {
                $error[] = 'Nama terlalu panjang';
            }
            if (! $asal) {
                $error[] = 'Asal kosong';
            }

            // --- tanggal lahir & usia ---
            $tanggal = $this->tanggal($r['tanggal_lahir'] ?? null);
            if ($tanggal === null) {
                $error[] = 'Tanggal lahir kosong';
            } elseif ($tanggal === false) {
                $error[] = 'Tanggal lahir tidak dikenali, gunakan format TAHUN-BULAN-TANGGAL (contoh: 1990-07-25)';
            } elseif ($tanggal->isFuture() || $tanggal->year < 1900) {
                $error[] = 'Tanggal lahir tidak masuk akal (' . $tanggal->format('d-m-Y') . ')';
            }
            $tanggalValid  = $tanggal instanceof Carbon && ! $tanggal->isFuture() && $tanggal->year >= 1900;
            $usiaHitung    = $tanggalValid ? (int) $tanggal->diffInYears(now()) : null;

            $usia = N::angka($r['usia'] ?? null);
            if ($usia === false || ($usia !== null && ($usia < 0 || $usia > 130 || floor($usia) != $usia))) {
                $error[] = 'Usia harus bilangan bulat 0–130';
            } elseif ($usia !== null && $usiaHitung !== null && abs($usia - $usiaHitung) > 1) {
                $error[] = "Usia ({$usia}) tidak sesuai tanggal lahir (seharusnya {$usiaHitung} tahun)";
            }
            $usia = $usia === null || $usia === false ? $usiaHitung : (int) $usia;

            // --- nomor KK ---
            // Boleh kosong (dokumen bisa hilang saat bencana); jika diisi harus 16 digit.
            $kk = $this->nomorKk($r['nomor_kk'] ?? null);
            if (is_array($kk)) {
                $error[] = $kk[0];
                $kk = null;
            }

            // --- pilihan tetap ---
            $jk = $this->pilih($r['jenis_kelamin'] ?? null, self::JENIS_KELAMIN);
            if ($jk === null) {
                $error[] = 'Jenis kelamin kosong';
            } elseif ($jk === false) {
                $error[] = 'Jenis kelamin "' . N::teks($r['jenis_kelamin']) . '" tidak dikenali (isi Laki-laki atau Perempuan)';
                $jk = null;
            }

            $rentanTeks = N::teks($r['kelompok_rentan'] ?? null);
            $rentan = $rentanTeks === null ? null : $this->pilih($rentanTeks, array_combine(
                self::KELOMPOK_RENTAN,
                array_map(fn ($v) => [mb_strtolower($v)], self::KELOMPOK_RENTAN)
            ) + ['Tidak' => ['tidak', '-', 'tidak ada', 'bukan']]);
            $rentanSalah = $rentan === false;
            if ($rentanSalah) {
                $error[] = "Kelompok rentan \"{$rentanTeks}\" tidak dikenali (pilihan: " . implode(', ', self::KELOMPOK_RENTAN) . ')';
                $rentan = null;
            }

            // --- duplikat ---
            $kunci = $nama && $tanggalValid ? N::kunci($nama) . '|' . $tanggal->toDateString() : null;
            if ($kunci && isset($dipakai[$kunci])) {
                $error[] = "Pengungsi sama dengan baris {$dipakai[$kunci]}";
            } elseif ($kunci) {
                $dipakai[$kunci] = $r['_row'];
            }
            $lama = $kunci ? $existing->get($kunci) : null;

            // Nilai bawaan hanya untuk pengungsi baru; untuk data lama, kolom kosong = tidak diubah
            if (! $lama) {
                $kondisi ??= 'Sehat';
                if (! $rentanSalah) {
                    $rentan ??= 'Tidak';
                }
            }

            $baru = [
                'nama'              => $nama,
                'asal'              => $asal,
                'tanggal_lahir'     => $tanggalValid ? $tanggal->toDateString() : null,
                'nomor_kk'          => $kk,
                'usia'              => $usia,
                'jenis_kelamin'     => $jk,
                'kondisi_kesehatan' => $kondisi,
                'kelompok_rentan'   => $rentan,
                'riwayat_penyakit'  => $riwayat,
            ];

            $ubah = [];
            if ($lama && ! $error) {
                $dataLama = $lama->only(array_keys($baru));
                $dataLama['tanggal_lahir'] = $lama->tanggal_lahir ? Carbon::parse($lama->tanggal_lahir)->toDateString() : null;
                foreach ($baru as $k => $v) {
                    if ($v !== null && (string) $v !== (string) ($dataLama[$k] ?? '')) {
                        $ubah[$k] = $dataLama[$k] ?? null;
                    }
                }
            }

            $hasil[] = [
                'row'    => $r['_row'],
                'status' => $error ? 'error' : (! $lama ? 'tambah' : ($ubah ? 'perbarui' : 'sama')),
                'error'  => $error,
                'data'   => array_merge($baru, [
                    'tanggal_lahir' => $tanggalValid ? $tanggal->translatedFormat('d M Y') : N::teks($r['tanggal_lahir'] ?? null),
                    'nomor_kk'      => $kk ?? N::teks($r['nomor_kk'] ?? null),
                    'jenis_kelamin' => $jk ?? N::teks($r['jenis_kelamin'] ?? null),
                    'kelompok_rentan' => $rentan ?? $rentanTeks ?? ($lama?->kelompok_rentan),
                    'kondisi_kesehatan' => $kondisi ?? ($lama?->kondisi_kesehatan),
                ]),
                'lama'   => $ubah,
                // Kolom opsional yang kosong di file tidak menghapus data lama
                'simpan' => $error ? null : ['id' => $lama?->id] + ($lama ? array_filter($baru, fn ($v) => $v !== null) : $baru),
            ];
        }

        return $hasil;
    }

    /** Tanggal dari teks (berbagai format) atau angka seri Excel. null = kosong, false = tidak dikenali. */
    private function tanggal($nilai)
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }

        if (is_numeric($nilai) && (float) $nilai > 0 && (float) $nilai < 80000) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $nilai))->startOfDay();
        }

        $s = trim((string) $nilai);
        foreach (['Y-m-d', 'd-m-Y', 'd/m/Y', 'd.m.Y', 'Y/m/d', 'Y-m-d H:i:s'] as $format) {
            try {
                $d = Carbon::createFromFormat('!' . $format, $s);
                if ($d && $d->format($format) === $s) {
                    return $d->startOfDay();
                }
            } catch (\Throwable) {
                // coba format berikutnya
            }
        }

        return false;
    }

    /** Nomor KK 16 digit. null = kosong, [pesan] = tidak valid. */
    private function nomorKk($nilai)
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }

        // Angka 16 digit yang dibaca Excel sebagai bilangan sudah kehilangan digit terakhirnya
        if (is_float($nilai) || (is_string($nilai) && stripos($nilai, 'e+') !== false)) {
            return ['Nomor KK rusak karena dibaca Excel sebagai angka (digit terakhir hilang). Format kolom Nomor KK sebagai Teks lalu ketik ulang'];
        }

        $kk = preg_replace('/[\s\'.-]/', '', (string) $nilai);

        if (! ctype_digit($kk) || strlen($kk) !== 16) {
            return ['Nomor KK harus 16 digit angka (terbaca: ' . N::teks($nilai) . ')'];
        }

        return $kk;
    }

    /** Mencocokkan teks dengan daftar pilihan tetap. null = kosong, false = tidak dikenali. */
    private function pilih($nilai, array $pilihan)
    {
        $kunci = N::kunci($nilai);
        if ($kunci === '') {
            return null;
        }
        foreach ($pilihan as $nilaiTetap => $alias) {
            if ($kunci === mb_strtolower($nilaiTetap) || in_array($kunci, $alias, true)) {
                return $nilaiTetap;
            }
        }
        return false;
    }

    private function pastikanBolehKelola(Request $request, Lokasi $lokasi): void
    {
        abort_unless($lokasi->bisaDikelolaOleh($request->user()), 403, 'Anda tidak berhak mengelola pengungsi di posko ini.');
    }

    private function kunciSession(Lokasi $lokasi): string
    {
        return "import.pengungsi.{$lokasi->id}";
    }

    private function kembali(Lokasi $lokasi, string $pesan, string $jenis)
    {
        Swal::toast($pesan, $jenis)->timerProgressBar();
        return redirect()->route('pengungsi.index', $lokasi->id);
    }
}
