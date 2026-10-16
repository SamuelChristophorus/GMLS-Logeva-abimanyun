<?php

namespace App\Http\Controllers\Admin\Import;

use App\Exports\Template\ImportTemplateExport;
use App\Imports\Support\NilaiImport as N;
use App\Models\Desa;
use App\Models\Lokasi;
use App\Models\Relawan;
use App\Models\SphereLokasi;
use App\Models\StatusLokasi;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Import Lokasi Pengungsian (export sudah ada: LokasiExport).
 * Dicocokkan berdasarkan NAMA LOKASI + DESA.
 *  - Kapasitas TIDAK diisi di Excel: dihitung otomatis dari luas (luas / 3,5 m² per orang),
 *    sama seperti form Tambah Lokasi.
 *  - Lokasi baru  : gambar default, data SPHERE dihitung otomatis dari kapasitas,
 *                   status persetujuan langsung APPROVED (di-import oleh admin).
 *  - Lokasi lama  : kolom yang diisi diperbarui; kolom kosong tidak mengubah data lama.
 *                   Jika luas berubah, kapasitas & data SPHERE dihitung ulang.
 */
class LokasiImportController extends BaseImportController
{
    public const GAMBAR_DEFAULT = 'assets/default/lokasi.png';

    /** Standar SPHERE: luas hunian per orang (m²), sama dengan form Tambah Lokasi */
    public const M2_PER_ORANG = 3.5;

    // Batas wilayah Indonesia (untuk mendeteksi latitude/longitude yang tertukar atau salah ketik)
    private const LAT_MIN = -11.5, LAT_MAX = 6.5, LNG_MIN = 94.5, LNG_MAX = 141.5;

    protected function nama(): string    { return 'lokasi'; }
    protected function judul(): string   { return 'Lokasi Pengungsian'; }
    protected function satuan(): string  { return 'lokasi'; }

    protected function kolomFile(): array
    {
        return [
            'nama_lokasi'         => ['nama lokasi', 'nama posko', 'lokasi', 'posko'],
            'nama_desa'           => ['nama desa', 'desa'],
            'email_relawan'       => ['email relawan', 'email penanggung jawab', 'penanggung jawab', 'relawan'],
            'alamat_lokasi'       => ['alamat lokasi', 'alamat', 'detail jalan'],
            'luas_lokasi'         => ['luas lokasi', 'luas', 'luas m2', 'estimasi luas area terpakai'],
            'latitude'            => ['latitude', 'lat', 'lintang'],
            'longitude'           => ['longitude', 'lng', 'long', 'bujur'],
        ];
    }

    protected function kolomWajib(): array
    {
        return ['nama_lokasi', 'nama_desa'];
    }

    protected function kolomTampil(): array
    {
        return [
            'nama_lokasi'         => 'Nama Lokasi',
            'nama_desa'           => 'Desa',
            'email_relawan'       => 'Email Relawan',
            'alamat_lokasi'       => 'Alamat',
            'luas_lokasi'         => 'Luas (m²)',
            'kapasitas_pengungsi' => 'Kapasitas (otomatis)',
            'latitude'            => 'Latitude',
            'longitude'           => 'Longitude',
        ];
    }

    protected function peringatan(): array
    {
        return [
            'Lokasi baru akan langsung berstatus DISETUJUI dan memakai foto default (dapat diganti lewat Edit).',
            'Kapasitas dihitung otomatis dari luas (luas ÷ 3,5 m² per orang), lalu data kebutuhan SPHERE dihitung dari kapasitas, sama seperti form Tambah Lokasi.',
        ];
    }

