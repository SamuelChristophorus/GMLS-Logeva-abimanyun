<?php

namespace App\Services;

use App\Imports\Support\NilaiImport as N;
use App\Models\Desa;
use App\Models\KategoriLogistik;
use App\Models\Logistik;
use App\Models\Lokasi;
use App\Models\Pengungsi;
use App\Models\PetugasPosko;
use App\Models\RiwayatImportPaket;
use App\Models\SphereLokasi;
use App\Models\StatusLokasi;
use App\Models\StokKeluar;
use App\Models\StokLogistik;
use App\Models\StokMasuk;
use App\Support\BerkasTerkunci;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use ZipArchive;

/**
 * IMPORT PAKET DATA POSKO dari aplikasi offline Logeva Client
 * (format: docs/SKEMA-DATABASE-UNTUK-APLIKASI.md §8).
 *
 * Alur: bacaZip() -> periksa() untuk pratinjau -> simpan() setelah dikonfirmasi.
 * simpan() memeriksa ulang data terhadap isi database saat itu, jadi aman walau
 * ada import lain di antara pratinjau dan konfirmasi.
 *
 * Aturan gabung (paket yang sama boleh diimpor berkali-kali, paket lama boleh
 * datang setelah paket baru, tanpa data dobel):
 *  - Posko     : dicocokkan dengan kode, lalu nama posko + desa; tidak ketemu = posko baru
 *                (admin memilih relawan penanggung jawab). Data posko diperbarui hanya
 *                jika versi paket lebih baru.
 *  - Pengungsi : kode baru = tambah (atau disambung ke orang yang sama: nama + tanggal lahir
 *                di posko yang sama); kode lama = perbarui hanya jika versi paket lebih baru;
 *                dihapus_pada terisi = dihapus di pusat. Kode yang pernah dihapus tidak
 *                pernah ditambahkan lagi.
 *  - Barang masuk/keluar : kode yang sudah pernah diimpor dilewati. Stok pusat diubah
 *                sebesar catatan yang BARU diimpor (bukan ditimpa angka stok HP).
 *  - stok.csv  : hanya untuk peringatan jika stok pusat berbeda dengan stok HP.
 *  - petugas.csv (opsional): nama & kontak admin lokal (penanggung jawab semua posko di
 *                paket) dan relawan lokal per posko. Disimpan di petugas_poskos, diganti utuh
 *                per (posko, perangkat) hanya jika paketnya lebih baru. Bukan akun website.
 */
class PaketDataPoskoService
{
    public const VERSI_FORMAT = '1';

    /** Judul kolom wajib per file (persis §8). */
    public const KOLOM = [
        'info.csv'          => ['versi_format', 'dibuat_pada', 'kode_perangkat', 'diekspor_oleh'],
        'posko.csv'         => ['kode_posko', 'nama_lokasi', 'nama_desa', 'alamat_lokasi', 'luas_lokasi',
                                'kapasitas_pengungsi', 'latitude', 'longitude', 'status_operasional',
                                'mulai_beroperasi_at', 'selesai_beroperasi_at', 'relawan_penanggung_jawab',
                                'diubah_pada'],
        'pengungsi.csv'     => ['kode', 'kode_posko', 'nama', 'asal', 'tanggal_lahir', 'nomor_kk', 'usia',
                                'jenis_kelamin', 'kondisi_kesehatan', 'kelompok_rentan', 'riwayat_penyakit',
                                'dicatat_oleh', 'dibuat_pada', 'diubah_pada', 'dihapus_pada'],
        'barang_masuk.csv'  => ['kode', 'kode_posko', 'nama_item', 'kategori', 'satuan', 'jumlah_masuk',
                                'sumber_bantuan', 'tanggal_masuk', 'keterangan', 'dicatat_oleh', 'dibuat_pada'],
        'barang_keluar.csv' => ['kode', 'kode_posko', 'nama_item', 'kategori', 'satuan', 'jumlah_keluar',
                                'tujuan', 'penerima', 'tanggal_keluar', 'keterangan', 'dicatat_oleh', 'dibuat_pada'],
        'stok.csv'          => ['kode_posko', 'nama_item', 'satuan', 'jumlah_stok'],
    ];

    /** File yang boleh tidak ada (paket dari versi aplikasi sebelumnya). */
    public const KOLOM_OPSIONAL = [
        'petugas.csv' => ['kode_posko', 'peran', 'nama', 'kontak'],
    ];

    public const MAKS_UKURAN_BYTE = 10 * 1024 * 1024;

    private const UUID = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';
    private const LAT_MIN = -11.5, LAT_MAX = 6.5, LNG_MIN = 94.5, LNG_MAX = 141.5;
    private const M2_PER_ORANG = 3.5;
    private const JENIS_KELAMIN = ['Laki-laki', 'Perempuan'];
    private const KELOMPOK_RENTAN = ['Tidak', 'Ibu Hamil', 'Lansia', 'Bayi', 'Disabilitas', 'Sakit'];
    private const STATUS_OPS = [Lokasi::OPS_SIAGA, Lokasi::OPS_BEROPERASI, Lokasi::OPS_SELESAI];
    private const GAMBAR_DEFAULT = 'assets/default/lokasi.png';

    /* =========================================================
     |  BACA FILE
     ========================================================= */

    /**
     * Isi ZIP -> ['info.csv' => 'teks', ...].
     *
     * @throws DomainException jika file bukan paket yang benar
     */
    /** Kata sandi posko yang membuka paket terakhir (null = paket tidak terkunci). */
    public ?string $kataSandiTerpakai = null;

    /**
     * @param  string[]  $kataSandi  kata sandi posko yang dicoba (paket terkunci)
     * @throws \App\Exceptions\KataSandiPaketException paket terkunci & kata sandi salah/kosong
     */
    public function bacaZip(string $path, array $kataSandi = []): array
    {
        $this->kataSandiTerpakai = null;
        if (BerkasTerkunci::terkunci($path)) {
            [$isiZip, $this->kataSandiTerpakai] = BerkasTerkunci::buka($path, $kataSandi);
            $sementara = tempnam(sys_get_temp_dir(), 'paket');
            file_put_contents($sementara, $isiZip);
            try {
                return $this->bacaZipBiasa($sementara);
            } finally {
                @unlink($sementara);
            }
        }
        return $this->bacaZipBiasa($path);
    }

