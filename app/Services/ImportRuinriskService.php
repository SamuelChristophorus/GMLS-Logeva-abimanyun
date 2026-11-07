<?php

namespace App\Services;

use App\Http\Controllers\Admin\Import\LokasiImportController;
use App\Imports\Support\NilaiImport as N;
use App\Imports\Support\SpreadsheetReader;
use App\Models\Desa;
use App\Models\Lokasi;
use App\Models\Relawan;
use App\Models\SphereLokasi;
use App\Models\StatusLokasi;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Import data Tempat Evakuasi Akhir (TEA) dari aplikasi RUinRISK (file "Data TEA" .xlsx).
 *
 * Kolom RUinRISK -> Logeva:
 *   ID Lokasi -> id_sumber (anti-dobel) · Nama Lokasi · Kapasitas -> luas = kapasitas × 3,5 m²
 *   Latitude/Longitude · Ketinggian (m dpl) -> ketinggian_mdpl
 *   Jenis Tempat, Fasilitas, Keterangan, Diusulkan Oleh: tidak disimpan (keputusan GMLS).
 * Yang tidak ada di RUinRISK (desa, relawan penanggung jawab) dipilih admin di pratinjau;
 * alamat diisi keterangan titik koordinat, foto memakai foto default.
 *
 * Pengaman:
 *   1. Import ulang: ID yang sama memperbarui posko yang sama, tidak menambah posko baru.
 *   2. Hanya baris "Asal Data" RUinRISK (baris yang asalnya dari LOGEVA dilewati, supaya tidak bolak-balik).
 *   3. Peringatan jika ada posko lain < 100 m (kemungkinan tempat yang sama); bawaannya dilewati.
 *   4. Peringatan jika titik di luar wilayah kerja (config logeva.wilayah_kerja).
 *   5. Hanya baris berstatus "approved" di RUinRISK. Di Logeva langsung DISETUJUI (di-import admin).
 */
class ImportRuinriskService
{
    public const SUMBER = 'ruinrisk';
    public const JARAK_DEKAT_METER = 100;

    private const KOLOM = [
        'id_sumber'   => ['id lokasi', 'id'],
        'nama_lokasi' => ['nama lokasi', 'nama tempat', 'nama'],
        'kapasitas'   => ['kapasitas pengungsi', 'kapasitas'],
        'latitude'    => ['latitude', 'lat', 'lintang'],
        'longitude'   => ['longitude', 'lng', 'long', 'bujur'],
        'ketinggian'  => ['ketinggian (m dpl)', 'ketinggian m dpl', 'ketinggian mdpl', 'ketinggian'],
        'asal_data'   => ['asal data', 'sumber data'],
        'status'      => ['status'],
    ];

    private const KOLOM_WAJIB = ['id_sumber', 'nama_lokasi', 'kapasitas', 'latitude', 'longitude'];

    /** Label kolom untuk pesan "kolom tidak ditemukan". */
    public const LABEL = [
        'id_sumber' => 'ID Lokasi', 'nama_lokasi' => 'Nama Lokasi', 'kapasitas' => 'Kapasitas Pengungsi',
        'latitude' => 'Latitude', 'longitude' => 'Longitude',
    ];

    public function baca(UploadedFile $file): array
    {
        return SpreadsheetReader::read($file, self::KOLOM, self::KOLOM_WAJIB);
    }