    public function template()
    {
        return Excel::download(new ImportTemplateExport(
            'Lokasi Pengungsian',
            [
                ['Nama Lokasi',         'wajib', 'Nama posko/TEA, contoh: Posko Utama Balai Desa.', 32],
                ['Nama Desa',           'wajib', 'Harus sudah terdaftar di menu Data Desa.', 20],
                ['Email Relawan',       'wajib', 'Email relawan penanggung jawab (harus sudah terdaftar). Boleh kosong jika hanya memperbarui lokasi lama.', 30],
                ['Alamat Lokasi',       'wajib', 'Detail jalan / rute ke posko.', 40],
                ['Luas Lokasi (m2)',    'wajib', 'Estimasi luas area terpakai (angka), contoh: 1050. Kapasitas dihitung otomatis: luas ÷ 3,5 m² per orang.', 18],
                ['Latitude',            'wajib', 'Contoh: -6.9195 (angka negatif untuk wilayah selatan khatulistiwa).', 14],
                ['Longitude',           'wajib', 'Contoh: 106.1297', 14],
            ],
            [
                ['Posko Utama Balai Desa', 'Panggarangan', 'ahmad.fauzi@contoh.com', 'Jl. Raya Panggarangan RT 01/RW 01', '1050', '-6.9195', '106.1297'],
            ],
            [
                'Lokasi dianggap sama jika Nama Lokasi dan Desa-nya sama. Data lokasi tersebut akan diperbarui.',
                'Untuk lokasi lama, kolom yang dikosongkan tidak mengubah data. Untuk lokasi baru, semua kolom wajib diisi.',
                'File hasil tombol Export di menu Lokasi Pengungsian juga bisa di-import kembali untuk memperbarui data.',
                'Kapasitas pengungsi tidak perlu diisi: dihitung otomatis dari luas (luas ÷ 3,5 m² per orang), sama seperti form Tambah Lokasi.',
                'Lokasi baru langsung berstatus disetujui, memakai foto default, dan data SPHERE dihitung otomatis dari kapasitas.',
            ]
        ), 'template_import_lokasi.xlsx');
    }

    protected function periksa(array $rows): array
    {
        $desas    = Desa::all()->keyBy(fn ($d) => N::kunciDesa($d->nama_desa));
        $relawans = Relawan::with('user')->get()
            ->filter(fn ($r) => $r->user)
            ->keyBy(fn ($r) => N::kunci($r->user->email));
        $existing = Lokasi::with('relawan.user')->get()
            ->keyBy(fn ($l) => $l->desa_id . '|' . N::kunci($l->nama_lokasi));
        $dipakai  = [];
        $hasil    = [];

        foreach ($rows as $r) {
            $nama      = N::teks($r['nama_lokasi'] ?? null);
            $desaNama  = N::teks($r['nama_desa'] ?? null);
            $email     = N::kunci($r['email_relawan'] ?? null);
            $alamat    = N::teks($r['alamat_lokasi'] ?? null);
            $luas      = N::angka($r['luas_lokasi'] ?? null);
            $kapasitas = is_numeric($luas) && $luas > 0 ? (int) round($luas / self::M2_PER_ORANG) : null;
            $lat       = N::koordinat($r['latitude'] ?? null);
            $lng       = N::koordinat($r['longitude'] ?? null);

            $desa    = $desaNama ? $desas->get(N::kunciDesa($desaNama)) : null;
            $relawan = $email !== '' ? $relawans->get($email) : null;
            $kunci   = $desa && $nama ? $desa->id . '|' . N::kunci($nama) : null;
            $lama    = $kunci ? $existing->get($kunci) : null;
            $error   = [];

            // --- identitas ---
            if (! $nama) {
                $error[] = 'Nama lokasi kosong';
            } elseif (mb_strlen($nama) > 255) {
                $error[] = 'Nama lokasi terlalu panjang';
            }
            if (! $desaNama) {
                $error[] = 'Nama desa kosong';
            } elseif (! $desa) {
                $error[] = "Desa \"{$desaNama}\" belum terdaftar di Data Desa";
            }
            if ($kunci && isset($dipakai[$kunci])) {
                $error[] = "Lokasi sama dengan baris {$dipakai[$kunci]}";
            } elseif ($kunci) {
                $dipakai[$kunci] = $r['_row'];
            }

            // --- relawan ---
            if ($email !== '' && ! $relawan) {
                $error[] = "Relawan dengan email \"{$email}\" tidak ditemukan";
            }

            // --- angka ---
            if ($luas === false || ($luas !== null && $luas <= 0)) {
                $error[] = 'Luas lokasi harus angka lebih dari 0';
            }
            if ($kapasitas === 0) {
                $error[] = 'Luas terlalu kecil (kapasitas menjadi 0 orang)';
            }
            if ($lat === false || ($lat !== null && ($lat < self::LAT_MIN || $lat > self::LAT_MAX))) {
                $error[] = 'Latitude tidak valid (di luar wilayah Indonesia, mungkin tertukar dengan longitude)';
            }
            if ($lng === false || ($lng !== null && ($lng < self::LNG_MIN || $lng > self::LNG_MAX))) {
                $error[] = 'Longitude tidak valid (di luar wilayah Indonesia, mungkin tertukar dengan latitude)';
            }

            // --- lokasi baru: semua kolom wajib ---
            if (! $lama && $desa && $nama) {
                foreach ([
                    'Email relawan' => $email !== '' ? $email : null,
                    'Alamat'        => $alamat,
                    'Luas lokasi'   => $luas,
                    'Latitude'      => $lat,
                    'Longitude'     => $lng,
                ] as $label => $nilai) {
                    if ($nilai === null) {
                        $error[] = "{$label} wajib diisi untuk lokasi baru";
                    }
                }
            }

            $baru = [
                'relawan_id'          => $relawan?->id,
                'alamat_lokasi'       => $alamat,
                'luas_lokasi'         => is_numeric($luas) ? (float) $luas : null,
                'kapasitas_pengungsi' => $kapasitas ?: null,
                'latitude'            => is_numeric($lat) ? (string) $lat : null,
                'longitude'           => is_numeric($lng) ? (string) $lng : null,
            ];

            $ubah = [];
            if ($lama && ! $error) {
                $ubah = $this->bandingkan($lama->only(array_keys($baru)), $baru);
                if (isset($ubah['relawan_id'])) {
                    unset($ubah['relawan_id']);
                    $ubah['email_relawan'] = optional(optional($lama->relawan)->user)->email ?? '-';
                }
            }

            $status = $error ? 'error' : (! $lama ? 'tambah' : ($ubah ? 'perbarui' : 'sama'));

            $hasil[] = [
                'row'    => $r['_row'],
                'status' => $status,
                'error'  => $error,
                'data'   => [
                    'nama_lokasi'         => $nama,
                    'nama_desa'           => $desa->nama_desa ?? $desaNama,
                    'email_relawan'       => $email ?: ($lama ? optional(optional($lama->relawan)->user)->email : null),
                    'alamat_lokasi'       => $alamat,
                    'luas_lokasi'         => $baru['luas_lokasi'],
                    'kapasitas_pengungsi' => $baru['kapasitas_pengungsi'],
                    'latitude'            => $baru['latitude'],
                    'longitude'           => $baru['longitude'],
                ],
                'lama'   => $ubah,
                'simpan' => $error ? null : [
                    'id'          => $lama?->id,
                    'desa_id'     => $desa?->id,
                    'nama_lokasi' => $nama,
                ] + $baru,
            ];
        }

        return $hasil;
    }

