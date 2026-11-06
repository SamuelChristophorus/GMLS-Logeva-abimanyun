<?php

namespace App\Http\Controllers\Admin\Import;

use App\Http\Controllers\Controller;
use App\Models\Desa;
use App\Models\Relawan;
use App\Services\ImportRuinriskService;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert as Swal;

/**
 * Menu admin "Import TEA RUinRISK": upload file Data TEA dari aplikasi RUinRISK -> pratinjau
 * (pilih desa & relawan untuk posko baru, lihat peringatan) -> konfirmasi. Aturan ada di ImportRuinriskService.
 */
class RuinriskImportController extends Controller
{
    private const SESSION = 'import.ruinrisk';

    public function __construct(private ImportRuinriskService $service) {}

    public function index(Request $request)
    {
        $pratinjau = $request->session()->get(self::SESSION);

        return view('pages.admin.lokasi.import-ruinrisk', [
            'pratinjau' => $pratinjau,
            'desas'     => $pratinjau ? Desa::orderBy('nama_desa')->get(['id', 'nama_desa']) : collect(),
            'relawans'  => $pratinjau ? Relawan::with('user:id,name,email')->whereHas('user')->get()->sortBy('user.name')->values() : collect(),
            'jarak'     => ImportRuinriskService::JARAK_DEKAT_METER,
        ]);
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:5120',
        ], [
            'file.required' => 'Pilih file Data TEA dari RUinRISK.',
            'file.mimes'    => 'Format file harus .xlsx, .xls, atau .csv.',
            'file.max'      => 'Ukuran file maksimal 5 MB.',
        ]);

        $hasil = $this->service->baca($request->file('file'));

        if ($hasil['missing']) {
            $label = collect($hasil['missing'])->map(fn ($k) => '"' . (ImportRuinriskService::LABEL[$k] ?? $k) . '"')->implode(', ');

            return back()->withErrors(['file' => "Kolom {$label} tidak ditemukan. Pastikan file adalah export Data TEA dari RUinRISK."]);
        }
        if (! $hasil['rows']) {
            return back()->withErrors(['file' => 'File tidak berisi data.']);
        }

        $request->session()->put(self::SESSION, [
            'namaFile' => $request->file('file')->getClientOriginalName(),
            'baris'    => $this->service->periksa($hasil['rows']),
        ]);

        return redirect()->route('admin.lokasi.ruinrisk.index');
    }

    public function confirm(Request $request)
    {
        $pratinjau = $request->session()->get(self::SESSION);

        if (! $pratinjau) {
            Swal::toast('Pratinjau sudah kedaluwarsa. Upload ulang file-nya.', 'error')->timerProgressBar();

            return redirect()->route('admin.lokasi.ruinrisk.index');
        }

        $data = $request->validate([
            'pilihan'              => 'nullable|array',
            'pilihan.*.aksi'       => 'nullable|in:tambah,lewati',
            'pilihan.*.desa_id'    => 'nullable|integer',
            'pilihan.*.relawan_id' => 'nullable|integer',
        ]);

        $hitung = $this->service->simpan($pratinjau['baris'], $data['pilihan'] ?? [], $request->user());
        $request->session()->forget(self::SESSION);

        Swal::toast("RUinRISK: {$hitung['tambah']} posko ditambah, {$hitung['perbarui']} diperbarui, {$hitung['dilewati']} dilewati", 'success')->timerProgressBar();

        return redirect()->route('admin.lokasi.index');
    }

    public function batal(Request $request)
    {
        $request->session()->forget(self::SESSION);

        return redirect()->route('admin.lokasi.ruinrisk.index');
    }
}
