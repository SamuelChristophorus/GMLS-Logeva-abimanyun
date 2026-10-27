<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Interfaces\LokasiRepositoryInterface;
use App\Models\Lokasi;
use App\Services\OperasionalLokasiService;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(private LokasiRepositoryInterface $lokasiRepository)
    {
    }

    public function index()
    {
        $persetujuan = $this->lokasiRepository->countLokasiPerStatus();

        $operasional = [
            'beroperasi' => Lokasi::where('status_operasional', Lokasi::OPS_BEROPERASI)->count(),
            'siaga'      => Lokasi::where(fn ($q) => $q->where('status_operasional', Lokasi::OPS_SIAGA)->orWhereNull('status_operasional'))->count(),
            'selesai'    => Lokasi::where('status_operasional', Lokasi::OPS_SELESAI)->count(),
        ];

        // Keterisian posko yang sedang beroperasi (pengungsi nyata vs kapasitas)
        $keterisian = Lokasi::where('status_operasional', Lokasi::OPS_BEROPERASI)
            ->withCount('pengungsi')
            ->get(['id', 'nama_lokasi', 'kapasitas_pengungsi'])
            ->map(fn ($l) => [
                'nama'      => $l->nama_lokasi,
                'pengungsi' => $l->pengungsi_count,
                'kapasitas' => (int) $l->kapasitas_pengungsi,
                'persen'    => $l->kapasitas_pengungsi > 0
                    ? round($l->pengungsi_count / $l->kapasitas_pengungsi * 100, 1)
                    : 0,
            ])
            ->sortByDesc('persen')
            ->take(10)
            ->values();

        // Laporan posko masuk per bulan (6 bulan terakhir)
        $awal = now()->startOfMonth()->subMonths(5);
        $perBulan = Lokasi::where('created_at', '>=', $awal)
            ->get(['created_at'])
            ->countBy(fn ($l) => $l->created_at->format('Y-m'));
        $trenLaporan = collect(range(0, 5))->map(function ($i) use ($awal, $perBulan) {
            $bulan = $awal->copy()->addMonths($i);
            return [
                'label'  => $bulan->translatedFormat('M Y'),
                'jumlah' => $perBulan[$bulan->format('Y-m')] ?? 0,
            ];
        });

        // Komposisi pengungsi seluruh posko per kelompok usia
        $p = fn () => \App\Models\Pengungsi::tabelDiPosko(); // tanpa yang sudah keluar posko
        $usia = [
            'Balita (0-4)'   => $p()->where('usia', '<', 5)->count(),
            'Anak (5-12)'    => $p()->whereBetween('usia', [5, 12])->count(),
            'Remaja (13-17)' => $p()->whereBetween('usia', [13, 17])->count(),
            'Dewasa (18-59)' => $p()->whereBetween('usia', [18, 59])->count(),
            'Lansia (60+)'   => $p()->where('usia', '>=', 60)->count(),
        ];

        $totalPengungsi = $p()->count();
        $kapasitasAktif = (int) Lokasi::where('status_operasional', Lokasi::OPS_BEROPERASI)->sum('kapasitas_pengungsi');
        $pengungsiAktif = $p()->whereIn('lokasi_id', Lokasi::where('status_operasional', Lokasi::OPS_BEROPERASI)->select('id'))->count();

        $ringkasan = [
            'total_pengungsi' => $totalPengungsi,
            'laki_laki'       => $p()->where('jenis_kelamin', 'Laki-laki')->count(),
            'perempuan'       => $p()->where('jenis_kelamin', 'Perempuan')->count(),
            'rentan'          => $p()->whereNotNull('kelompok_rentan')->where('kelompok_rentan', '!=', 'Tidak')->count(),
            'persen_terisi'   => $kapasitasAktif > 0 ? round($pengungsiAktif / $kapasitasAktif * 100) : 0,
            'kapasitas_aktif' => $kapasitasAktif,
            'pengungsi_aktif' => $pengungsiAktif,
        ];

        // Antrean laporan yang menunggu persetujuan
        $laporanPending = $this->lokasiRepository->getLokasiByStatus('pending')->take(5);

        // Posko beroperasi yang butuh bantuan (panel + penanda di peta)
        $statusBantuan = app(OperasionalLokasiService::class)->semuaStatusBantuan();
        $bantuanPer    = $statusBantuan->keyBy(fn ($b) => $b['lokasi']->id);

        // Titik di peta admin (semua lokasi, termasuk yang ditolak)
        $petaAdmin = Lokasi::with(['latestStatusView', 'desa', 'permintaanMenunggu'])
            ->withCount('pengungsi')
            ->get()
            ->map(fn ($l) => [
                'bantuan'     => $bantuanPer->get($l->id)['tingkat'] ?? null,
                'butuh'       => ($b = $bantuanPer->get($l->id))
                    ? $b['kebutuhan']->filter->mendesak->take(3)->map(fn ($k) => ['nama' => $k->nama_item, 'jumlah' => $k->kekurangan, 'satuan' => $k->satuan])->values()
                    : [],
                'id'          => $l->id,
                'nama'        => $l->nama_lokasi,
                'alamat'      => $l->alamat_lokasi,
                'desa'        => $l->desa->nama_desa ?? null,
                'latitude'    => $l->latitude,
                'longitude'   => $l->longitude,
                'kapasitas'   => (int) $l->kapasitas_pengungsi,
                'luas'        => (float) $l->luas_lokasi,
                'pengungsi'   => $l->pengungsi_count,
                'status'      => optional($l->latestStatusView)->status ?? 'pending',
                'operasional' => $l->status_operasional ?? Lokasi::OPS_SIAGA,
                'hari_ke'     => $l->hariOperasi(),
                'permintaan'  => $l->permintaanMenunggu?->labelAksi(),
            ])
            ->values();

        return view('pages.admin.dashboard', compact(
            'persetujuan', 'operasional', 'keterisian', 'trenLaporan', 'usia', 'ringkasan', 'laporanPending', 'petaAdmin', 'statusBantuan'
        ));
    }
}
