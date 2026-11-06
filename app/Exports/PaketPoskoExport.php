<?php

namespace App\Exports;

use App\Models\Lokasi;
use App\Models\Pengungsi;
use App\Models\PetugasPosko;
use App\Models\StokKeluar;
use App\Models\StokLogistik;
use App\Models\StokMasuk;
use App\Services\PaketDataPoskoService;
use App\Support\BerkasTerkunci;
use Illuminate\Support\Collection;
use ZipArchive;

/**
 * Export PAKET DATA POSKO dari website untuk diimpor di aplikasi offline (website -> HP).
 * Format SAMA dengan paket dari aplikasi (PaketDataPoskoService::KOLOM), jadi aplikasi bisa
 * memakai pembaca paket yang sudah ada, dan paket yang nanti dikirim balik dari HP tidak dobel:
 *
 *   info.csv, posko.csv (+ kolom foto), pengungsi.csv (+ status keberadaan), barang_masuk.csv,
 *   barang_keluar.csv, stok.csv, petugas.csv, foto/<kode_posko>.<ext>
 *
 * Data yang dibuat di website belum punya kode -> diberi kode UUID & disimpan (tanpa mengubah
 * updated_at). Hasilnya DIKUNCI kata sandi (format terkunci.enc yang sama dengan aplikasi)
 * karena berisi data pribadi pengungsi.
 */
class PaketPoskoExport
{
    private const KOLOM_PENGUNGSI_TAMBAHAN = ['status_keberadaan', 'keluar_pada', 'keterangan_keluar'];

    /** @param Collection<int, Lokasi> $poskos */
    public function __construct(private Collection $poskos, private string $dieksporOleh) {}

    /** Buat file ZIP terkunci sementara, kembalikan path-nya. */
    public function simpan(string $kataSandi): string
    {
        $polos = $this->buatZipPolos();

        try {
            return BerkasTerkunci::kunci(file_get_contents($polos), $kataSandi);
        } finally {
            @unlink($polos);
        }
    }

    /** ZIP paket TANPA kunci (dipakai simpan() & tes). */
    public function buatZipPolos(): string
    {
        $k    = PaketDataPoskoService::KOLOM;
        $csv  = [PoskoAplikasiExport::class, 'csv'];
        $path = tempnam(sys_get_temp_dir(), 'paket_pusat_');
        $zip  = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $posko = $pengungsi = $masuk = $keluar = $stok = $petugas = [];
        $adminSudah = [];

        foreach ($this->poskos as $l) {
            $posko[] = PoskoAplikasiExport::barisPosko($l, $zip);
            $kodePosko = $l->kode;

            foreach (Pengungsi::semuaKeberadaan()->where('lokasi_id', $l->id)->orderBy('id')->get() as $p) {
                $pengungsi[] = [
                    PoskoAplikasiExport::pastikanKode($p), $kodePosko, $p->nama, $p->asal,
                    $p->tanggal_lahir ? \Illuminate\Support\Carbon::parse($p->tanggal_lahir)->toDateString() : '',
                    $p->nomor_kk ?? '', (int) $p->usia, $p->jenis_kelamin, $p->kondisi_kesehatan,
                    $p->kelompok_rentan ?: 'Tidak', $p->riwayat_penyakit ?? '', $p->dicatat_oleh ?? '',
                    $this->waktu($p->created_at), $this->waktu($p->updated_at ?? $p->created_at), '',
                    $p->status_keberadaan ?: Pengungsi::DI_POSKO,
                    $p->keluar_pada ? \Illuminate\Support\Carbon::parse($p->keluar_pada)->toDateString() : '',
                    $p->keterangan_keluar ?? '',
                ];
            }

            foreach (StokMasuk::with('logistik.kategori', 'relawan.user')->where('lokasi_id', $l->id)->orderBy('id')->get() as $m) {
                $masuk[] = [
                    PoskoAplikasiExport::pastikanKode($m), $kodePosko, $m->logistik->nama_item ?? '', $m->logistik->kategori->nama_kategori ?? '',
                    $m->logistik->satuan ?? '', (int) $m->jumlah_masuk, $m->sumber_bantuan,
                    \Illuminate\Support\Carbon::parse($m->tanggal_masuk)->toDateString(), $m->keterangan ?? '',
                    $m->relawan?->user?->name ?? '', $this->waktu($m->created_at),
                ];
            }

            foreach (StokKeluar::with('logistik.kategori', 'user')->where('lokasi_id', $l->id)->orderBy('id')->get() as $o) {
                $keluar[] = [
                    PoskoAplikasiExport::pastikanKode($o), $kodePosko, $o->logistik->nama_item ?? '', $o->logistik->kategori->nama_kategori ?? '',
                    $o->logistik->satuan ?? '', (int) $o->jumlah_keluar, $o->tujuan, $o->penerima ?? '',
                    $o->tanggal_keluar?->toDateString() ?? '', $o->keterangan ?? '', $o->user?->name ?? '', $this->waktu($o->created_at),
                ];
            }

            foreach (StokLogistik::with('logistik')->where('lokasi_id', $l->id)->get() as $s) {
                $stok[] = [$kodePosko, $s->logistik->nama_item ?? '', $s->logistik->satuan ?? '', (int) $s->jumlah_stok];
            }

            foreach ($l->petugasUnik() as $t) {
                // Admin = penanggung jawab semua posko di paket (kode_posko kosong), cukup sekali
                if ($t->peran === PetugasPosko::ADMIN) {
                    $kunci = mb_strtolower($t->nama) . '|' . $t->kontak;
                    if (isset($adminSudah[$kunci])) {
                        continue;
                    }
                    $adminSudah[$kunci] = true;
                }
                $petugas[] = [$t->peran === PetugasPosko::ADMIN ? '' : $kodePosko, $t->peran, $t->nama, $t->kontak ?? ''];
            }
        }

        $zip->addFromString('info.csv', $csv([...$k['info.csv'], 'sumber'], [[
            PaketDataPoskoService::VERSI_FORMAT, now()->format('Y-m-d H:i:s'), self::kodePerangkatWebsite(), $this->dieksporOleh, 'website',
        ]]));
        $zip->addFromString('posko.csv', $csv(PoskoAplikasiExport::KOLOM_POSKO, $posko));
        $zip->addFromString('pengungsi.csv', $csv([...$k['pengungsi.csv'], ...self::KOLOM_PENGUNGSI_TAMBAHAN], $pengungsi));
        $zip->addFromString('barang_masuk.csv', $csv($k['barang_masuk.csv'], $masuk));
        $zip->addFromString('barang_keluar.csv', $csv($k['barang_keluar.csv'], $keluar));
        $zip->addFromString('stok.csv', $csv($k['stok.csv'], $stok));
        $zip->addFromString('petugas.csv', $csv(PaketDataPoskoService::KOLOM_OPSIONAL['petugas.csv'], $petugas));
        $zip->close();

        return $path;
    }

    /** Kode "perangkat" tetap untuk website pusat (format UUID v4), supaya bisa dibedakan dari HP. */
    public static function kodePerangkatWebsite(): string
    {
        $h = md5('logeva-website|' . config('app.key'));

        return sprintf('%s-%s-4%s-%x%s-%s', substr($h, 0, 8), substr($h, 8, 4), substr($h, 13, 3),
            8 + (hexdec($h[16]) & 3), substr($h, 17, 3), substr($h, 20, 12));
    }

    private function waktu($nilai): string
    {
        return $nilai ? \Illuminate\Support\Carbon::parse($nilai)->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s');
    }
}