    private function bacaZipBiasa(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new DomainException('File bukan ZIP yang valid. Pilih file paket_....zip dari aplikasi.');
        }

        $isi = [];
        $total = 0;
        foreach (array_keys(self::KOLOM + self::KOLOM_OPSIONAL) as $nama) {
            $stat = $zip->statName($nama);
            if ($stat === false && isset(self::KOLOM_OPSIONAL[$nama])) {
                continue;
            }
            if ($stat === false) {
                $zip->close();
                throw new DomainException("File {$nama} tidak ada di dalam ZIP. Pastikan file berasal dari menu Ekspor Paket Data Posko di aplikasi.");
            }
            // Ukuran dicek SEBELUM diekstrak (mencegah file ZIP yang sengaja dibuat sangat besar).
            $total += $stat['size'];
            if ($total > self::MAKS_UKURAN_BYTE) {
                $zip->close();
                throw new DomainException('Isi paket terlalu besar (maks. 10 MB setelah diekstrak).');
            }
            $isi[$nama] = (string) $zip->getFromName($nama);
        }
        $zip->close();

        // Pastikan judul kolom & versi format sesuai sebelum diperiksa lebih jauh.
        foreach (self::KOLOM + self::KOLOM_OPSIONAL as $nama => $kolom) {
            if (! isset($isi[$nama])) {
                continue;
            }
            $kurang = array_diff($kolom, $this->judul($isi[$nama]));
            if ($kurang) {
                throw new DomainException("Kolom " . implode(', ', $kurang) . " tidak ada di {$nama}.");
            }
        }
        $info = $this->csv($isi['info.csv'])[0] ?? [];
        if (($info['versi_format'] ?? '') !== self::VERSI_FORMAT) {
            throw new DomainException('Versi format paket "' . ($info['versi_format'] ?? '-') . '" tidak dikenali. Perbarui aplikasi atau website.');
        }

