<?php

namespace App\Http\Controllers;

use App\Models\Donasi;
use App\Models\DonasiItem;
use App\Models\GudangPusat;
use App\Models\Logistik;
use App\Models\Lokasi;
use App\Models\PenyaluranDonasi;
use App\Services\DonasiService;
use App\Services\OperasionalLokasiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Halaman donatur (publik, tanpa login): beri donasi, lacak donasi dengan kode,
 * dan laporan penyaluran donasi.
 */
class DonasiController extends Controller
{
    private const SESI_JAWABAN = 'donasi_jawaban';
    private const DETIK_MINIMAL = 4;            // form diisi lebih cepat dari ini = bot
    private const JAM_KEDALUWARSA = 3;          // form yang dibuka terlalu lama harus dimuat ulang
    private const MAKS_MENUNGGU_PER_KONTAK = 3; // donasi belum dicek per no. HP / email

    public function __construct(private DonasiService $service) {}

    /** Beranda donasi: ringkasan, posko yang butuh bantuan, donasi terbaru. */
    public function index(OperasionalLokasiService $operasional)
    {
        $gudang   = GudangPusat::utama();
        $ringkas  = $this->ringkasan();
        $butuh    = $operasional->semuaStatusBantuan()->whereIn('tingkat', ['segera', 'pantau'])->take(6)->values();
        $terbaru  = Donasi::terverifikasi()->with('items.logistik', 'lokasi', 'gudang')->latest('diverifikasi_pada')->limit(8)->get();

        return view('pages.donasi.index', compact('gudang', 'ringkas', 'butuh', 'terbaru'));
    }

    public function create(Request $request, OperasionalLokasiService $operasional)
    {
        $gudang = GudangPusat::utama();
        $poskos = $this->poskoTujuan();
        $logistiks = Logistik::orderBy('nama_item')->get(['id', 'nama_item', 'satuan']);

        // Kebutuhan tiap posko untuk ditampilkan saat donatur memilih posko
        $kebutuhan = $operasional->semuaStatusBantuan()->mapWithKeys(fn ($b) => [
            $b['lokasi']->id => $b['kebutuhan']->take(5)->map(fn ($k) => [
                'id'     => $k->logistik_id,
                'nama'   => $k->nama_item,
                'jumlah' => $k->kekurangan,
                'satuan' => $k->satuan,
            ])->values(),
        ]);

        $pilihPosko = $poskos->firstWhere('id', (int) $request->query('posko'))?->id;

        // Pengaman: soal hitungan sederhana (jawaban disimpan di session) + waktu form dibuka
        [$a, $b] = [random_int(1, 9), random_int(1, 9)];
        $request->session()->put(self::SESI_JAWABAN, $a + $b);
        $soal       = "{$a} + {$b}";
        $dibukaPada = encrypt(now()->timestamp);

        return view('pages.donasi.create', compact('gudang', 'poskos', 'logistiks', 'kebutuhan', 'pilihPosko', 'soal', 'dibukaPada'));
    }

