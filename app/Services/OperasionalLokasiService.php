<?php

namespace App\Services;

use App\Models\Lokasi;
use Illuminate\Support\Facades\DB;

/**
 * Menyiapkan data dashboard untuk posko yang sedang BEROPERASI:
 * jumlah pengungsi nyata, stok logistik saat ini, dan kebutuhan SPHERE
 * yang dihitung dari jumlah pengungsi (bukan dari kapasitas).
 */
class OperasionalLokasiService
{
    /**
     * true  = kolom logistiks.kebutuhan_harian berarti kebutuhan PER ORANG per hari
     *         (contoh: beras 0.4 kg/orang/hari), dikalikan jumlah pengungsi.
     * false = kebutuhan_harian berarti kebutuhan SATU POSKO per hari (tidak dikalikan).
     */
    public const KEBUTUHAN_PER_ORANG = true;

    // Standar minimum SPHERE (per orang per hari, kecuali toilet)
    public const SPHERE_AIR_LITER   = 15;    // total air minum + masak + kebersihan
    public const SPHERE_AIR_MINUM   = 3;     // bagian air minum
    public const SPHERE_KALORI      = 2100;  // kkal
    public const SPHERE_ORANG_PER_TOILET = 20;

    // Batas status stok (dalam hari)
    public const BATAS_MERAH  = 1;  // < 1 hari  -> kritis
    public const BATAS_KUNING = 3;  // < 3 hari  -> menipis

    public function ringkasan(Lokasi $lokasi): array
    {
        $pengungsi = $this->ringkasanPengungsi($lokasi);
        $stok      = $this->stokLogistik($lokasi, $pengungsi['total']);

        return [
            'pengungsi'   => $pengungsi,
            'stok'        => $stok,
            'stok_status' => [
                'merah'  => $stok->where('status', 'merah')->count(),
                'kuning' => $stok->where('status', 'kuning')->count(),
                'hijau'  => $stok->where('status', 'hijau')->count(),
            ],
            'sphere'      => $this->kebutuhanSphere($pengungsi['total']),
            'stok_masuk_terbaru' => $this->stokMasukTerbaru($lokasi),
            'hari_ke'     => $lokasi->hariOperasi(),
        ];
    }

    /* ---------------------------------------------------------------- */

    private function ringkasanPengungsi(Lokasi $lokasi): array
    {
        $q = fn () => DB::table('pengungsis')->where('lokasi_id', $lokasi->id);

        $total     = $q()->count();
        $kapasitas = (int) $lokasi->kapasitas_pengungsi;

        $rentan = $q()
            ->where('kelompok_rentan', '!=', 'Tidak')
            ->select('kelompok_rentan', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('kelompok_rentan')
            ->pluck('jumlah', 'kelompok_rentan');

        return [
            'total'       => $total,
            'kapasitas'   => $kapasitas,
            'persen_isi'  => $kapasitas > 0 ? min(100, round($total / $kapasitas * 100)) : 0,
            'sisa_tempat' => max(0, $kapasitas - $total),
            'laki_laki'   => $q()->where('jenis_kelamin', 'Laki-laki')->count(),
            'perempuan'   => $q()->where('jenis_kelamin', 'Perempuan')->count(),
            'usia' => [
                'Balita (0-4)'  => $q()->where('usia', '<', 5)->count(),
                'Anak (5-12)'   => $q()->whereBetween('usia', [5, 12])->count(),
                'Remaja (13-17)'=> $q()->whereBetween('usia', [13, 17])->count(),
                'Dewasa (18-59)'=> $q()->whereBetween('usia', [18, 59])->count(),
                'Lansia (60+)'  => $q()->where('usia', '>=', 60)->count(),
            ],
            'rentan'       => $rentan,
            'total_rentan' => $rentan->sum(),
        ];
    }

    private function stokLogistik(Lokasi $lokasi, int $jumlahPengungsi)
    {
        return DB::table('stok_logistiks as s')
            ->join('logistiks as l', 'l.id', '=', 's.logistik_id')
            ->leftJoin('kategori_logistiks as k', 'k.id', '=', 'l.kategori_logistik_id')
            ->where('s.lokasi_id', $lokasi->id)
            ->select(
                'l.nama_item',
                'l.satuan',
                'l.kebutuhan_harian',
                'k.nama_kategori',
                's.jumlah_stok',
                's.updated_at'
            )
            ->orderBy('k.nama_kategori')
            ->orderBy('l.nama_item')
            ->get()
            ->map(function ($row) use ($jumlahPengungsi) {
                $perHari = (float) ($row->kebutuhan_harian ?? 0);
                if (self::KEBUTUHAN_PER_ORANG) {
                    $perHari *= $jumlahPengungsi;
                }

                $cukupHari = $perHari > 0 ? round($row->jumlah_stok / $perHari, 1) : null;

                $status = match (true) {
                    $cukupHari === null               => 'netral',
                    $cukupHari < self::BATAS_MERAH    => 'merah',
                    $cukupHari < self::BATAS_KUNING   => 'kuning',
                    default                           => 'hijau',
                };

                $row->kebutuhan_hari_ini = round($perHari, 1);
                $row->cukup_hari         = $cukupHari;
                $row->status             = $status;

                return $row;
            });
    }

    private function kebutuhanSphere(int $jumlahPengungsi): array
    {
        return [
            'air_total' => $jumlahPengungsi * self::SPHERE_AIR_LITER,
            'air_minum' => $jumlahPengungsi * self::SPHERE_AIR_MINUM,
            'kalori'    => $jumlahPengungsi * self::SPHERE_KALORI,
            'toilet'    => (int) ceil($jumlahPengungsi / self::SPHERE_ORANG_PER_TOILET),
        ];
    }

    private function stokMasukTerbaru(Lokasi $lokasi)
    {
        return DB::table('stok_masuks as m')
            ->join('logistiks as l', 'l.id', '=', 'm.logistik_id')
            ->where('m.lokasi_id', $lokasi->id)
            ->select('l.nama_item', 'l.satuan', 'm.jumlah_masuk', 'm.sumber_bantuan', 'm.tanggal_masuk')
            ->orderByDesc('m.tanggal_masuk')
            ->orderByDesc('m.id')
            ->limit(5)
            ->get();
    }
}