        return $isi;
    }

    /** Baris judul CSV (huruf kecil, tanpa BOM). */
    private function judul(string $teks): array
    {
        $teks = preg_replace('/^\xEF\xBB\xBF/', '', $teks);
        $baris = strtok($teks, "\r\n");
        return array_map(fn ($k) => strtolower(trim($k)), str_getcsv((string) $baris, ',', '"', ''));
    }

    /** Teks CSV -> daftar baris ['kolom' => nilai, '_row' => nomor baris di file]. */
    public function csv(string $teks): array
    {
        $teks = preg_replace('/^\xEF\xBB\xBF/', '', $teks);
        $f = fopen('php://memory', 'r+');
        fwrite($f, $teks);
        rewind($f);

        $judul = null;
        $rows = [];
        $nomor = 0;
        while (($data = fgetcsv($f, 0, ',', '"', '')) !== false) {
            $nomor++;
            if ($judul === null) {
                $judul = array_map(fn ($k) => strtolower(trim((string) $k)), $data);
                continue;
            }
            if ($data === [null] || implode('', $data) === '') {
                continue; // baris kosong
            }
            $row = ['_row' => $nomor];
            foreach ($judul as $i => $k) {
                $row[$k] = trim((string) ($data[$i] ?? ''));
            }
            $rows[] = $row;
        }
        fclose($f);

        return $rows;
    }

    /* =========================================================
     |  PERIKSA (PRATINJAU)
     ========================================================= */

    /**
     * Memeriksa seluruh paket tanpa menyimpan apa pun.
     * Setiap baris mendapat 'status' (tambah / perbarui / hapus / sama / lewati / error),
     * 'alasan' (teks untuk pratinjau), dan 'error' (daftar kesalahan).
     */
    public function periksa(array $isi): array
    {
        $info = $this->csv($isi['info.csv'])[0];
        $peringatan = [];

        $kategori = KategoriLogistik::all()->keyBy(fn ($k) => N::kunci($k->nama_kategori));
        $logistik = Logistik::all()->keyBy(fn ($l) => $l->kategori_logistik_id . '|' . N::kunci($l->nama_item));

        // ---------------- POSKO ----------------
        $posko = [];
        $kePosko = []; // kode_posko -> baris posko (yang valid)
        foreach ($this->csv($isi['posko.csv']) as $r) {
            $b = $this->periksaPosko($r, $peringatan);
            $posko[] = $b;
            if ($b['status'] !== 'error') {
                $kePosko[$b['kode']] = $b;
            }
        }

        $cariPosko = function (array $r, array &$error) use ($kePosko) {
            $kode = $r['kode_posko'] ?? '';
            if (! isset($kePosko[$kode])) {
                $error[] = 'Posko dengan kode_posko ini tidak ada / tidak valid di posko.csv';
                return null;
            }
            return $kePosko[$kode];
        };

        // ---------------- PENGUNGSI ----------------
        $pengungsi = [];
        $kodeDipakai = [];
        $dihapus = DB::table('paket_penghapusans')->where('jenis', 'pengungsi')->pluck('kode')->flip();
        $lamaByKode = Pengungsi::semuaKeberadaan()->whereNotNull('kode')->get()->keyBy('kode');

        foreach ($this->csv($isi['pengungsi.csv']) as $r) {
            $pengungsi[] = $this->periksaPengungsi($r, $cariPosko, $kodeDipakai, $dihapus, $lamaByKode, $peringatan);
        }

        // ---------------- BARANG MASUK / KELUAR ----------------
        $barangBaru = [];
        $masuk = [];
        $keluar = [];
        $kodeMasuk = StokMasuk::whereNotNull('kode')->pluck('kode')->flip();
        $kodeKeluar = StokKeluar::whereNotNull('kode')->pluck('kode')->flip();
        $dipakaiMasuk = $dipakaiKeluar = [];

        foreach ($this->csv($isi['barang_masuk.csv']) as $r) {
            $masuk[] = $this->periksaBarang($r, 'masuk', $cariPosko, $kategori, $logistik, $kodeMasuk, $dipakaiMasuk, $barangBaru);
        }
        foreach ($this->csv($isi['barang_keluar.csv']) as $r) {
            $keluar[] = $this->periksaBarang($r, 'keluar', $cariPosko, $kategori, $logistik, $kodeKeluar, $dipakaiKeluar, $barangBaru);
        }

        // ---------------- PETUGAS (opsional) ----------------
        $petugas = [];
        foreach ($this->csv($isi['petugas.csv'] ?? '') as $r) {
            $petugas[] = $this->periksaPetugas($r, $kePosko);
        }

        // ---------------- PERBANDINGAN STOK (hanya peringatan) ----------------
        $peringatan = array_merge($peringatan, $this->bandingkanStok($isi, $kePosko, $masuk, $keluar));

        $hitung = fn ($baris) => collect($baris)->countBy('status')->all();

        return [
            'info'        => $info,
            'posko'       => $posko,
            'pengungsi'   => $pengungsi,
            'masuk'       => $masuk,
            'keluar'      => $keluar,
            'petugas'     => $petugas,
            'ada_petugas' => isset($isi['petugas.csv']),
            'barang_baru' => array_values($barangBaru),
            'posko_baru'  => collect($posko)->where('status', 'tambah')->pluck('data.nama_lokasi', 'kode')->all(),
            'peringatan'  => array_values(array_unique($peringatan)),
            'ringkasan'   => [
                'posko'     => $hitung($posko),
                'pengungsi' => $hitung($pengungsi),
                'masuk'     => $hitung($masuk),
                'keluar'    => $hitung($keluar),
                'petugas'   => $hitung($petugas),
            ],
        ];
    }

    private function periksaPosko(array $r, array &$peringatan): array
    {
        $error = [];
        $kode = $r['kode_posko'];
        $nama = N::teks($r['nama_lokasi']);
        $namaDesa = N::teks($r['nama_desa']);
        $alamat = N::teks($r['alamat_lokasi']);
        $luas = N::angka($r['luas_lokasi']);
        $lat = N::koordinat($r['latitude']);
        $lng = N::koordinat($r['longitude']);
        $status = $r['status_operasional'];
        $diubah = $this->waktu($r['diubah_pada']);

        if (! preg_match(self::UUID, $kode)) $error[] = 'kode_posko tidak valid';
        if (! $nama) $error[] = 'Nama lokasi kosong';
        if (! $namaDesa) $error[] = 'Nama desa kosong';
        if (! $alamat) $error[] = 'Alamat kosong';
        if (! is_float($luas) && ! is_int($luas) || $luas <= 0) $error[] = 'Luas lokasi harus angka lebih dari 0';
        if (! is_float($lat) || $lat < self::LAT_MIN || $lat > self::LAT_MAX) $error[] = 'Latitude tidak valid (di luar wilayah Indonesia)';
        if (! is_float($lng) || $lng < self::LNG_MIN || $lng > self::LNG_MAX) $error[] = 'Longitude tidak valid (di luar wilayah Indonesia)';
        if (! in_array($status, self::STATUS_OPS, true)) $error[] = "Status operasional \"{$status}\" tidak dikenali";
        if ($diubah === false || $diubah === null) $error[] = 'diubah_pada tidak valid';

        $kapasitas = is_numeric($luas) && $luas > 0 ? (int) round($luas / self::M2_PER_ORANG) : 0;

        $desa = $namaDesa
            ? Desa::all()->first(fn ($d) => N::kunciDesa($d->nama_desa) === N::kunciDesa($namaDesa))
            : null;

        // Cocokkan: kode dulu, lalu nama posko + desa.
        $lokasi = preg_match(self::UUID, $kode) ? Lokasi::where('kode', $kode)->first() : null;
        $cocokNama = false;
        if (! $lokasi && $desa && $nama) {
            $lokasi = Lokasi::where('desa_id', $desa->id)->get()
                ->first(fn ($l) => N::kunci($l->nama_lokasi) === N::kunci($nama));
            $cocokNama = (bool) $lokasi;
            if ($lokasi && $lokasi->kode && $lokasi->kode !== $kode) {
                $peringatan[] = "Posko {$nama} juga pernah dikirim dari perangkat lain. Datanya digabung ke posko yang sama.";
            }
        }

        $baru = [
            'alamat_lokasi'         => $alamat,
            'luas_lokasi'           => is_numeric($luas) ? (float) $luas : null,
            'kapasitas_pengungsi'   => $kapasitas,
            'latitude'              => is_float($lat) ? (string) $lat : null,
            'longitude'             => is_float($lng) ? (string) $lng : null,
            'status_operasional'    => $status,
            'mulai_beroperasi_at'   => $this->waktu($r['mulai_beroperasi_at']),
            'selesai_beroperasi_at' => $this->waktu($r['selesai_beroperasi_at']),
        ];

        $lama = [];
        $ubah = false;
        if ($lokasi && ! $error) {
            // Diperbarui hanya jika versi paket lebih baru dari data di pusat.
            $ubah = $diubah instanceof Carbon && $diubah->gt($lokasi->updated_at ?? Carbon::create(1970));
            if ($ubah) {
                if (! $lokasi->isApproved() && $status !== $lokasi->status_operasional) {
                    $peringatan[] = "Status operasional posko {$nama} tidak diubah karena laporan posko belum disetujui di website.";
                    $baru['status_operasional'] = $lokasi->status_operasional;
                    $baru['mulai_beroperasi_at'] = $lokasi->mulai_beroperasi_at;
                    $baru['selesai_beroperasi_at'] = $lokasi->selesai_beroperasi_at;
                }
                $lama = $this->bandingkan($lokasi, $baru);
                $ubah = (bool) $lama;
            }
        }

        $statusBaris = match (true) {
            (bool) $error => 'error',
            ! $lokasi     => 'tambah',
            $ubah         => 'perbarui',
            default       => 'sama',
        };

        return [
            'row'    => $r['_row'],
            'kode'   => $kode,
            'status' => $statusBaris,
            'error'  => $error,
            'alasan' => match ($statusBaris) {
                'tambah'   => 'Posko baru' . ($desa ? '' : " · desa \"{$namaDesa}\" ditambahkan"),
                'perbarui' => $cocokNama ? 'Disambungkan ke posko yang sudah ada (nama + desa) & diperbarui' : 'Data paket lebih baru',
                'sama'     => $cocokNama ? 'Disambungkan ke posko yang sudah ada (nama + desa)' : 'Sudah ada, tidak ada perubahan',
                default    => '',
            },
            'lokasi_id' => $lokasi?->id,
            'sambung'   => $lokasi && ! $lokasi->kode,
            'data'   => [
                'nama_lokasi'        => $nama,
                'nama_desa'          => $desa->nama_desa ?? $namaDesa,
                'kapasitas'          => $kapasitas,
                'status_operasional' => $baru['status_operasional'],
                'relawan'            => N::teks($r['relawan_penanggung_jawab']),
            ],
            'lama'   => $lama,
            'simpan' => $error ? null : [
                'desa_id'   => $desa?->id,
                'nama_desa' => $namaDesa,
                'nama'      => $nama,
                'ubah'      => $ubah,
            ] + $baru,
        ];
    }

    private function periksaPetugas(array $r, array $kePosko): array
    {
        $error = [];
        $peran = $r['peran'];
        $nama = N::teks($r['nama']);
        $kode = $r['kode_posko'];

        if (! in_array($peran, [PetugasPosko::ADMIN, PetugasPosko::RELAWAN], true)) {
            $error[] = "Peran \"{$peran}\" tidak dikenali (admin / relawan)";
        }
        if (! $nama) $error[] = 'Nama kosong';
        // Admin = penanggung jawab semua posko di paket (kode_posko kosong);
        // relawan harus merujuk posko di posko.csv.
        if ($peran === PetugasPosko::RELAWAN && ! isset($kePosko[$kode])) {
            $error[] = 'Posko relawan tidak ada / tidak valid di posko.csv';
        }

        return [
            'row'    => $r['_row'],
            'status' => $error ? 'error' : 'tambah',
            'alasan' => '',
            'error'  => $error,
            'data'   => [
                'nama'   => $nama,
                'peran'  => $peran === PetugasPosko::ADMIN ? 'Admin (penanggung jawab)' : 'Relawan',
                'posko'  => $peran === PetugasPosko::ADMIN ? 'Semua posko di paket' : ($kePosko[$kode]['data']['nama_lokasi'] ?? '-'),
                'kontak' => N::teks($r['kontak']),
            ],
            'simpan' => $error ? null : [
                'peran'      => $peran,
                'nama'       => $nama,
                'kontak'     => N::teks($r['kontak']),
                'kode_akun'  => preg_match(self::UUID, $r['kode_akun'] ?? '') ? $r['kode_akun'] : null,
                'kode_posko' => $peran === PetugasPosko::ADMIN ? null : $kode,
            ],
        ];
    }

    private function periksaPengungsi(array $r, callable $cariPosko, array &$kodeDipakai, $dihapus, $lamaByKode, array &$peringatan): array
    {
        $error = [];
        $kode = $r['kode'];
        $posko = $cariPosko($r, $error);

        $nama = N::teks($r['nama']);
        $tglTeks = $r['tanggal_lahir'];
        $tanggal = $tglTeks === '' ? null : $this->tanggal($tglTeks);
        $kk = preg_replace('/[\s\'.-]/', '', $r['nomor_kk']);
        $usia = $r['usia'] === '' ? null : filter_var($r['usia'], FILTER_VALIDATE_INT);
        $diubah = $this->waktu($r['diubah_pada']);
        $dihapusPada = $this->waktu($r['dihapus_pada']);

        if (! preg_match(self::UUID, $kode)) $error[] = 'kode tidak valid';
        if (isset($kodeDipakai[$kode])) $error[] = "kode sama dengan baris {$kodeDipakai[$kode]}";
        $kodeDipakai[$kode] = $r['_row'];
        if (! $nama) $error[] = 'Nama kosong';
        if ($tanggal === false) $error[] = 'Tanggal lahir harus TAHUN-BULAN-TANGGAL';
        elseif ($tanggal instanceof Carbon && $tanggal->isAfter(today())) $error[] = 'Tanggal lahir tidak boleh setelah hari ini';
        // Nomor KK boleh kosong (dokumen bisa hilang saat bencana); jika diisi harus 16 digit.
        if ($kk !== '' && ! preg_match('/^\d{16}$/', $kk)) $error[] = 'Nomor KK harus 16 digit angka';
        if ($usia === false || ($usia !== null && ($usia < 0 || $usia > 130))) $error[] = 'Usia harus bilangan bulat 0–130';
        if (! in_array($r['jenis_kelamin'], self::JENIS_KELAMIN, true)) $error[] = 'Jenis kelamin harus Laki-laki atau Perempuan';
        $rentan = $r['kelompok_rentan'] === '' ? 'Tidak' : $r['kelompok_rentan'];
        if (! in_array($rentan, self::KELOMPOK_RENTAN, true)) $error[] = "Kelompok rentan \"{$rentan}\" tidak dikenali";
        if ($diubah === false || $diubah === null) $error[] = 'diubah_pada tidak valid';
        if ($dihapusPada === false) $error[] = 'dihapus_pada tidak valid';
        if ($usia === null && $tanggal instanceof Carbon) {
            $usia = (int) $tanggal->diffInYears(now());
        }

        $data = [
            'nama'              => $nama,
            'asal'              => N::teks($r['asal']) ?? '',
            'tanggal_lahir'     => $tanggal instanceof Carbon ? $tanggal->toDateString() : null,
            'nomor_kk'          => $kk !== '' ? $kk : null,
            'usia'              => $usia ?? 0,
            'jenis_kelamin'     => $r['jenis_kelamin'],
            'kondisi_kesehatan' => N::teks($r['kondisi_kesehatan']) ?? 'Sehat',
            'kelompok_rentan'   => $rentan,
            'riwayat_penyakit'  => N::teks($r['riwayat_penyakit']),
            'dicatat_oleh'      => ($oleh = N::teks($r['dicatat_oleh'] ?? null)) ? mb_substr($oleh, 0, 255) : null,
        ];
        // Keberadaan (kolom sejak 27 Okt 2026; paket lama tanpa kolom ini = di posko).
        $status = $r['status_keberadaan'] ?? '';
        $data['status_keberadaan'] = isset(Pengungsi::KEBERADAAN[$status]) ? $status : Pengungsi::DI_POSKO;
        $keluar = $data['status_keberadaan'] === Pengungsi::DI_POSKO ? false : $this->tanggal($r['keluar_pada'] ?? '');
        $data['keluar_pada'] = $keluar instanceof Carbon ? $keluar->toDateString() : null;
        $data['keterangan_keluar'] = $data['status_keberadaan'] === Pengungsi::DI_POSKO
            ? null
            : (($k = N::teks($r['keterangan_keluar'] ?? null)) ? mb_substr($k, 0, 255) : null);

        $hasil = fn ($status, $alasan, $lama = null, $simpan = null) => [
            'row'    => $r['_row'],
            'kode'   => $kode,
            'status' => $status,
            'alasan' => $alasan,
            'error'  => $error,
            'data'   => [
                'nama'   => $nama,
                'posko'  => $posko['data']['nama_lokasi'] ?? '-',
                'usia'   => $data['usia'],
                'jenis_kelamin' => $data['jenis_kelamin'],
            ],
            'simpan' => $simpan,
            'lama_id' => $lama?->id,
        ];

        if ($error) return $hasil('error', '');

        if (isset($dihapus[$kode])) {
            return $hasil('lewati', 'Sudah pernah dihapus di pusat');
        }

        $lama = $lamaByKode[$kode] ?? null;
        $simpan = ['kode' => $kode, 'kode_posko' => $r['kode_posko'], 'diubah' => $diubah, 'dihapus' => $dihapusPada] + $data;

        if ($dihapusPada instanceof Carbon) {
            return $lama
                ? $hasil('hapus', 'Dihapus di aplikasi', $lama, $simpan)
                : $hasil('lewati', 'Dihapus di aplikasi sebelum pernah diimpor', null, $simpan);
        }

        if ($lama) {
            $versiPusat = $lama->diubah_pada_perangkat ? Carbon::parse($lama->diubah_pada_perangkat) : null;
            return ($versiPusat === null || $diubah->gt($versiPusat))
                ? $hasil('perbarui', 'Data paket lebih baru', $lama, $simpan)
                : $hasil('sama', 'Versi di pusat sama atau lebih baru', $lama);
        }

        // Orang yang sama yang dicatat langsung di website (nama + tanggal lahir, posko sama).
        if ($posko['lokasi_id'] && $data['tanggal_lahir']) {
            $sama = Pengungsi::semuaKeberadaan()->where('lokasi_id', $posko['lokasi_id'])
                ->whereDate('tanggal_lahir', $data['tanggal_lahir'])
                ->get()
                ->first(fn ($p) => N::kunci($p->nama) === N::kunci($nama));
            if ($sama && ! $sama->kode) {
                return $hasil('perbarui', 'Disambungkan ke data yang sudah ada (nama + tanggal lahir)', $sama, $simpan);
            }
            if ($sama) {
                $peringatan[] = "{$nama} ({$data['tanggal_lahir']}) di posko {$posko['data']['nama_lokasi']} juga tercatat dari perangkat lain. Periksa apakah orang yang sama.";
            }
        }

        return $hasil('tambah', 'Pengungsi baru', null, $simpan);
    }

    private function periksaBarang(array $r, string $jenis, callable $cariPosko, $kategori, $logistik, $kodeAda, array &$dipakai, array &$barangBaru): array
    {
        $error = [];
        $kode = $r['kode'];
        $posko = $cariPosko($r, $error);
        $kolomJumlah = $jenis === 'masuk' ? 'jumlah_masuk' : 'jumlah_keluar';
        $kolomTanggal = $jenis === 'masuk' ? 'tanggal_masuk' : 'tanggal_keluar';

        $nama = N::teks($r['nama_item']);
        $satuan = N::teks($r['satuan']);
        $kat = $kategori->get(N::kunci($r['kategori']));
        $jumlah = filter_var($r[$kolomJumlah], FILTER_VALIDATE_INT);
        $tanggal = $this->tanggal($r[$kolomTanggal]);
        $dibuat = $this->waktu($r['dibuat_pada']);

        if (! preg_match(self::UUID, $kode)) $error[] = 'kode tidak valid';
        if (isset($dipakai[$kode])) $error[] = "kode sama dengan baris {$dipakai[$kode]}";
        $dipakai[$kode] = $r['_row'];
        if (! $nama) $error[] = 'Nama item kosong';
        if (! $kat) $error[] = 'Kategori "' . $r['kategori'] . '" tidak ada di sistem';
        if (! $satuan) $error[] = 'Satuan kosong';
        if ($jumlah === false || $jumlah < 1) $error[] = 'Jumlah harus bilangan bulat minimal 1';
        if (! $tanggal instanceof Carbon) $error[] = 'Tanggal harus TAHUN-BULAN-TANGGAL';
        elseif ($tanggal->isAfter(today())) $error[] = 'Tanggal tidak boleh setelah hari ini';
        if ($dibuat === false || $dibuat === null) $error[] = 'dibuat_pada tidak valid';
        if ($jenis === 'masuk' && ! N::teks($r['sumber_bantuan'])) $error[] = 'Sumber bantuan kosong';
        if ($jenis === 'keluar' && ! array_key_exists($r['tujuan'], StokKeluar::TUJUAN)) $error[] = 'Tujuan "' . $r['tujuan'] . '" tidak dikenali';

        $kunciBarang = $kat && $nama ? $kat->id . '|' . N::kunci($nama) : null;
        $ada = $kunciBarang ? $logistik->get($kunciBarang) : null;
        // Nama lama barang yang sudah digabung (alias) -> barang baku.
        if (! $ada && $kunciBarang && ($baku = GabungLogistikService::dariAlias($nama))) {
            $ada = $baku;
            $kunciBarang = $baku->kategori_logistik_id . '|' . N::kunci($baku->nama_item);
            $nama = $baku->nama_item;
        }

        $status = match (true) {
            (bool) $error          => 'error',
            isset($kodeAda[$kode]) => 'lewati',
            default                => 'tambah',
        };

        if ($status === 'tambah' && ! $ada) {
            $barangBaru[$kunciBarang] = ['nama_item' => $nama, 'kategori' => $kat->nama_kategori, 'kategori_id' => $kat->id, 'satuan' => $satuan];
        }

        $keterangan = N::teks($r['keterangan']);
        if ($oleh = N::teks($r['dicatat_oleh'])) {
            $keterangan = trim(($keterangan ? "{$keterangan} · " : '') . "Dicatat oleh {$oleh} (aplikasi offline)");
        }

        return [
            'row'    => $r['_row'],
            'kode'   => $kode,
            'status' => $status,
            'alasan' => $status === 'lewati' ? 'Sudah pernah diimpor' : '',
            'error'  => $error,
            'data'   => [
                'nama_item' => $nama,
                'posko'     => $posko['data']['nama_lokasi'] ?? '-',
                'jumlah'    => $jumlah === false ? $r[$kolomJumlah] : $jumlah,
                'satuan'    => $satuan,
                'tanggal'   => $tanggal instanceof Carbon ? $tanggal->toDateString() : $r[$kolomTanggal],
            ],
            'simpan' => $status !== 'tambah' ? null : [
                'kode'        => $kode,
                'kode_posko'  => $r['kode_posko'],
                'barang'      => $kunciBarang,
                'jumlah'      => $jumlah,
                'tanggal'     => $tanggal->toDateString(),
                'keterangan'  => $keterangan,
                'dibuat'      => $dibuat,
                'sumber'      => N::teks($r['sumber_bantuan'] ?? null),
                'tujuan'      => $r['tujuan'] ?? null,
                'penerima'    => N::teks($r['penerima'] ?? null),
            ],
        ];
    }

    /**
     * Peringatan jika stok di HP (stok.csv) berbeda dengan stok pusat
     * setelah catatan baru digabung. Tidak mengubah data apa pun.
     */
    private function bandingkanStok(array $isi, array $kePosko, array $masuk, array $keluar): array
    {
        $selisih = []; // "kode_posko|nama" -> perubahan dari catatan baru
        foreach ([[$masuk, 1], [$keluar, -1]] as [$baris, $tanda]) {
            foreach ($baris as $b) {
                if ($b['status'] === 'tambah') {
                    $k = $b['simpan']['kode_posko'] . '|' . N::kunci($b['data']['nama_item']);
                    $selisih[$k] = ($selisih[$k] ?? 0) + $tanda * $b['simpan']['jumlah'];
                }
            }
        }

        $hasil = [];
        foreach ($this->csv($isi['stok.csv']) as $r) {
            $posko = $kePosko[$r['kode_posko']] ?? null;
            if (! $posko) continue;
            $namaBaku = GabungLogistikService::dariAlias($r['nama_item'])?->nama_item ?? $r['nama_item'];
            $kunci = $r['kode_posko'] . '|' . N::kunci($namaBaku);

            $pusat = $posko['lokasi_id'] ? $this->stokPusat($posko['lokasi_id'], $r['nama_item']) : 0;
            $setelah = max(0, $pusat + ($selisih[$kunci] ?? 0));
            $hp = (int) $r['jumlah_stok'];
            if ($setelah !== $hp) {
                $hasil[] = "Stok {$r['nama_item']} di posko {$posko['data']['nama_lokasi']}: di HP {$hp} {$r['satuan']}, di pusat setelah digabung {$setelah} {$r['satuan']}. Periksa apakah ada catatan yang dibuat langsung di website.";
            }
        }

        return $hasil;
    }

    /** Jumlah stok pusat satu barang (dicocokkan dengan nama) di satu lokasi. */
    private function stokPusat(int $lokasiId, string $namaItem): int
    {
        $namaItem = GabungLogistikService::dariAlias($namaItem)?->nama_item ?? $namaItem;
        return (int) StokLogistik::query()
            ->join('logistiks', 'logistiks.id', '=', 'stok_logistiks.logistik_id')
            ->where('stok_logistiks.lokasi_id', $lokasiId)
            ->get(['stok_logistiks.jumlah_stok', 'logistiks.nama_item'])
            ->filter(fn ($s) => N::kunci($s->nama_item) === N::kunci($namaItem))
            ->sum('jumlah_stok');
    }

    /**
     * Setelah paket disimpan: barang yang stoknya di HP (stok.csv) berbeda dengan
     * stok pusat. Disimpan di selisih_stok_pakets supaya bisa diselidiki nanti.
     */
    private function selisihSetelahSimpan(array $isi, array $lokasiId): array
    {
        $hasil = [];
        foreach ($this->csv($isi['stok.csv']) as $r) {
            $lokasi = $lokasiId[$r['kode_posko']] ?? null;
            $nama = N::teks($r['nama_item']);
            if (! $lokasi || ! $nama || ! is_numeric($r['jumlah_stok'])) continue;

            $hp = (int) $r['jumlah_stok'];
            $pusat = $this->stokPusat($lokasi, $nama);
            if ($hp !== $pusat) {
                $hasil[] = [
                    'lokasi_id'  => $lokasi,
                    'nama_item'  => mb_substr($nama, 0, 255),
                    'satuan'     => mb_substr((string) N::teks($r['satuan']), 0, 50),
                    'stok_hp'    => $hp,
                    'stok_pusat' => $pusat,
                ];
            }
        }
        return $hasil;
    }

    /* =========================================================
     |  SIMPAN
     ========================================================= */

    /**
     * Menyimpan paket. [$relawanBaru] = kode_posko -> relawan_id untuk posko baru.
     * Setiap paket yang digabung dicatat di riwayat_import_pakets ([$namaFile],
     * [$userId] = admin pengimpor).
     *
     * @throws DomainException jika ada posko baru yang belum dipilih relawannya
     */
    public function simpan(array $isi, array $relawanBaru, string $namaFile = 'paket.zip', ?int $userId = null): array
    {
        return DB::transaction(function () use ($isi, $relawanBaru, $namaFile, $userId) {
            $hasil = $this->periksa($isi);

            foreach ($hasil['posko_baru'] as $kode => $nama) {
                if (empty($relawanBaru[$kode])) {
                    throw new DomainException("Pilih relawan penanggung jawab untuk posko baru {$nama}.");
                }
            }

            $hitung = ['posko' => 0, 'pengungsi' => 0, 'dihapus' => 0, 'masuk' => 0, 'keluar' => 0, 'barang' => 0, 'petugas' => 0];

            // ---- posko ----
            $lokasiId = [];
            foreach ($hasil['posko'] as $b) {
                if ($b['status'] === 'error') continue;
                $lokasiId[$b['kode']] = $this->simpanPosko($b, (int) ($relawanBaru[$b['kode']] ?? 0), $hitung);
            }

            // ---- petugas (hanya jika paket membawa petugas.csv) ----
            if ($hasil['ada_petugas']) {
                $this->simpanPetugas($hasil, $lokasiId, $hitung);
            }

            // ---- pengungsi ----
            foreach ($hasil['pengungsi'] as $b) {
                if (! $b['simpan']) continue;
                $s = $b['simpan'];
                if ($s['dihapus']) {
                    DB::table('paket_penghapusans')->insertOrIgnore([
                        'kode' => $s['kode'], 'jenis' => 'pengungsi', 'dihapus_pada' => $s['dihapus'],
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    if ($b['status'] === 'hapus') {
                        Pengungsi::semuaKeberadaan()->whereKey($b['lama_id'])->delete();
                        $hitung['dihapus']++;
                    }
                    continue;
                }
                $p = $b['lama_id'] ? Pengungsi::semuaKeberadaan()->find($b['lama_id']) : new Pengungsi;
                $p->forceFill([
                    'kode'                  => $s['kode'],
                    'lokasi_id'             => $lokasiId[$s['kode_posko']],
                    'nama'                  => $s['nama'],
                    'asal'                  => $s['asal'],
                    'tanggal_lahir'         => $s['tanggal_lahir'],
                    'nomor_kk'              => $s['nomor_kk'],
                    'usia'                  => $s['usia'],
                    'jenis_kelamin'         => $s['jenis_kelamin'],
                    'kondisi_kesehatan'     => $s['kondisi_kesehatan'],
                    'kelompok_rentan'       => $s['kelompok_rentan'],
                    'riwayat_penyakit'      => $s['riwayat_penyakit'],
                    'dicatat_oleh'          => $s['dicatat_oleh'] ?? $p->dicatat_oleh, // kosong di paket = tetap
                    'status_keberadaan'     => $s['status_keberadaan'],
                    'keluar_pada'           => $s['keluar_pada'],
                    'keterangan_keluar'     => $s['keterangan_keluar'],
                    'diubah_pada_perangkat' => $s['diubah'],
                ])->save();
                $hitung['pengungsi']++;
            }

            // ---- barang baru di master ----
            $logistik = Logistik::all()->keyBy(fn ($l) => $l->kategori_logistik_id . '|' . N::kunci($l->nama_item));
            foreach ($hasil['barang_baru'] as $bb) {
                $kunci = $bb['kategori_id'] . '|' . N::kunci($bb['nama_item']);
                if (! $logistik->has($kunci)) {
                    $logistik[$kunci] = Logistik::create([
                        'kategori_logistik_id' => $bb['kategori_id'],
                        'nama_item'            => $bb['nama_item'],
                        'satuan'               => $bb['satuan'],
                        'deskripsi'            => 'Ditambahkan dari Paket Data Posko',
                    ]);
                    $hitung['barang']++;
                }
            }

            // ---- barang masuk / keluar + stok ----
            foreach ([['masuk', 1], ['keluar', -1]] as [$jenis, $tanda]) {
                foreach ($hasil[$jenis] as $b) {
                    if (! $b['simpan']) continue;
                    $s = $b['simpan'];
                    $barang = $logistik[$s['barang']];
                    $lokasi = $lokasiId[$s['kode_posko']];

                    $catatan = $jenis === 'masuk' ? new StokMasuk : new StokKeluar;
                    $catatan->forceFill($jenis === 'masuk'
                        ? ['jumlah_masuk' => $s['jumlah'], 'sumber_bantuan' => $s['sumber'], 'tanggal_masuk' => $s['tanggal']]
                        : ['jumlah_keluar' => $s['jumlah'], 'tujuan' => $s['tujuan'], 'penerima' => $s['penerima'], 'tanggal_keluar' => $s['tanggal']]
                    );
                    $catatan->forceFill([
                        'kode'        => $s['kode'],
                        'lokasi_id'   => $lokasi,
                        'logistik_id' => $barang->id,
                        'keterangan'  => $s['keterangan'],
                        'created_at'  => $s['dibuat'],
                    ])->save();

                    $stok = StokLogistik::firstOrNew(['lokasi_id' => $lokasi, 'logistik_id' => $barang->id], ['jumlah_stok' => 0]);
                    // Stok tidak boleh negatif (aturan website).
                    $stok->ubahJumlah(max(-$stok->jumlah_stok, $tanda * $s['jumlah']));
                    $hitung[$jenis]++;
                }
            }

            $riwayat = $this->catatRiwayat($hasil['info'], $namaFile, $userId, $hitung, $lokasiId);
            $riwayat->selisihStok()->createMany($this->selisihSetelahSimpan($isi, $lokasiId));
            $hitung['selisih_stok'] = $riwayat->selisihStok()->count();

            return $hitung;
        });
    }

    private function catatRiwayat(array $info, string $namaFile, ?int $userId, array $hitung, array $lokasiId): RiwayatImportPaket
    {
        $peran = $info['peran_perangkat'] ?? '';
        $riwayat = RiwayatImportPaket::create([
            'user_id'           => $userId,
            'nama_file'         => mb_substr($namaFile, 0, 255),
            'kode_perangkat'    => mb_substr((string) ($info['kode_perangkat'] ?? ''), 0, 36) ?: null,
            'peran_perangkat'   => in_array($peran, ['induk', 'anggota'], true) ? $peran : null,
            'diekspor_oleh'     => mb_substr((string) ($info['diekspor_oleh'] ?? ''), 0, 255) ?: null,
            'paket_dibuat_pada' => $this->waktu($info['dibuat_pada'] ?? '') ?: null,
            'jumlah'            => $hitung,
        ]);
        $riwayat->lokasis()->attach(array_values(array_unique($lokasiId)));

        return $riwayat;
    }

    /**
     * Daftar petugas per posko diganti utuh dengan isi paket, tapi hanya jika paket ini
     * lebih baru dari paket terakhir dari perangkat yang sama (paket lama tidak menimpa).
     */
    private function simpanPetugas(array $hasil, array $lokasiId, array &$hitung): void
    {
        $perangkat = (string) ($hasil['info']['kode_perangkat'] ?? '');
        $dibuat = $this->waktu($hasil['info']['dibuat_pada'] ?? '') ?: now();
        $valid = collect($hasil['petugas'])->pluck('simpan')->filter();

        foreach ($lokasiId as $kodePosko => $id) {
            $terakhir = PetugasPosko::where('lokasi_id', $id)
                ->where('kode_perangkat', $perangkat)
                ->max('paket_dibuat_pada');
            if ($terakhir && Carbon::parse($terakhir)->gte($dibuat)) {
                continue;
            }

            PetugasPosko::where('lokasi_id', $id)->where('kode_perangkat', $perangkat)->delete();
            foreach ($valid as $t) {
                if ($t['kode_posko'] !== null && $t['kode_posko'] !== $kodePosko) {
                    continue; // relawan posko lain
                }
                PetugasPosko::create([
                    'lokasi_id'         => $id,
                    'kode_perangkat'    => $perangkat,
                    'kode_akun'         => $t['kode_akun'],
                    'peran'             => $t['peran'],
                    'nama'              => $t['nama'],
                    'kontak'            => $t['kontak'],
                    'paket_dibuat_pada' => $dibuat,
                ]);
                $hitung['petugas']++;
            }
        }
    }

    private function simpanPosko(array $b, int $relawanId, array &$hitung): int
    {
        $s = $b['simpan'];
        $isian = [
            'alamat_lokasi'         => $s['alamat_lokasi'],
            'luas_lokasi'           => $s['luas_lokasi'],
            'kapasitas_pengungsi'   => $s['kapasitas_pengungsi'],
            'latitude'              => $s['latitude'],
            'longitude'             => $s['longitude'],
            'status_operasional'    => $s['status_operasional'],
            'mulai_beroperasi_at'   => $s['mulai_beroperasi_at'],
            'selesai_beroperasi_at' => $s['selesai_beroperasi_at'],
        ];

        if ($b['lokasi_id']) {
            $lokasi = Lokasi::find($b['lokasi_id']);
            $kapasitasLama = (int) $lokasi->kapasitas_pengungsi;
            if ($b['sambung']) {
                $lokasi->kode = $b['kode'];
            }
            if ($s['ubah']) {
                $lokasi->forceFill($isian);
                $hitung['posko']++;
            }
            $lokasi->save();
            if ((int) $lokasi->kapasitas_pengungsi !== $kapasitasLama || ! $lokasi->sphereLokasi) {
                SphereLokasi::hitungUntuk($lokasi);
            }
            return $lokasi->id;
        }

        $desaId = $s['desa_id'] ?: Desa::create([
            'nama_desa'   => $s['nama_desa'],
            'alamat_desa' => 'Ditambahkan dari Paket Data Posko',
        ])->id;

        $lokasi = new Lokasi;
        $lokasi->forceFill($isian + [
            'kode'          => $b['kode'],
            'desa_id'       => $desaId,
            'relawan_id'    => $relawanId,
            'nama_lokasi'   => $s['nama'],
            'gambar_lokasi' => self::GAMBAR_DEFAULT,
        ])->save();
        SphereLokasi::hitungUntuk($lokasi);

        // Ditambahkan oleh admin pusat lewat import -> langsung disetujui (sama dengan import Lokasi).
        (new StatusLokasi)->forceFill([
            'lokasi_id' => $lokasi->id,
            'status'    => 'approved',
            'catatan'   => 'Lokasi ditambahkan dari Paket Data Posko pada ' . now('Asia/Jakarta')->format('d-m-Y H:i') . ' WIB',
        ])->save();

        $hitung['posko']++;
        return $lokasi->id;
    }

    /* =========================================================
     |  BANTUAN
     ========================================================= */

    /** 'YYYY-MM-DD HH:MM:SS' WIB -> Carbon. null = kosong, false = tidak valid. */
    private function waktu(?string $s)
    {
        $s = trim((string) $s);
        if ($s === '') return null;
        try {
            $t = Carbon::createFromFormat('Y-m-d H:i:s', $s, config('app.timezone'));
            return $t && $t->format('Y-m-d H:i:s') === $s ? $t : false;
        } catch (\Throwable) {
            return false;
        }
    }

    /** 'YYYY-MM-DD' -> Carbon (awal hari). false = tidak valid. */
    private function tanggal(?string $s)
    {
        $s = trim((string) $s);
        try {
            $t = Carbon::createFromFormat('!Y-m-d', $s, config('app.timezone'));
            return $t && $t->format('Y-m-d') === $s && $t->year >= 1900 ? $t : false;
        } catch (\Throwable) {
            return false;
        }
    }

    /** Kolom posko yang berubah: [kunci => nilai lama]. */
    private function bandingkan(Lokasi $lokasi, array $baru): array
    {
        $berubah = [];
        foreach ($baru as $k => $v) {
            $a = $lokasi->{$k};
            $sama = match (true) {
                $a instanceof Carbon || $v instanceof Carbon => optional($a)?->format('Y-m-d H:i:s') === optional($v)?->format('Y-m-d H:i:s'),
                is_numeric($a) && is_numeric($v)             => (float) $a == (float) $v,
                default                                      => (string) $a === (string) $v,
            };
            if (! $sama) {
                $berubah[$k] = $a instanceof Carbon ? $a->format('Y-m-d H:i') : $a;
            }
        }
        return $berubah;
    }
}