    public function store(Request $request)
    {
        // Jebakan bot: kolom ini disembunyikan dari manusia
        if (filled($request->input('situs_web'))) {
            abort(422);
        }

        $this->cekManusia($request);

        // Kontak dirapikan dulu (spasi / strip dihapus) supaya batas per kontak tidak bisa diakali
        $request->merge(['kontak' => self::rapikanKontak((string) $request->input('kontak'))]);

        $tanpaLink = 'not_regex:/(https?:\/\/|www\.|<[a-z\/])/i';

        $data = $request->validate([
            'nama_donatur'        => ['required', 'string', 'min:3', 'max:120', $tanpaLink],
            'anonim'              => 'nullable|boolean',
            'kontak'              => ['required', 'string', 'max:100', function ($attr, $nilai, $gagal) {
                $hp    = preg_match('/^(\+62|62|0)8\d{7,12}$/', $nilai);
                $email = filter_var($nilai, FILTER_VALIDATE_EMAIL);
                if (! $hp && ! $email) {
                    $gagal('Isi nomor HP (contoh 081234567890) atau alamat email yang benar.');
                }
            }],
            'jenis'               => ['required', Rule::in([Donasi::UANG, Donasi::LOGISTIK])],
            'tujuan'              => ['required', Rule::in([Donasi::KE_PUSAT, Donasi::KE_POSKO])],
            'lokasi_id'           => ['nullable', 'required_if:tujuan,' . Donasi::KE_POSKO, Rule::in($this->poskoTujuan()->pluck('id'))],
            'nominal'             => 'nullable|required_if:jenis,' . Donasi::UANG . '|integer|min:10000|max:1000000000',
            'bukti_transfer'      => 'nullable|required_if:jenis,' . Donasi::UANG . '|image|mimes:jpg,jpeg,png,webp|max:4096',
            'items'               => 'nullable|array|max:20',
            'items.*.logistik_id' => 'nullable|exists:logistiks,id',
            'items.*.jumlah'      => 'nullable|integer|min:1|max:100000',
            'perkiraan_tiba'      => 'nullable|date|after_or_equal:today|before_or_equal:+3 months',
            'pengirim'            => ['nullable', 'string', 'max:120', $tanpaLink],
            'pesan'               => ['nullable', 'string', 'max:500', $tanpaLink],
        ], [
            'nama_donatur.min'               => 'Nama minimal 3 huruf.',
            'nama_donatur.not_regex'         => 'Nama tidak boleh berisi link.',
            'pengirim.not_regex'             => 'Kolom pengirim tidak boleh berisi link.',
            'pesan.not_regex'                => 'Pesan tidak boleh berisi link.',
            'lokasi_id.required_if'          => 'Pilih posko tujuan donasi.',
            'lokasi_id.in'                   => 'Posko yang dipilih tidak sedang menerima bantuan.',
            'nominal.required_if'            => 'Isi jumlah uang yang ditransfer.',
            'nominal.min'                    => 'Donasi uang minimal Rp 10.000.',
            'nominal.max'                    => 'Untuk donasi di atas Rp 1 miliar, silakan hubungi GMLS langsung.',
            'bukti_transfer.required_if'     => 'Unggah foto / tangkapan layar bukti transfer.',
            'bukti_transfer.image'           => 'Bukti transfer harus berupa gambar (JPG / PNG).',
            'bukti_transfer.max'             => 'Ukuran bukti transfer maksimal 4 MB.',
            'perkiraan_tiba.after_or_equal'  => 'Perkiraan tiba tidak boleh sebelum hari ini.',
            'perkiraan_tiba.before_or_equal' => 'Perkiraan tiba maksimal 3 bulan dari sekarang.',
        ]);

        // Satu kontak tidak boleh menumpuk banyak donasi yang belum dicek
        $menunggu = Donasi::where('kontak', $data['kontak'])->where('status', Donasi::MENUNGGU)->count();
        if ($menunggu >= self::MAKS_MENUNGGU_PER_KONTAK) {
            throw ValidationException::withMessages([
                'kontak' => "Masih ada {$menunggu} donasi Anda yang menunggu dicek GMLS. Tunggu sampai dicek, atau hubungi GMLS.",
            ]);
        }

        // Baris barang yang kosong diabaikan; barang yang sama digabung
        $items = collect($data['items'] ?? [])
            ->filter(fn ($i) => ! empty($i['logistik_id']) && ! empty($i['jumlah']))
            ->groupBy('logistik_id')
            ->map(fn ($baris, $id) => ['logistik_id' => (int) $id, 'jumlah' => (int) $baris->sum('jumlah')])
            ->values()
            ->all();

        if ($data['jenis'] === Donasi::LOGISTIK && empty($items)) {
            throw ValidationException::withMessages(['items' => 'Tambahkan minimal satu barang beserta jumlahnya.']);
        }

        if ($data['jenis'] === Donasi::UANG && ! GudangPusat::utama()?->punyaRekening()) {
            throw ValidationException::withMessages(['jenis' => 'Rekening donasi GMLS belum tersedia. Silakan donasi berupa barang atau hubungi GMLS.']);
        }

        $donasi = $this->service->ajukan($data, $data['jenis'] === Donasi::LOGISTIK ? $items : [], $request->file('bukti_transfer'));

        return redirect()->route('donasi.lacak', ['kode' => $donasi->kode])->with('donasi_baru', true);
    }

    /** Lacak donasi dengan kode (tanpa login). */
    public function lacak(Request $request)
    {
        $kode   = strtoupper(trim((string) $request->query('kode')));
        $donasi = null;

        if ($kode !== '') {
            $donasi = Donasi::with(['items.logistik', 'lokasi.desa', 'gudang', 'penyaluran.lokasi', 'penyaluran.gudang', 'penyaluran.logistik', 'jejak.lokasi'])
                ->where('kode', $kode)
                ->first();
        }

        return view('pages.donasi.lacak', compact('kode', 'donasi'));
    }

