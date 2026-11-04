<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Donasi;
use App\Models\Logistik;
use App\Models\Lokasi;
use App\Models\StokGudang;
use App\Services\DonasiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert as Swal;

/** Panel admin: cek donasi masuk, terima/tolak, catat penyaluran. */
class DonasiController extends Controller
{
    public function __construct(private DonasiService $service) {}

    public function index(Request $request)
    {
        $status = $request->query('status');
        $jenis  = $request->query('jenis');
        $cari   = trim((string) $request->query('cari'));

        $donasis = Donasi::with('items.logistik', 'lokasi', 'gudang')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($jenis, fn ($q) => $q->where('jenis', $jenis))
            ->when($cari !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('kode', 'like', "%{$cari}%")
                ->orWhere('nama_donatur', 'like', "%{$cari}%")
                ->orWhere('kontak', 'like', "%{$cari}%")))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $jumlahStatus = Donasi::selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status');

        return view('pages.admin.donasi.index', compact('donasis', 'status', 'jenis', 'cari', 'jumlahStatus'));
    }

    public function show(Donasi $donasi)
    {
        $donasi->load(['items.logistik', 'lokasi.desa', 'gudang', 'penyaluran.lokasi', 'penyaluran.gudang', 'penyaluran.logistik', 'jejak', 'verifikator']);

        $poskos     = $this->daftarPosko();
        $logistiks  = Logistik::orderBy('nama_item')->get(['id', 'nama_item', 'satuan']);
        $stokGudang = StokGudang::where('gudang_pusat_id', $donasi->gudang_pusat_id)->pluck('jumlah_stok', 'logistik_id');

        return view('pages.admin.donasi.show', compact('donasi', 'poskos', 'logistiks', 'stokGudang'));
    }

    /** Lihat bukti transfer (file privat, hanya admin). */
    public function bukti(Donasi $donasi)
    {
        abort_unless($donasi->bukti_transfer && Storage::disk('local')->exists($donasi->bukti_transfer), 404);

        return Storage::disk('local')->response($donasi->bukti_transfer);
    }

    public function verifikasi(Request $request, Donasi $donasi)
    {
        $data = $request->validate([
            'nominal_diterima' => 'required|integer|min:1',
            'catatan'          => 'nullable|string|max:500',
        ], ['nominal_diterima.required' => 'Isi jumlah dana yang benar-benar masuk rekening.']);

        $this->service->verifikasiUang($donasi, $data['nominal_diterima'], $request->user(), $data['catatan'] ?? null);

        return $this->selesai('Dana donasi dikonfirmasi diterima');
    }

    public function terima(Request $request, Donasi $donasi)
    {
        $data = $request->validate([
            'diterima'   => 'required|array',
            'diterima.*' => 'nullable|integer|min:0',
            'catatan'    => 'nullable|string|max:500',
        ]);

        $this->service->terimaBarang($donasi, $data['diterima'], $request->user(), $data['catatan'] ?? null);

        return $this->selesai($donasi->isKePosko() ? 'Barang diterima dan masuk stok posko' : 'Barang diterima dan masuk stok gudang pusat');
    }

    public function tolak(Request $request, Donasi $donasi)
    {
        $data = $request->validate(['alasan' => 'nullable|string|max:500']);

        $this->service->tolak($donasi, $request->user(), $data['alasan'] ?? null);

        return $this->selesai('Donasi ditandai tidak terverifikasi');
    }

    public function salurkan(Request $request, Donasi $donasi)
    {
        $uang = $donasi->isUang();

        $data = $request->validate([
            'lokasi_id'   => [$uang ? 'nullable' : 'required', 'exists:lokasis,id'],
            'ke_gudang'   => 'nullable|boolean',
            'nominal'     => [$uang ? 'required' : 'nullable', 'integer', 'min:1'],
            'logistik_id' => [$uang ? 'nullable' : 'required', 'exists:logistiks,id'],
            'jumlah'      => [$uang ? 'nullable' : 'required', 'required_with:logistik_id', 'integer', 'min:1'],
            'tanggal'     => 'required|date|before_or_equal:today',
            'keterangan'  => [$uang ? 'required' : 'nullable', 'string', 'max:500'],
            'foto'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ], [
            'lokasi_id.required'      => 'Pilih posko tujuan.',
            'nominal.required'        => 'Isi jumlah dana yang dipakai.',
            'keterangan.required'     => 'Tulis dana dipakai untuk apa, supaya donatur bisa membacanya.',
            'jumlah.required_with'    => 'Isi jumlah barang yang dibeli.',
            'tanggal.before_or_equal' => 'Tanggal tidak boleh setelah hari ini.',
        ]);

        $uang
            ? $this->service->salurkanDana($donasi, $data, $request->file('foto'), $request->user())
            : $this->service->salurkanBarang($donasi, $data, $request->file('foto'), $request->user());

        return $this->selesai('Penyaluran dicatat dan tampil di jejak donasi');
    }

    public function kabar(Request $request, Donasi $donasi)
    {
        $data = $request->validate([
            'judul'      => 'required|string|max:120',
            'keterangan' => 'nullable|string|max:1000',
            'foto'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $this->service->tambahKabar($donasi, $data['judul'], $data['keterangan'] ?? null, $request->file('foto'), $request->user());

        return $this->selesai('Kabar ditambahkan ke jejak donasi');
    }

    /** Posko tujuan penyaluran: yang tampil di publik, yang beroperasi di atas. */
    public static function daftarPosko()
    {
        return Lokasi::tampilPublik()
            ->get(['id', 'nama_lokasi', 'status_operasional'])
            ->sortBy(fn ($l) => ($l->isBeroperasi() ? '0' : '1') . mb_strtolower($l->nama_lokasi))
            ->values();
    }

    private function selesai(string $pesan)
    {
        Swal::toast($pesan, 'success')->timerProgressBar();

        return back();
    }
}
