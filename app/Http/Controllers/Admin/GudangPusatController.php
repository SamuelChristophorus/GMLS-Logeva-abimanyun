<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Donasi;
use App\Models\GudangPusat;
use App\Models\Logistik;
use App\Models\Lokasi;
use App\Models\MutasiGudang;
use App\Models\StokGudang;
use App\Services\DonasiService;
use App\Services\OperasionalLokasiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RealRashid\SweetAlert\Facades\Alert as Swal;

/** Pusat Logistik GMLS: gudang pusat (info & rekening, stok, barang masuk, kirim ke posko) dan pantau stok semua posko. */
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

    /**
     * Pantau stok semua posko dalam satu tabel, berdampingan dengan stok gudang pusat.
     * Hanya MEMBACA data yang sudah ada (stok_logistiks & stok_gudangs) dengan rumus yang
     * sama seperti Monitoring Logistik posko, jadi angka & warnanya selalu selaras.
     */
    public function stokPosko(Request $request, OperasionalLokasiService $operasional)
    {
        $semua  = $request->boolean('semua');
        $gudang = GudangPusat::utama();
        $urutan = ['segera' => 0, 'pantau' => 1, 'belum' => 2, 'cukup' => 3];

        $baris = Lokasi::tampilPublik()
            ->when(! $semua, fn ($q) => $q->where('status_operasional', Lokasi::OPS_BEROPERASI))
            ->with('desa')
            ->get()
            ->map(function ($l) use ($operasional) {
                $b = $operasional->statusBantuan($l);
                $b['lokasi'] = $l;
                $b['stok']   = $b['stok']->keyBy('logistik_id');

                return $b;
            })
            ->sortBy(fn ($b) => sprintf('%d-%s', $urutan[$b['tingkat']] ?? 9, mb_strtolower($b['lokasi']->nama_lokasi)))
            ->values();

        $stokGudang = $gudang
            ? StokGudang::where('gudang_pusat_id', $gudang->id)->where('jumlah_stok', '>', 0)->pluck('jumlah_stok', 'logistik_id')
            : collect();

        // Kolom barang: barang pokok + barang yang ada stoknya di posko mana pun / gudang pusat
        $idBarang = $baris->flatMap(fn ($b) => $b['stok']->keys())->merge($stokGudang->keys())->unique();
        $barang = Logistik::whereIn('id', $idBarang)->get(['id', 'nama_item', 'satuan', 'kebutuhan_harian'])
            ->sortBy(fn ($l) => ($l->kebutuhan_harian > 0 ? '0' : '1') . mb_strtolower($l->nama_item))
            ->values();

        // Total per barang di semua posko yang ditampilkan
        $total = $barang->mapWithKeys(fn ($l) => [$l->id => [
            'stok'       => $baris->sum(fn ($b) => $b['stok'][$l->id]->jumlah_stok ?? 0),
            'kekurangan' => $baris->sum(fn ($b) => ($b['stok'][$l->id]->perlu_dikirim ?? false) ? $b['stok'][$l->id]->kekurangan : 0),
        ]]);

        return view('pages.admin.gudang.stok-posko', compact('gudang', 'baris', 'barang', 'stokGudang', 'total', 'semua'));
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

    /**
     * Kirim beberapa barang sekaligus ke satu posko (dari halaman Stok Semua Posko).
     * Semua barang terkirim, atau tidak sama sekali jika salah satu stoknya tidak cukup.
     */
    public function kirimBanyak(Request $request, GudangPusat $gudang)
    {
        $data = $request->validate([
            'lokasi_id'  => 'required|exists:lokasis,id',
            'items'      => 'required|array',
            'items.*'    => 'nullable|integer|min:0|max:1000000',
            'tanggal'    => 'required|date|before_or_equal:today',
            'keterangan' => 'nullable|string|max:500',
        ], ['tanggal.before_or_equal' => 'Tanggal tidak boleh setelah hari ini.']);

        $items = collect($data['items'])->map(fn ($j) => (int) $j)->filter(fn ($j) => $j > 0);

        if ($items->isEmpty()) {
            throw ValidationException::withMessages(['items' => 'Isi jumlah minimal satu barang yang akan dikirim.']);
        }

        if (Logistik::whereIn('id', $items->keys())->count() !== $items->count()) {
            throw ValidationException::withMessages(['items' => 'Ada barang yang tidak dikenal. Muat ulang halaman lalu coba lagi.']);
        }

        DB::transaction(fn () => $items->each(fn ($jumlah, $logistikId) => $this->service->kirimKePosko($gudang->id, [
            'lokasi_id'   => $data['lokasi_id'],
            'logistik_id' => $logistikId,
            'jumlah'      => $jumlah,
            'tanggal'     => $data['tanggal'],
            'keterangan'  => $data['keterangan'] ?? null,
        ], $request->user())));

        $posko = Lokasi::find($data['lokasi_id']);
        Swal::toast($items->count() . ' jenis barang dikirim ke ' . $posko->nama_lokasi . ', stok posko sudah bertambah', 'success')->timerProgressBar();

        return back();
    }
}
