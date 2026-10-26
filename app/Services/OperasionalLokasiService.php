<?php

namespace App\Services;

use App\Models\Lokasi;
use Carbon\Carbon;
use Illuminate\Support\Collection;
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

    // Posko dianggap perlu dipantau jika keterisiannya >= angka ini (%)
    public const BATAS_PENUH_PERSEN = 90;

    // Kebutuhan khusus per kelompok rentan (ditampilkan untuk donatur)
    public const KEBUTUHAN_RENTAN = [
        'Bayi'        => 'susu formula / MPASI, popok, selimut bayi',
        'Ibu Hamil'   => 'makanan bergizi, vitamin, pemeriksaan kehamilan',
        'Lansia'      => 'obat-obatan rutin, alas tidur yang layak, makanan lunak',
        'Disabilitas' => 'alat bantu (kursi roda, tongkat), akses toilet yang mudah',
        'Sakit'       => 'obat-obatan, tenaga kesehatan',
    ];

    /**
     * Daya tahan stok dalam bahasa awam, TANPA desimal:
     *   0        -> "Sudah habis"
     *   < 1 hari -> "Habis hari ini"
     *   >= 1     -> "2 hari" (dibulatkan ke bawah agar tidak terlihat lebih aman)
     * $kalimat = true -> "Habis dalam 2 hari" (untuk kalimat keterangan)
     * Status kritis/menipis tetap dihitung dari angka asli.
     */
    public static function teksHari(?float $hari, bool $kalimat = false): string
    {
        return match (true) {
            $hari === null => '-',
            $hari <= 0     => 'Sudah habis',
            $hari < 1      => 'Habis hari ini',
            default        => ($kalimat ? 'Habis dalam ' : '') . (int) floor($hari) . ' hari',
        };
    }

    /** Kekurangan stok dihitung agar cukup untuk sekian hari ke depan (.env TARGET_HARI_BANTUAN). */
    public static function targetHari(): int
    {
        return max(1, (int) config('logeva.target_hari_bantuan', 7));
    }

    public function ringkasan(Lokasi $lokasi): array
    {
        $pengungsi = $this->ringkasanPengungsi($lokasi);
        $stok      = $this->stokLogistik($lokasi, $pengungsi['total']);
        $bantuan   = $this->susunStatusBantuan($lokasi, $stok, $pengungsi);
        $medis     = $this->perhatianMedis($lokasi);
        $keluar    = $this->stokKeluarTerbaru($lokasi);

        return [
            'pengungsi'   => $pengungsi,
            'stok'        => $stok,
            'stok_status' => $this->hitungStatus($stok),
            'sphere'      => $this->kebutuhanSphere($pengungsi['total']),
            'stok_masuk_terbaru'  => $this->stokMasukTerbaru($lokasi),
            'stok_keluar_terbaru' => $keluar,
            'perhatian_medis'     => $medis,
            'tugas'       => $this->tugasHariIni($stok, $pengungsi, $medis, $keluar),
            'hari_ke'     => $lokasi->hariOperasi(),
            'bantuan'     => $bantuan,
        ];
    }

    /**
     * Daftar tugas relawan untuk hari ini, disusun dari data yang sudah ada.
     * prioritas: tinggi / sedang / rendah. aksi: tujuan tombol (dipetakan ke route di view).
     */
    private function tugasHariIni(Collection $stok, array $pengungsi, Collection $medis, Collection $keluar): Collection
    {
        $tugas = collect();

        if ($pengungsi['total'] === 0) {
            $tugas->push(['prioritas' => 'sedang', 'ikon' => 'fa-user-plus', 'aksi' => 'pengungsi',
                'teks' => 'Catat data pengungsi di posko ini',
                'ket'  => 'Kebutuhan logistik dihitung dari jumlah pengungsi, jadi belum bisa dihitung.']);
        }

        // Barang yang hampir / sudah habis
        foreach ($stok->where('mendesak', true)->sortBy('cukup_hari') as $s) {
            $tugas->push(['prioritas' => $s->status === 'merah' ? 'tinggi' : 'sedang', 'ikon' => 'fa-box-open', 'aksi' => 'logistik',
                'teks' => "Minta kiriman ±{$s->kekurangan} {$s->satuan} {$s->nama_item}",
                'ket'  => $s->belum_pernah_masuk ? 'Barang ini belum pernah ada di posko.' : self::teksHari($s->cukup_hari, true) . '.']);
        }

        $sakit = $medis->where('sakit', true)->count();
        if ($sakit > 0) {
            $tugas->push(['prioritas' => 'tinggi', 'ikon' => 'fa-first-aid', 'aksi' => 'medis',
                'teks' => "Periksa kondisi {$sakit} pengungsi yang sedang sakit",
                'ket'  => 'Pastikan sudah ditangani tenaga kesehatan dan obatnya tersedia.']);
        }

        if ($pengungsi['persen_isi'] >= self::BATAS_PENUH_PERSEN) {
            $tugas->push(['prioritas' => 'tinggi', 'ikon' => 'fa-house-user', 'aksi' => null,
                'teks' => "Kapasitas terisi {$pengungsi['persen_isi']}%, koordinasikan posko cadangan",
                'ket'  => "Sisa tempat {$pengungsi['sisa_tempat']} orang."]);
        }

        $rentanKhusus = collect($pengungsi['rentan'])->except('Sakit')->filter();
        if ($rentanKhusus->isNotEmpty()) {
            $tugas->push(['prioritas' => 'sedang', 'ikon' => 'fa-blind', 'aksi' => 'medis',
                'teks' => 'Cek kebutuhan khusus ' . $rentanKhusus->sum() . ' pengungsi rentan',
                'ket'  => $rentanKhusus->map(fn ($j, $k) => "{$k} {$j}")->join(', ') . '.']);
        }

        if ($pengungsi['total'] > 0 && ! $keluar->contains(fn ($k) => Carbon::parse($k->tanggal_keluar)->isToday())) {
            $tugas->push(['prioritas' => 'rendah', 'ikon' => 'fa-dolly', 'aksi' => 'keluar',
                'teks' => 'Catat barang yang dibagikan / dimasak hari ini',
                'ket'  => 'Belum ada catatan barang keluar hari ini, sisa stok bisa jadi tidak akurat.']);
        }

        $tanpaKebutuhan = $stok->whereNull('kebutuhan_harian');
        if ($tanpaKebutuhan->isNotEmpty()) {
            $tugas->push(['prioritas' => 'rendah', 'ikon' => 'fa-sliders-h', 'aksi' => 'master',
                'teks' => 'Atur kebutuhan harian ' . $tanpaKebutuhan->count() . ' barang di Master Logistik',
                'ket'  => $tanpaKebutuhan->pluck('nama_item')->take(4)->join(', ') . '.']);
        }

        $urutan = ['tinggi' => 0, 'sedang' => 1, 'rendah' => 2];

        return $tugas->sortBy(fn ($t) => $urutan[$t['prioritas']])->values();
    }

    /**
     * Pengungsi yang perlu perhatian medis: sedang sakit (kondisi kesehatan selain "Sehat"),
     * punya riwayat penyakit, atau termasuk kelompok rentan. Yang sakit di urutan atas.
     */
    private function perhatianMedis(Lokasi $lokasi): Collection
    {
        $sehat = "LOWER(TRIM(COALESCE(kondisi_kesehatan, ''))) IN ('', '-', 'sehat', 'baik')";

        return DB::table('pengungsis')
            ->where('lokasi_id', $lokasi->id)
            ->where(fn ($q) => $q->whereRaw("NOT ({$sehat})")
                ->orWhere(fn ($q) => $q->whereNotNull('riwayat_penyakit')->where('riwayat_penyakit', '!=', ''))
                ->orWhere('kelompok_rentan', '!=', 'Tidak'))
            ->select('id', 'nama', 'usia', 'jenis_kelamin', 'kondisi_kesehatan', 'kelompok_rentan', 'riwayat_penyakit',
                DB::raw("CASE WHEN {$sehat} THEN 0 ELSE 1 END as sakit"))
            ->orderByDesc('sakit')
            ->orderByDesc('usia')
            ->get()
            ->map(function ($p) {
                $p->sakit = (bool) $p->sakit;

                return $p;
            });
    }

    private function stokKeluarTerbaru(Lokasi $lokasi): Collection
    {
        return DB::table('stok_keluars as k')
            ->join('logistiks as l', 'l.id', '=', 'k.logistik_id')
            ->leftJoin('users as u', 'u.id', '=', 'k.user_id')
            ->where('k.lokasi_id', $lokasi->id)
            ->select('l.nama_item', 'l.satuan', 'k.jumlah_keluar', 'k.tujuan', 'k.penerima', 'k.tanggal_keluar', 'u.name as petugas')
            ->orderByDesc('k.tanggal_keluar')
            ->orderByDesc('k.id')
            ->limit(5)
            ->get();
    }

    /**
     * Status bantuan SATU posko (hanya untuk posko yang beroperasi):
     *   segera = ada barang habis < 1 hari (termasuk barang pokok yang stoknya 0)
     *   pantau = ada barang habis < 3 hari, atau posko hampir penuh
     *   cukup  = semua barang cukup >= 3 hari (atau belum ada pengungsi)
     *   belum  = belum bisa dihitung (kebutuhan harian belum diatur di Master Logistik)
     *   null   = posko tidak beroperasi
     */
    public function statusBantuan(Lokasi $lokasi): array
    {
        $pengungsi = $this->ringkasanPengungsi($lokasi);

        return $this->susunStatusBantuan($lokasi, $this->stokLogistik($lokasi, $pengungsi['total']), $pengungsi);
    }

    /**
     * Semua posko publik yang sedang beroperasi beserta status bantuannya,
     * urut dari yang paling mendesak. Dipakai Monitoring Lokasi, dashboard admin & peta.
     */
    public function semuaStatusBantuan(): Collection
    {
        $urutan = ['segera' => 0, 'pantau' => 1, 'belum' => 2, 'cukup' => 3];

        return Lokasi::tampilPublik()
            ->where('status_operasional', Lokasi::OPS_BEROPERASI)
            ->with('desa')
            ->get()
            ->map(fn ($l) => ['lokasi' => $l] + $this->statusBantuan($l))
            ->sortBy(fn ($b) => sprintf('%d-%08.1f', $urutan[$b['tingkat']] ?? 9, $b['kebutuhan']->min('cukup_hari') ?? 999999))
            ->values();
    }

    /**
     * Stok logistik posko + status ketahanannya, dengan rumus yang sama seperti panel detail posko.
     * Dipakai halaman Monitoring Logistik agar status di semua halaman selaras.
     */
    public function stokPosko(Lokasi $lokasi): array
    {
        $jumlahPengungsi = DB::table('pengungsis')->where('lokasi_id', $lokasi->id)->count();
        $stok            = $this->stokLogistik($lokasi, $jumlahPengungsi);

        return [
            'stok'             => $stok,
            'stok_status'      => $this->hitungStatus($stok),
            'jumlah_pengungsi' => $jumlahPengungsi,
        ];
    }

    /* ---------------------------------------------------------------- */

    private function susunStatusBantuan(Lokasi $lokasi, Collection $stok, array $pengungsi): array
    {
        $kebutuhan = $stok
            ->filter(fn ($s) => $s->perlu_dikirim)
            ->sortBy('cukup_hari')
            ->values();

        $alasan = [];
        foreach ($stok->whereIn('status', ['merah', 'kuning']) as $s) {
            $alasan[] = $s->belum_pernah_masuk
                ? "{$s->nama_item} belum ada stok"
                : "{$s->nama_item}: " . lcfirst(self::teksHari($s->cukup_hari, true));
        }
        if ($pengungsi['persen_isi'] >= self::BATAS_PENUH_PERSEN) {
            $alasan[] = "Kapasitas terisi {$pengungsi['persen_isi']}%";
        }

        $tingkat = match (true) {
            ! $lokasi->isBeroperasi()                      => null,
            $pengungsi['total'] === 0                      => 'cukup',
            $stok->contains('status', 'merah')             => 'segera',
            $stok->contains('status', 'kuning')
                || $pengungsi['persen_isi'] >= self::BATAS_PENUH_PERSEN => 'pantau',
            $stok->isEmpty() || $stok->every(fn ($s) => $s->status === 'netral') => 'belum',
            default                                        => 'cukup',
        };

        // Kebutuhan khusus kelompok rentan yang benar-benar ada di posko
        $rentan = collect($pengungsi['rentan'])
            ->filter(fn ($jumlah, $kelompok) => isset(self::KEBUTUHAN_RENTAN[$kelompok]))
            ->map(fn ($jumlah, $kelompok) => ['jumlah' => $jumlah, 'kebutuhan' => self::KEBUTUHAN_RENTAN[$kelompok]]);

        return [
            'tingkat'          => $tingkat,
            'alasan'           => $alasan,
            'kebutuhan'        => $kebutuhan,
            'stok'             => $stok,
            'rentan'           => $rentan,
            'jumlah_pengungsi' => $pengungsi['total'],
            'persen_isi'       => $pengungsi['persen_isi'],
            'target_hari'      => self::targetHari(),
            'diperbarui'       => ($t = $stok->max('updated_at')) ? Carbon::parse($t) : null,
        ];
    }

    private function hitungStatus($stok): array
    {
        return [
            'merah'  => $stok->where('status', 'merah')->count(),
            'kuning' => $stok->where('status', 'kuning')->count(),
            'hijau'  => $stok->where('status', 'hijau')->count(),
            'netral' => $stok->where('status', 'netral')->count(),
        ];
    }

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

    /**
     * Stok setiap barang di posko. Barang POKOK (punya kebutuhan harian di Master Logistik)
     * selalu ikut dihitung walaupun belum pernah masuk ke posko (stok dianggap 0),
     * agar posko yang sama sekali belum punya barang itu terdeteksi kekurangan.
     */
    private function stokLogistik(Lokasi $lokasi, int $jumlahPengungsi)
    {
        $target = self::targetHari();

        return DB::table('logistiks as l')
            ->leftJoin('stok_logistiks as s', function ($join) use ($lokasi) {
                $join->on('s.logistik_id', '=', 'l.id')->where('s.lokasi_id', '=', $lokasi->id);
            })
            ->leftJoin('kategori_logistiks as k', 'k.id', '=', 'l.kategori_logistik_id')
            ->where(fn ($q) => $q->whereNotNull('s.id')->orWhere('l.kebutuhan_harian', '>', 0))
            ->select(
                'l.id as logistik_id',
                'l.nama_item',
                'l.satuan',
                'l.kebutuhan_harian',
                'k.nama_kategori',
                DB::raw('COALESCE(s.jumlah_stok, 0) as jumlah_stok'),
                DB::raw('s.id IS NULL as belum_pernah_masuk'),
                's.updated_at'
            )
            ->orderBy('k.nama_kategori')
            ->orderBy('l.nama_item')
            ->get()
            ->map(function ($row) use ($jumlahPengungsi, $target) {
                $row->jumlah_stok        = (int) $row->jumlah_stok;
                $row->belum_pernah_masuk = (bool) $row->belum_pernah_masuk;

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

                // Jumlah yang perlu dikirim agar stok cukup untuk $target hari ke depan
                $kekurangan = $perHari > 0 ? max(0, (int) ceil($perHari * $target - $row->jumlah_stok)) : null;

                $row->kebutuhan_hari_ini = round($perHari, 1);
                $row->cukup_hari         = $cukupHari;
                $row->status             = $status;
                $row->kekurangan         = $kekurangan;
                $row->perlu_dikirim      = $kekurangan > 0;
                // mendesak = merah/kuning; tidak mendesak = aman tapi < target hari
                $row->mendesak           = in_array($status, ['merah', 'kuning'], true);

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