    /** Laporan terbuka: berapa yang diterima & ke mana disalurkan. */
    public function laporan()
    {
        $ringkas = $this->ringkasan();

        // Barang donasi yang diterima, per jenis barang
        $barang = DonasiItem::query()
            ->join('donasis', 'donasis.id', '=', 'donasi_items.donasi_id')
            ->join('logistiks', 'logistiks.id', '=', 'donasi_items.logistik_id')
            ->whereIn('donasis.status', [Donasi::DITERIMA, Donasi::SEBAGIAN, Donasi::SELESAI])
            ->groupBy('logistiks.id', 'logistiks.nama_item', 'logistiks.satuan')
            ->orderByDesc('total')
            ->get(['logistiks.nama_item', 'logistiks.satuan', DB::raw('SUM(donasi_items.jumlah_diterima) as total')]);

        // Penyaluran per posko tujuan
        $perPosko = PenyaluranDonasi::with('lokasi.desa', 'logistik', 'gudang')
            ->get()
            ->groupBy(fn ($p) => $p->lokasi_id ? 'posko-' . $p->lokasi_id : ($p->gudang_pusat_id ? 'gudang' : 'operasional'))
            ->map(fn ($baris) => [
                'nama'   => $baris->first()->namaTujuan(),
                'desa'   => $baris->first()->lokasi?->desa?->nama_desa,
                'dana'   => (int) $baris->sum('nominal'),
                'barang' => $baris->whereNotNull('logistik_id')->groupBy('logistik_id')->map(fn ($b) => [
                    'nama'   => $b->first()->logistik->nama_item ?? '-',
                    'jumlah' => (int) $b->sum('jumlah'),
                    'satuan' => $b->first()->logistik->satuan ?? '',
                ])->values(),
                'kali'   => $baris->count(),
                'terakhir' => $baris->max('tanggal'),
            ])
            ->sortByDesc('terakhir')
            ->values();

        $donasis = Donasi::terverifikasi()
            ->with('items.logistik', 'lokasi', 'gudang')
            ->latest('diverifikasi_pada')
            ->paginate(15);

        return view('pages.donasi.laporan', compact('ringkas', 'barang', 'perPosko', 'donasis'));
    }

    /* ---------------------------------------------------------------- */

    /** Cek soal hitungan & lama pengisian form. Soal hanya berlaku sekali. */
    private function cekManusia(Request $request): void
    {
        $jawaban = $request->session()->pull(self::SESI_JAWABAN);

        try {
            $dibuka = (int) decrypt((string) $request->input('dibuka_pada'));
        } catch (\Throwable) {
            $dibuka = 0;
        }

        $lama = now()->timestamp - $dibuka;

        if ($jawaban === null || $dibuka === 0 || $lama > self::JAM_KEDALUWARSA * 3600) {
            throw ValidationException::withMessages(['jawaban' => 'Form sudah kedaluwarsa. Silakan isi ulang.']);
        }

        if ($lama < self::DETIK_MINIMAL) {
            throw ValidationException::withMessages(['jawaban' => 'Form dikirim terlalu cepat. Silakan coba lagi.']);
        }

        if (trim((string) $request->input('jawaban')) !== (string) $jawaban) {
            throw ValidationException::withMessages(['jawaban' => 'Jawaban hitungan salah. Silakan coba lagi.']);
        }
    }

    /** "0812-3456 7890" -> "081234567890"; email -> huruf kecil. */
    public static function rapikanKontak(string $kontak): string
    {
        $kontak = trim($kontak);

        return str_contains($kontak, '@')
            ? mb_strtolower($kontak)
            : preg_replace('/[\s\-\.()]/', '', $kontak);
    }

    /** Posko yang bisa dipilih donatur: tampil di publik & sedang beroperasi. */
    private function poskoTujuan()
    {
        return Lokasi::tampilPublik()
            ->where('status_operasional', Lokasi::OPS_BEROPERASI)
            ->with('desa')
            ->orderBy('nama_lokasi')
            ->get();
    }

    private function ringkasan(): array
    {
        $diterima = Donasi::terverifikasi();

        return [
            'dana_diterima'    => (int) (clone $diterima)->where('jenis', Donasi::UANG)->sum('nominal_diterima'),
            'dana_disalurkan'  => (int) PenyaluranDonasi::sum('nominal'),
            'jumlah_donasi'    => (clone $diterima)->count(),
            'jumlah_donatur'   => (clone $diterima)->distinct()->count('kontak'),
            'donasi_barang'    => (clone $diterima)->where('jenis', Donasi::LOGISTIK)->count(),
            'posko_terbantu'   => PenyaluranDonasi::whereNotNull('lokasi_id')->distinct()->count('lokasi_id'),
        ];
    }
}