    protected function simpan(array $data): array
    {
        $tambah = $perbarui = 0;

        foreach ($data as $row) {
            $id = $row['id'];
            unset($row['id']);

            if ($id && ($lokasi = Lokasi::find($id))) {
                $kapasitasLama = (int) $lokasi->kapasitas_pengungsi;
                $lokasi->forceFill(array_filter($row, fn ($v) => $v !== null))->save();

                if ((int) $lokasi->kapasitas_pengungsi !== $kapasitasLama || ! $lokasi->sphereLokasi) {
                    $this->simpanSphere($lokasi);
                }
                $perbarui++;
                continue;
            }

            $lokasi = new Lokasi;
            $lokasi->forceFill($row + ['gambar_lokasi' => self::GAMBAR_DEFAULT])->save();

            $this->simpanSphere($lokasi);

            (new StatusLokasi)->forceFill([
                'lokasi_id' => $lokasi->id,
                'status'    => 'approved',
                'catatan'   => 'Lokasi di-import oleh admin pada ' . now('Asia/Jakarta')->format('d-m-Y H:i') . ' WIB',
            ])->save();

            $tambah++;
        }

        return compact('tambah', 'perbarui');
    }

    /**
     * Kebutuhan SPHERE harian untuk seluruh kapasitas posko.
     * Rumus sama persis dengan form Tambah Lokasi (pages/admin/lokasi/create.blade.php):
     * air hidup 2,5 L, kebersihan 2 L, memasak 3 L per orang,
     * toilet = round(kapasitas / 50) & round(kapasitas / 20),
     * kalori 2.100 kkal, protein 53 g, lemak 40 g per orang.
     */
    private function simpanSphere(Lokasi $lokasi): void
    {
        $k = (int) $lokasi->kapasitas_pengungsi;

        $nilai = [
            'air_hidup'      => (int) round($k * 2.5),
            'air_kebersihan' => $k * 2,
            'air_memasak'    => $k * 3,
            'toilet_pendek'  => (int) round($k / 50),
            'toilet_panjang' => (int) round($k / 20),
            'kalori'         => $k * 2100,
            'protein'        => $k * 53,
            'lemak'          => $k * 40,
        ];

        $sphere = SphereLokasi::where('lokasi_id', $lokasi->id)->first() ?? new SphereLokasi;
        $sphere->forceFill(['lokasi_id' => $lokasi->id] + $nilai)->save();
    }
}