    /**
     * Periksa setiap baris. Status: tambah | perbarui | sama | lewati | error.
     * Baris "tambah" masih butuh pilihan desa & relawan dari admin saat konfirmasi.
     */
    public function periksa(array $rows): array
    {
        $poskoAda = Lokasi::get(['id', 'nama_lokasi', 'latitude', 'longitude', 'sumber_data', 'id_sumber', 'luas_lokasi',
            'kapasitas_pengungsi', 'ketinggian_mdpl']);
        $perId    = $poskoAda->where('sumber_data', self::SUMBER)->keyBy(fn ($l) => mb_strtolower($l->id_sumber));
        $wilayah  = config('logeva.wilayah_kerja');
        $dipakai  = [];
        $titikFile = []; // titik baris sebelumnya di file yang sama
        $hasil    = [];

        foreach ($rows as $r) {
            $id     = N::teks($r['id_sumber'] ?? null);
            $nama   = N::teks($r['nama_lokasi'] ?? null);
            $kap    = N::angka($r['kapasitas'] ?? null);
            $lat    = N::koordinat($r['latitude'] ?? null);
            $lng    = N::koordinat($r['longitude'] ?? null);
            $tinggi = N::angka($r['ketinggian'] ?? null);
            $asal   = N::teks($r['asal_data'] ?? null);
            $status = mb_strtolower((string) N::teks($r['status'] ?? null));

            $baris = [
                'row' => $r['_row'], 'id_sumber' => $id, 'status' => 'tambah', 'alasan' => '', 'error' => [], 'peringatan' => [],
                'dekat' => false, 'lama_id' => null, 'ubah' => [],
                'data' => [
                    'nama_lokasi' => $nama, 'kapasitas' => is_numeric($kap) ? (int) $kap : $kap,
                    'latitude' => $lat, 'longitude' => $lng, 'ketinggian' => is_numeric($tinggi) ? (float) $tinggi : null,
                ],
            ];

            // --- Pengaman 2 & 5: hanya data asli RUinRISK yang sudah disetujui di sana ---
            if ($asal && ! str_contains(mb_strtolower($asal), 'ruinrisk')) {
                $hasil[] = ['status' => 'lewati', 'alasan' => "Asal data \"{$asal}\" (bukan RUinRISK), kemungkinan berasal dari LOGEVA"] + $baris;
                continue;
            }
            if ($status !== '' && $status !== 'approved') {
                $hasil[] = ['status' => 'lewati', 'alasan' => "Status di RUinRISK \"{$status}\" (belum disetujui)"] + $baris;
                continue;
            }

            // --- Isi wajib ---
            $e = [];
            if (! $id) $e[] = 'ID Lokasi kosong';
            elseif (mb_strlen($id) > 64) $e[] = 'ID Lokasi terlalu panjang';
            elseif (isset($dipakai[mb_strtolower($id)])) $e[] = "ID Lokasi sama dengan baris {$dipakai[mb_strtolower($id)]}";
            if (! $nama) $e[] = 'Nama lokasi kosong';
            elseif (mb_strlen($nama) > 255) $e[] = 'Nama lokasi terlalu panjang';
            if ($kap === null || $kap === false || $kap < 1 || floor((float) $kap) != $kap) $e[] = 'Kapasitas harus bilangan bulat minimal 1';
            if ($lat === null || $lat === false || $lat < -11.5 || $lat > 6.5) $e[] = 'Latitude tidak valid (di luar Indonesia)';
            if ($lng === null || $lng === false || $lng < 94.5 || $lng > 141.5) $e[] = 'Longitude tidak valid (di luar Indonesia)';
            if ($tinggi === false || ($tinggi !== null && ($tinggi < -50 || $tinggi > 5000))) $e[] = 'Ketinggian harus angka -50 sampai 5000 m dpl';
            if ($id) $dipakai[mb_strtolower($id)] = $r['_row'];

            if ($e) {
                $hasil[] = ['status' => 'error', 'error' => $e] + $baris;
                continue;
            }

            // --- Pengaman 1: ID yang sama = posko yang sama ---
            $lama = $perId->get(mb_strtolower($id));
            if ($lama) {
                $baru = $this->nilaiSimpan($baris['data']);
                $ubah = [];
                foreach (['nama_lokasi', 'kapasitas_pengungsi', 'latitude', 'longitude', 'ketinggian_mdpl'] as $k) {
                    $nilaiLama = $lama->$k;
                    $sama = is_numeric($nilaiLama) && is_numeric($baru[$k]) ? abs((float) $nilaiLama - (float) $baru[$k]) < 1e-7 : (string) $nilaiLama === (string) $baru[$k];
                    if (! $sama) $ubah[$k] = [$nilaiLama, $baru[$k]];
                }
                $baris['lama_id'] = $lama->id;
                $baris['ubah'] = $ubah;
                $baris['status'] = $ubah ? 'perbarui' : 'sama';
                $baris['alasan'] = $ubah ? 'Sudah pernah di-import, ada perubahan' : 'Sudah pernah di-import, tidak ada perubahan';
            }

            // --- Pengaman 4: di luar wilayah kerja ---
            if ($wilayah && ($lat < $wilayah['lat'][0] || $lat > $wilayah['lat'][1] || $lng < $wilayah['lng'][0] || $lng > $wilayah['lng'][1])) {
                $baris['peringatan'][] = "Titik berada di luar wilayah {$wilayah['nama']}. Periksa koordinatnya.";
            }

            // --- Pengaman 3: ada posko lain < 100 m ---
            foreach ($poskoAda as $p) {
                if ($p->id === $baris['lama_id'] || ! is_numeric($p->latitude) || ! is_numeric($p->longitude)) continue;
                $m = self::jarakMeter($lat, $lng, (float) $p->latitude, (float) $p->longitude);
                if ($m < self::JARAK_DEKAT_METER) {
                    $baris['dekat'] = true;
                    $baris['peringatan'][] = "Berjarak ±" . round($m) . " m dari posko \"{$p->nama_lokasi}\" yang sudah terdaftar. Periksa apakah tempat yang sama.";
                }
            }
            foreach ($titikFile as [$baris2, $lat2, $lng2, $nama2]) {
                $m = self::jarakMeter($lat, $lng, $lat2, $lng2);
                if ($m < self::JARAK_DEKAT_METER) {
                    $baris['dekat'] = true;
                    $baris['peringatan'][] = "Berjarak ±" . round($m) . " m dari \"{$nama2}\" (baris {$baris2} di file ini).";
                }
            }
            $titikFile[] = [$r['_row'], $lat, $lng, $nama];

            $hasil[] = $baris;
        }

        return $hasil;
    }

