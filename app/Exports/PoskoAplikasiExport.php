<?php

namespace App\Exports;

use App\Http\Controllers\Admin\Import\LokasiImportController;
use App\Models\Lokasi;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Export lokasi pengungsian UNTUK APLIKASI OFFLINE (website -> HP), berupa ZIP:
 *
 *   info.csv   versi_format, dibuat_pada, sumber, jumlah_posko
 *   posko.csv  kolom SAMA dengan posko.csv di Paket Data Posko (HP -> website) + kolom foto
 *   foto/      foto posko, nama file = <kode_posko>.<ekstensi> (kosong jika posko tidak punya foto)
 *
 * Kapasitas dihitung ulang dari luas (luas ÷ 3,5 m²). Nilai SPHERE tidak ikut karena turunan
 * dari kapasitas (aplikasi bisa menghitung sendiri). Posko yang belum punya kode (dibuat di
 * website) diberi kode UUID dan disimpan, supaya paket dari HP nanti dikenali sebagai posko yang sama.
 *
 * Bagian posko & foto dipakai juga oleh PaketPoskoExport (paket lengkap).
 */
class PoskoAplikasiExport
{
    public const VERSI_FORMAT = 1;

    public const KOLOM_POSKO = [
        'kode_posko', 'nama_lokasi', 'nama_desa', 'alamat_lokasi', 'luas_lokasi', 'kapasitas_pengungsi',
        'latitude', 'longitude', 'status_operasional', 'mulai_beroperasi_at', 'selesai_beroperasi_at',
        'relawan_penanggung_jawab', 'diubah_pada', 'foto', 'ketinggian_mdpl',
    ];

    /** @param Collection<int, Lokasi> $poskos */
    public function __construct(private Collection $poskos) {}

    /** Buat file ZIP sementara, kembalikan path-nya. */
    public function simpan(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'posko_app_');
        $zip  = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $baris = $this->poskos->map(fn ($l) => self::barisPosko($l, $zip))->all();

        $zip->addFromString('info.csv', self::csv(
            ['versi_format', 'dibuat_pada', 'sumber', 'jumlah_posko'],
            [[self::VERSI_FORMAT, now()->format('Y-m-d H:i:s'), 'website', count($baris)]]
        ));
        $zip->addFromString('posko.csv', self::csv(self::KOLOM_POSKO, $baris));
        $zip->close();

        return $path;
    }

    /** Satu baris posko.csv (urutan = KOLOM_POSKO); foto ikut disalin ke ZIP. */
    public static function barisPosko(Lokasi $l, ZipArchive $zip): array
    {
        self::pastikanKode($l);

        return [
            $l->kode,
            $l->nama_lokasi,
            $l->desa->nama_desa ?? '',
            $l->alamat_lokasi,
            self::angka($l->luas_lokasi),
            $l->luas_lokasi > 0 ? (int) round($l->luas_lokasi / LokasiImportController::M2_PER_ORANG) : (int) $l->kapasitas_pengungsi,
            $l->latitude,
            $l->longitude,
            $l->status_operasional ?: Lokasi::OPS_SIAGA,
            $l->mulai_beroperasi_at?->format('Y-m-d H:i:s') ?? '',
            $l->selesai_beroperasi_at?->format('Y-m-d H:i:s') ?? '',
            $l->relawan?->user?->name ?? '',
            $l->updated_at?->format('Y-m-d H:i:s') ?? '',
            self::tambahFoto($zip, $l),
            $l->ketinggian_mdpl === null ? '' : self::angka($l->ketinggian_mdpl),
        ];
    }

    /**
     * Beri kode UUID ke data yang dibuat di website (kodenya masih kosong). Langsung lewat query
     * supaya updated_at TIDAK berubah (data tidak dianggap baru diedit).
     */
    public static function pastikanKode(\Illuminate\Database\Eloquent\Model $model): string
    {
        if (! $model->kode) {
            $model->kode = (string) Str::uuid();
            DB::table($model->getTable())->where('id', $model->getKey())->update(['kode' => $model->kode]);
        }

        return $model->kode;
    }

    /** Salin foto posko ke foto/<kode>.<ext>; kembalikan nama file di ZIP (kosong jika tidak ada). */
    private static function tambahFoto(ZipArchive $zip, Lokasi $l): string
    {
        $asal = $l->gambar_lokasi ? public_path('storage/' . ltrim($l->gambar_lokasi, '/')) : null;

        if (! $asal || ! is_file($asal)) {
            return '';
        }

        $nama = 'foto/' . $l->kode . '.' . strtolower(pathinfo($asal, PATHINFO_EXTENSION) ?: 'jpg');
        $zip->addFile($asal, $nama);
        $zip->setCompressionName($nama, ZipArchive::CM_STORE); // foto sudah terkompres, tidak perlu dikompres lagi

        return $nama;
    }

    public static function csv(array $judul, array $baris): string
    {
        $f = fopen('php://temp', 'r+');
        fputcsv($f, $judul, ',', '"', '');
        foreach ($baris as $b) {
            fputcsv($f, $b, ',', '"', '');
        }
        rewind($f);

        return stream_get_contents($f);
    }

    private static function angka($nilai): string
    {
        return rtrim(rtrim(number_format((float) $nilai, 2, '.', ''), '0'), '.');
    }
}
