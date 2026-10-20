<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Lokasi;
use App\Models\Logistik;
use App\Models\StokMasuk;
use App\Models\StokLogistik;
use App\Models\StokKeluar;
use App\Services\OperasionalLokasiService;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert as Swal;

class StokMasukController extends Controller
{
    public function create($lokasi)
    {
        $lokasi = Lokasi::findOrFail($lokasi);

        abort_unless($lokasi->bisaDikelolaOleh(auth()->user()), 403, 'Anda tidak berhak mengelola logistik posko ini.');

        $logistiks = Logistik::orderBy('nama_item')->get();

        return view('logistik.create', compact(
            'lokasi',
            'logistiks'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'lokasi_id' => 'required|exists:lokasis,id',
            'logistik_id' => 'required|exists:logistiks,id',
            'jumlah_masuk' => 'required|integer|min:1',
            'sumber_bantuan' => 'required',
            'tanggal_masuk' => 'required|date|before_or_equal:today',
        ], [
            'tanggal_masuk.before_or_equal' => 'Tanggal tidak boleh setelah hari ini.',
        ]);

        $lokasi = Lokasi::findOrFail($request->lokasi_id);

        abort_unless($lokasi->bisaDikelolaOleh(auth()->user()), 403, 'Anda tidak berhak mengelola logistik posko ini.');

        DB::transaction(function () use ($request) {
            StokMasuk::create([
                'lokasi_id' => $request->lokasi_id,
                'logistik_id' => $request->logistik_id,
                'relawan_id' => auth()->user()->relawan?->id,
                'jumlah_masuk' => $request->jumlah_masuk,
                'sumber_bantuan' => $request->sumber_bantuan,
                'tanggal_masuk' => $request->tanggal_masuk,
                'keterangan' => $request->keterangan,
            ]);

            $stok = StokLogistik::lockForUpdate()->firstOrCreate(
                [
                    'lokasi_id' => $request->lokasi_id,
                    'logistik_id' => $request->logistik_id,
                ],
                [
                    'jumlah_stok' => 0,
                    'status_ketersediaan' => 'Merah'
                ]
            );

            $stok->ubahJumlah((int) $request->jumlah_masuk);
        });

        Swal::toast('Barang masuk berhasil dicatat, stok sudah ditambah', 'success')->timerProgressBar();

        return redirect()->route('logistik.index', $lokasi->id);
    }

    public function index($lokasi_id, OperasionalLokasiService $operasionalService)
{
    $lokasi = Lokasi::findOrFail($lokasi_id);

    // Hitungan stok & status SAMA dengan panel detail posko:
    // kebutuhan harian (Master Logistik) x jumlah pengungsi tercatat
    [
        'stok'             => $stok,
        'stok_status'      => $stokStatus,
        'jumlah_pengungsi' => $jumlahPengungsi,
    ] = $operasionalService->stokPosko($lokasi);

    // Barang yang paling cepat habis (lebih jelas bagi petugas daripada rata-rata semua barang)
    $palingCepatHabis = $stok->whereNotNull('cukup_hari')->sortBy('cukup_hari')->first();

    // Riwayat gabungan barang masuk (+) & keluar (-), terbaru di atas
    $riwayat = StokMasuk::with('logistik')
        ->where('lokasi_id', $lokasi_id)
        ->get()
        ->map(fn ($m) => (object) [
            'jenis'      => 'masuk',
            'tanggal'    => \Carbon\Carbon::parse($m->tanggal_masuk),
            'dicatat'    => $m->created_at,
            'item'       => optional($m->logistik)->nama_item ?? 'Data tidak ditemukan',
            'satuan'     => optional($m->logistik)->satuan,
            'jumlah'     => $m->jumlah_masuk,
            'keterangan' => 'Dari ' . $m->sumber_bantuan,
            'catatan'    => $m->keterangan,
        ])
        ->concat(
            StokKeluar::with('logistik')
                ->where('lokasi_id', $lokasi_id)
                ->get()
                ->map(fn ($k) => (object) [
                    'jenis'      => 'keluar',
                    'tanggal'    => $k->tanggal_keluar,
                    'dicatat'    => $k->created_at,
                    'item'       => optional($k->logistik)->nama_item ?? 'Data tidak ditemukan',
                    'satuan'     => optional($k->logistik)->satuan,
                    'jumlah'     => $k->jumlah_keluar,
                    'keterangan' => $k->labelTujuan() . ($k->penerima ? ' · ' . $k->penerima : ''),
                    'catatan'    => $k->keterangan,
                ])
        )
        ->sortByDesc(fn ($r) => $r->tanggal->format('Ymd') . $r->dicatat?->format('His'))
        ->values();

    $bisaKelola = $lokasi->bisaDikelolaOleh(auth()->user());
    $isAdmin    = auth()->user()?->hasRole('admin') ?? false;

    return view('logistik.index', compact(
        'lokasi',
        'stok',
        'stokStatus',
        'jumlahPengungsi',
        'palingCepatHabis',
        'riwayat',
        'bisaKelola',
        'isAdmin'
    ));
}
}