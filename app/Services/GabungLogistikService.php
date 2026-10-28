<?php

namespace App\Services;

use App\Imports\Support\NilaiImport as N;
use App\Models\AliasLogistik;
use App\Models\KategoriLogistik;
use App\Models\Logistik;
use App\Models\StokKeluar;
use App\Models\StokLogistik;
use App\Models\StokMasuk;
use App\Support\MasterLogistikBaku;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Menyamakan Master Logistik:
 *  - gabung(): barang ganda digabung ke satu barang. Catatan masuk/keluar & stok
 *    tiap posko dipindahkan, nama lama diingat sebagai alias, barang lama dihapus.
 *  - standarkan(): memastikan daftar baku (MasterLogistikBaku) ada, kebutuhan
 *    hariannya sesuai, dan alias bawaan diterapkan.
 */
class GabungLogistikService
{
    /** @return array{masuk:int, keluar:int, stok:int} */
    public function gabung(Logistik $dari, Logistik $ke): array
    {
        if ($dari->is($ke)) {
            throw new DomainException('Pilih barang tujuan yang berbeda.');
        }

        return DB::transaction(function () use ($dari, $ke) {
            $hasil = [
                'masuk'  => StokMasuk::where('logistik_id', $dari->id)->update(['logistik_id' => $ke->id]),
                'keluar' => StokKeluar::where('logistik_id', $dari->id)->update(['logistik_id' => $ke->id]),
                'stok'   => 0,
            ];

            // Stok per posko dijumlahkan ke barang tujuan.
            foreach (StokLogistik::where('logistik_id', $dari->id)->get() as $s) {
                $tujuan = StokLogistik::where('lokasi_id', $s->lokasi_id)->where('logistik_id', $ke->id)->first();
                if ($tujuan) {
                    $tujuan->ubahJumlah((int) $s->jumlah_stok);
                    $s->delete();
                } else {
                    $s->update(['logistik_id' => $ke->id]);
                }
                $hasil['stok']++;
            }

            AliasLogistik::where('logistik_id', $dari->id)->update(['logistik_id' => $ke->id]);
            $this->catatAlias($dari->nama_item, $ke);
            $dari->delete();

            return $hasil;
        });
    }

    public function catatAlias(string $nama, Logistik $ke): void
    {
        $kunci = N::kunci($nama);
        if ($kunci === '' || $kunci === N::kunci($ke->nama_item)) {
            return;
        }
        AliasLogistik::updateOrCreate(['kunci' => $kunci], ['nama_alias' => $nama, 'logistik_id' => $ke->id]);
    }

    /** Barang tujuan untuk nama lama (alias), atau null. */
    public static function dariAlias(?string $nama): ?Logistik
    {
        $kunci = N::kunci($nama);
        return $kunci === '' ? null : AliasLogistik::with('logistik')->where('kunci', $kunci)->first()?->logistik;
    }

    private function cari(string $nama): ?Logistik
    {
        return Logistik::all()->first(fn ($l) => N::kunci($l->nama_item) === N::kunci($nama));
    }

    public function standarkan(): void
    {
        DB::transaction(function () {
            $kategori = KategoriLogistik::pluck('id')->flip();
            foreach (MasterLogistikBaku::DAFTAR as [$nama, $katId, $satuan, $kebutuhan]) {
                if (! isset($kategori[$katId])) continue;
                if ($ada = $this->cari($nama)) {
                    $ada->update(['kebutuhan_harian' => $kebutuhan, 'satuan' => $satuan]);
                } else {
                    Logistik::create([
                        'nama_item'            => $nama,
                        'kategori_logistik_id' => $katId,
                        'satuan'               => $satuan,
                        'kebutuhan_harian'     => $kebutuhan,
                    ]);
                }
            }

            foreach (MasterLogistikBaku::ALIAS as $lama => $baku) {
                $ke = $this->cari($baku);
                if (! $ke) continue;
                $dari = $this->cari($lama);
                $dari ? $this->gabung($dari, $ke) : $this->catatAlias($lama, $ke);
            }
        });
    }
}
