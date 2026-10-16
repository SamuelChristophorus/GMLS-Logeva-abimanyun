<?php

namespace App\Http\Controllers\Admin\Import;

use App\Http\Controllers\Controller;
use App\Imports\Support\SpreadsheetReader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert as Swal;

/**
 * Alur import yang sama untuk semua menu admin:
 *   upload -> baca file -> periksa tiap baris -> pratinjau -> konfirmasi -> simpan.
 *
 * Setiap menu cukup menentukan kolom, aturan pemeriksaan, dan cara menyimpan.
 */
abstract class BaseImportController extends Controller
{
    /** contoh: 'logistik' (dipakai untuk nama session & route) */
    abstract protected function nama(): string;

    /** contoh: 'Master Logistik' */
    abstract protected function judul(): string;

    /** contoh: 'item' -> "5 item ditambahkan" */
    abstract protected function satuan(): string;

    /** Kolom yang dibaca dari file: ['kunci' => ['alias', ...]] */
    abstract protected function kolomFile(): array;

    /** Kolom yang wajib ada di baris judul file */
    abstract protected function kolomWajib(): array;

    /** Kolom yang ditampilkan di pratinjau: ['kunci' => 'Label'] */
    abstract protected function kolomTampil(): array;

    /**
     * Memeriksa semua baris. Harus mengembalikan daftar:
     * ['row' => int, 'status' => tambah|perbarui|sama|error, 'error' => [..],
     *  'data' => [kunci => nilai untuk ditampilkan], 'lama' => [kunci => nilai lama],
     *  'simpan' => [data yang dikirim ke simpan()]]
     */
    abstract protected function periksa(array $rows): array;

    /** Menyimpan baris valid. Mengembalikan ['tambah' => n, 'perbarui' => n] */
    abstract protected function simpan(array $data): array;

    /** Peringatan umum yang ditampilkan di pratinjau (opsional) */
    protected function peringatan(): array
    {
        return [];
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

        $hasil = SpreadsheetReader::read($request->file('file'), $this->kolomFile(), $this->kolomWajib());

        if ($hasil['missing']) {
            $label = collect($hasil['missing'])->map(fn ($k) => '"' . ($this->kolomTampil()[$k] ?? $k) . '"')->implode(', ');
            Swal::toast("Kolom {$label} tidak ditemukan. Gunakan template yang disediakan.", 'error')->timerProgressBar();
            return redirect()->route("admin.{$this->nama()}.index");
        }

        if (! $hasil['rows']) {
            Swal::toast('File tidak berisi data.', 'error')->timerProgressBar();
            return redirect()->route("admin.{$this->nama()}.index");
        }

        $baris = $this->periksa($hasil['rows']);
        $valid = collect($baris)
            ->whereIn('status', ['tambah', 'perbarui'])
            ->pluck('simpan')
            ->values()
            ->all();

        session(["import.{$this->nama()}" => $valid]);

        return view('pages.admin.import.preview', [
            'judul'       => $this->judul(),
            'kolom'       => $this->kolomTampil(),
            'baris'       => $baris,
            'ringkasan'   => collect($baris)->countBy('status'),
            'jumlahValid' => count($valid),
            'namaFile'    => $request->file('file')->getClientOriginalName(),
            'peringatan'  => $this->peringatan(),
            'routeKembali' => route("admin.{$this->nama()}.index"),
            'routeSimpan'  => route("admin.{$this->nama()}.import.confirm"),
        ]);
    }

    public function confirm()
    {
        $key  = "import.{$this->nama()}";
        $data = session($key);

        if (! $data) {
            Swal::toast('Tidak ada data untuk di-import. Silakan upload ulang file.', 'error')->timerProgressBar();
            return redirect()->route("admin.{$this->nama()}.index");
        }

        $hasil = DB::transaction(fn () => $this->simpan($data));

        session()->forget($key);

        Swal::toast(
            "Import selesai: {$hasil['tambah']} {$this->satuan()} ditambahkan, {$hasil['perbarui']} diperbarui",
            'success'
        )->timerProgressBar();

        return redirect()->route("admin.{$this->nama()}.index");
    }

    /** Membandingkan data lama & baru, mengembalikan kolom yang berubah: [kunci => nilai lama] */
    protected function bandingkan(array $lama, array $baru): array
    {
        $berubah = [];
        foreach ($baru as $k => $v) {
            if ($v === null) {
                continue; // kosong di file = tidak diubah
            }
            $a = $lama[$k] ?? null;
            $sama = (is_numeric($a) && is_numeric($v)) ? ((float) $a == (float) $v) : ((string) $a === (string) $v);
            if (! $sama) {
                $berubah[$k] = $a;
            }
        }
        return $berubah;
    }
}
