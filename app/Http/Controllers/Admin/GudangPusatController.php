<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Donasi;
use App\Models\GudangPusat;
use App\Models\Logistik;
use App\Models\MutasiGudang;
use App\Models\StokGudang;
use App\Services\DonasiService;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert as Swal;

/** Gudang / posko pusat GMLS: info & rekening, stok, barang masuk, kirim ke posko. */
class GudangPusatController extends Controller
{
    public function __construct(private DonasiService $service) {}

    public function index()
    {
        $gudang = GudangPusat::utama() ?? GudangPusat::create(['nama' => 'Posko Pusat GMLS']);

        $stok = StokGudang::with('logistik')
            ->where('gudang_pusat_id', $gudang->id)
            ->get()
            ->sortBy(fn ($s) => $s->logistik->nama_item ?? '')
            ->values();

        $mutasi = MutasiGudang::with('logistik', 'lokasi', 'donasi', 'user')
            ->where('gudang_pusat_id', $gudang->id)
            ->latest('tanggal')->latest('id')
            ->paginate(20);

        $poskos    = DonasiController::daftarPosko();
        $logistiks = Logistik::orderBy('nama_item')->get(['id', 'nama_item', 'satuan']);

        $donasiMenunggu      = Donasi::where('status', Donasi::MENUNGGU)->count();
        $danaBelumDisalurkan = Donasi::where('jenis', Donasi::UANG)
            ->whereIn('status', [Donasi::DITERIMA, Donasi::SEBAGIAN])
            ->with('penyaluran')->get()->sum(fn ($d) => $d->sisaDana());

        return view('pages.admin.gudang.index', compact('gudang', 'stok', 'mutasi', 'poskos', 'logistiks', 'donasiMenunggu', 'danaBelumDisalurkan'));
    }

    public function update(Request $request, GudangPusat $gudang)
    {
        $data = $request->validate([
            'nama'               => 'required|string|max:255',
            'alamat'             => 'nullable|string|max:1000',
            'latitude'           => 'nullable|numeric|between:-11,6',
            'longitude'          => 'nullable|numeric|between:95,141',
            'penanggung_jawab'   => 'nullable|string|max:255',
            'kontak'             => 'nullable|string|max:30',
            'nama_bank'          => 'nullable|required_with:nomor_rekening|string|max:60',
            'nomor_rekening'     => 'nullable|string|max:40',
            'atas_nama_rekening' => 'nullable|required_with:nomor_rekening|string|max:255',
            'keterangan'         => 'nullable|string|max:1000',
        ], [
            'latitude.between'  => 'Latitude harus di wilayah Indonesia.',
            'longitude.between' => 'Longitude harus di wilayah Indonesia.',
        ]);

        $gudang->update($data);

        Swal::toast('Info gudang pusat disimpan', 'success')->timerProgressBar();

        return back();
    }

    public function masuk(Request $request, GudangPusat $gudang)
    {
        $data = $request->validate([
            'logistik_id' => 'required|exists:logistiks,id',
            'jumlah'      => 'required|integer|min:1',
            'sumber'      => 'required|string|max:255',
            'tanggal'     => 'required|date|before_or_equal:today',
            'keterangan'  => 'nullable|string|max:500',
        ]);

        $this->service->barangMasukGudang($gudang, $data, $request->user());

        Swal::toast('Barang masuk gudang pusat dicatat', 'success')->timerProgressBar();

        return back();
    }

    public function kirim(Request $request, GudangPusat $gudang)
    {
        $data = $request->validate([
            'lokasi_id'   => 'required|exists:lokasis,id',
            'logistik_id' => 'required|exists:logistiks,id',
            'jumlah'      => 'required|integer|min:1',
            'tanggal'     => 'required|date|before_or_equal:today',
            'keterangan'  => 'nullable|string|max:500',
        ]);

        $this->service->kirimKePosko($gudang->id, $data, $request->user());

        Swal::toast('Barang dikirim, stok posko sudah bertambah', 'success')->timerProgressBar();

        return back();
    }
}