    /**
     * Simpan hasil pratinjau.
     *
     * @param array $baris   hasil periksa()
     * @param array $pilihan [index baris => ['ambil' => 1, 'desa_id' => .., 'relawan_id' => ..]]
     *                       Hanya baris yang DICENTANG "ambil" yang disimpan (posko baru maupun pembaruan);
     *                       desa & relawan wajib untuk posko baru yang diambil.
     * @return array{tambah:int, perbarui:int, dilewati:int}
     */
    public function simpan(array $baris, array $pilihan, User $admin): array
    {
        $hitung = ['tambah' => 0, 'perbarui' => 0, 'dilewati' => 0];
        $desa    = Desa::pluck('id')->flip();
        $relawan = Relawan::whereHas('user')->pluck('id')->flip();

        // Periksa pilihan admin dulu, sebelum ada yang disimpan
        $kurang = [];
        foreach ($baris as $i => $b) {
            if ($b['status'] !== 'tambah' || ! $this->diambil($pilihan, $i)) continue;
            if (! isset($desa[(int) ($pilihan[$i]['desa_id'] ?? 0)]) || ! isset($relawan[(int) ($pilihan[$i]['relawan_id'] ?? 0)])) {
                $kurang[] = "baris {$b['row']} ({$b['data']['nama_lokasi']})";
            }
        }
        if ($kurang) {
            throw ValidationException::withMessages(['pilihan' => 'Pilih desa dan relawan penanggung jawab untuk: ' . implode(', ', $kurang) . '.']);
        }

        DB::transaction(function () use ($baris, $pilihan, $admin, &$hitung) {
            foreach ($baris as $i => $b) {
                $bisaDiambil = in_array($b['status'], ['tambah', 'perbarui'], true);
                if (! $bisaDiambil || ! $this->diambil($pilihan, $i)) {
                    $hitung['dilewati'] += $bisaDiambil || $b['status'] === 'lewati' ? 1 : 0;
                    continue;
                }
                $tambah = $b['status'] === 'tambah';

                $nilai = $this->nilaiSimpan($b['data']);
                // Klik dua kali / file sama di tab lain: ID yang sudah tersimpan diperbarui, bukan ditambah
                $lokasi = Lokasi::where('sumber_data', self::SUMBER)->where('id_sumber', $b['id_sumber'])->lockForUpdate()->first();

                if ($lokasi) {
                    $kapasitasLama = (int) $lokasi->kapasitas_pengungsi;
                    $lokasi->forceFill($nilai)->save();
                    if ((int) $lokasi->kapasitas_pengungsi !== $kapasitasLama || ! $lokasi->sphereLokasi) {
                        SphereLokasi::hitungUntuk($lokasi);
                    }
                    $hitung['perbarui']++;
                    continue;
                }

                if (! $tambah) continue;

                $lokasi = new Lokasi;
                $lokasi->forceFill($nilai + [
                    'desa_id'       => (int) $pilihan[$i]['desa_id'],
                    'relawan_id'    => (int) $pilihan[$i]['relawan_id'],
                    'alamat_lokasi' => 'Titik dari RUinRISK (' . $nilai['latitude'] . ', ' . $nilai['longitude'] . ')',
                    'gambar_lokasi' => LokasiImportController::GAMBAR_DEFAULT,
                    'sumber_data'   => self::SUMBER,
                    'id_sumber'     => $b['id_sumber'],
                ])->save();

                SphereLokasi::hitungUntuk($lokasi);

                (new StatusLokasi)->forceFill([
                    'lokasi_id' => $lokasi->id,
                    'status'    => 'approved',
                    'catatan'   => "Di-import dari RUinRISK (ID {$b['id_sumber']}) oleh {$admin->name} pada " . now('Asia/Jakarta')->format('d-m-Y H:i') . ' WIB',
                ])->save();

                $hitung['tambah']++;
            }
        });

        return $hitung;
    }

    /** Baris dicentang "Ambil" oleh admin di pratinjau. */
    private function diambil(array $pilihan, int $i): bool
    {
        return filter_var($pilihan[$i]['ambil'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    /** Kolom lokasis dari data RUinRISK. Luas dihitung balik dari kapasitas agar rumus kapasitas tetap sama. */
    private function nilaiSimpan(array $d): array
    {
        return [
            'nama_lokasi'         => $d['nama_lokasi'],
            'luas_lokasi'         => $d['kapasitas'] * LokasiImportController::M2_PER_ORANG,
            'kapasitas_pengungsi' => (int) $d['kapasitas'],
            'latitude'            => (string) $d['latitude'],
            'longitude'           => (string) $d['longitude'],
            'ketinggian_mdpl'     => $d['ketinggian'],
        ];
    }

    /** Jarak dua titik (meter), rumus haversine. */
    public static function jarakMeter(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $r * asin(min(1, sqrt($a)));
    }
}
