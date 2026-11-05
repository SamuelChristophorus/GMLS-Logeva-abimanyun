<?php

namespace App\Services;

use App\Models\Donasi;
use App\Models\DonasiJejak;
use App\Models\GudangPusat;
use App\Models\Logistik;
use App\Models\Lokasi;
use App\Models\MutasiGudang;
use App\Models\PenyaluranDonasi;
use App\Models\StokGudang;
use App\Models\StokLogistik;
use App\Models\StokMasuk;
use App\Models\User;
use App\Notifications\PemberitahuanPosko;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Semua perubahan data donasi & gudang pusat lewat sini, supaya stok posko,
 * stok gudang pusat, dan jejak donasi selalu selaras.
 */
class DonasiService
{
    /* =========================================================
     |  DONATUR
     ========================================================= */

    /**
     * Donatur mengisi form donasi (tanpa login).
     *
     * @param array $data   hasil validasi form
     * @param array $items  [[logistik_id, jumlah], ...] untuk donasi logistik
     */
    public function ajukan(array $data, array $items, ?UploadedFile $bukti): Donasi
    {
        $donasi = DB::transaction(function () use ($data, $items, $bukti) {
            $kePosko = $data['tujuan'] === Donasi::KE_POSKO;

            $donasi = Donasi::create([
                'kode'            => Donasi::buatKode(),
                'nama_donatur'    => $data['nama_donatur'],
                'anonim'          => (bool) ($data['anonim'] ?? false),
                'kontak'          => $data['kontak'],
                'jenis'           => $data['jenis'],
                'tujuan'          => $data['tujuan'],
                'lokasi_id'       => $kePosko ? $data['lokasi_id'] : null,
                'gudang_pusat_id' => $kePosko ? null : GudangPusat::utama()?->id,
                'nominal'         => $data['jenis'] === Donasi::UANG ? $data['nominal'] : null,
                'bukti_transfer'  => $bukti ? $bukti->store('donasi/bukti-transfer', 'local') : null,
                'perkiraan_tiba'  => $data['jenis'] === Donasi::LOGISTIK ? ($data['perkiraan_tiba'] ?? null) : null,
                'pengirim'        => $data['jenis'] === Donasi::LOGISTIK ? ($data['pengirim'] ?? null) : null,
                'pesan'           => $data['pesan'] ?? null,
                'status'          => Donasi::MENUNGGU,
            ]);

            foreach ($items as $i) {
                $donasi->items()->create(['logistik_id' => $i['logistik_id'], 'jumlah' => $i['jumlah']]);
            }

            $this->jejak($donasi, 'Donasi dicatat', $donasi->isUang()
                ? 'Donatur melaporkan transfer. GMLS akan mencocokkan dengan mutasi rekening.'
                : 'Donatur akan mengirim barang ke ' . $donasi->namaTujuan() . '.', 'fa-file-signature', 'slate');

            return $donasi;
        });

        $donasi->load('items.logistik', 'lokasi.relawan.user');

        $pesan = $donasi->namaTampil() . ' berdonasi ' . $donasi->ringkasan() . ' untuk ' . $donasi->namaTujuan() . '.';
        Notification::send(
            User::role('admin')->get(),
            new PemberitahuanPosko('Donasi baru ' . $donasi->kode, $pesan, route('admin.donasi.show', $donasi), 'fa-hand-holding-heart', 'green', $donasi->lokasi_id)
        );

        // Barang langsung ke posko: relawan penanggung jawab perlu tahu ada kiriman datang
        if ($donasi->isKePosko() && ! $donasi->isUang()) {
            $donasi->lokasi?->relawan?->user?->notify(new PemberitahuanPosko(
                'Kiriman donasi akan datang',
                $pesan . ' Konfirmasi setelah barang sampai di posko.',
                route('donasi.konfirmasi', $donasi),
                'fa-truck',
                'blue',
                $donasi->lokasi_id
            ));
        }

        return $donasi;
    }

    /* =========================================================
     |  VERIFIKASI (admin / relawan posko tujuan)
     ========================================================= */

    /** Admin mengecek transfer di rekening GMLS. */
    public function verifikasiUang(Donasi $donasi, int $nominalDiterima, User $oleh, ?string $catatan): void
    {
        DB::transaction(function () use ($donasi, $nominalDiterima, $oleh, $catatan) {
            $this->kunciMenunggu($donasi);

            $donasi->update([
                'nominal_diterima'  => $nominalDiterima,
                'status'            => Donasi::DITERIMA,
                'diverifikasi_oleh' => $oleh->id,
                'diverifikasi_pada' => now(),
                'catatan_admin'     => $catatan,
            ]);

            $this->jejak($donasi, 'Dana diterima GMLS',
                'Rp ' . number_format($nominalDiterima, 0, ',', '.') . ' sudah masuk rekening GMLS'
                . ($donasi->isKePosko() ? ' dan akan dipakai untuk kebutuhan ' . $donasi->namaTujuan() . '.' : '.')
                . ($catatan ? " Catatan: {$catatan}" : ''),
                'fa-money-bill-wave', 'green', $oleh);
        });
    }

    /**
     * Barang donasi sudah sampai.
     *  - Ke posko : langsung menambah stok posko, donasi selesai.
     *  - Ke pusat : menambah stok gudang pusat, menunggu disalurkan.
     *
     * @param array<int,int> $diterima  donasi_item_id => jumlah yang benar-benar sampai
     */
    public function terimaBarang(Donasi $donasi, array $diterima, User $oleh, ?string $catatan): void
    {
        DB::transaction(function () use ($donasi, $diterima, $oleh, $catatan) {
            $this->kunciMenunggu($donasi);

            $donasi->load('items.logistik');
            $tanggal = now()->toDateString();

            foreach ($donasi->items as $item) {
                $jumlah = max(0, (int) ($diterima[$item->id] ?? 0));

                // Tidak boleh melebihi yang dijanjikan donatur, supaya stok tidak bisa digelembungkan
                if ($jumlah > $item->jumlah) {
                    throw ValidationException::withMessages([
                        'diterima' => "Jumlah {$item->logistik->nama_item} yang diterima melebihi yang dikirim donatur ({$item->jumlah}). "
                            . 'Kelebihannya catat lewat Barang Masuk.',
                    ]);
                }

                $item->update(['jumlah_diterima' => $jumlah]);

                if ($jumlah === 0) {
                    continue;
                }

                if ($donasi->isKePosko()) {
                    $this->tambahStokPosko($donasi->lokasi_id, $item->logistik_id, $jumlah, $tanggal,
                        $this->sumber($donasi), "Donasi {$donasi->kode}", $donasi->id, $oleh);

                    $donasi->penyaluran()->create([
                        'lokasi_id'   => $donasi->lokasi_id,
                        'logistik_id' => $item->logistik_id,
                        'jumlah'      => $jumlah,
                        'tanggal'     => $tanggal,
                        'keterangan'  => 'Diantar langsung oleh donatur',
                        'user_id'     => $oleh->id,
                    ]);
                } else {
                    $this->ubahStokGudang($donasi->gudang_pusat_id ?? GudangPusat::utama()->id, $item->logistik_id, $jumlah, [
                        'jenis'      => MutasiGudang::MASUK,
                        'tanggal'    => $tanggal,
                        'sumber'     => $this->sumber($donasi),
                        'donasi_id'  => $donasi->id,
                        'keterangan' => "Donasi {$donasi->kode}",
                        'user_id'    => $oleh->id,
                    ]);
                }
            }

            if ($donasi->items->sum('jumlah_diterima') === 0) {
                throw ValidationException::withMessages(['diterima' => 'Isi jumlah barang yang sampai. Jika tidak ada yang datang, pilih "Tolak".']);
            }

            $donasi->update([
                'status'            => $donasi->isKePosko() ? Donasi::SELESAI : Donasi::DITERIMA,
                'diverifikasi_oleh' => $oleh->id,
                'diverifikasi_pada' => now(),
                'catatan_admin'     => $catatan,
            ]);

            $this->jejak($donasi,
                $donasi->isKePosko() ? 'Barang diterima posko' : 'Barang diterima gudang GMLS',
                $donasi->fresh('items.logistik')->ringkasan() . ' diterima di ' . $donasi->namaTujuan()
                    . ($donasi->isKePosko() ? ' dan sudah masuk stok posko.' : '. Barang akan disalurkan ke posko yang membutuhkan.')
                    . ($catatan ? " Catatan: {$catatan}" : ''),
                'fa-box-open', 'green', $oleh, $donasi->lokasi_id);
        });
    }

    public function tolak(Donasi $donasi, User $oleh, ?string $alasan): void
    {
        DB::transaction(function () use ($donasi, $oleh, $alasan) {
            $this->kunciMenunggu($donasi);

            $donasi->update([
                'status'            => Donasi::DITOLAK,
                'diverifikasi_oleh' => $oleh->id,
                'diverifikasi_pada' => now(),
                'catatan_admin'     => $alasan,
            ]);

            $this->jejak($donasi, $donasi->isUang() ? 'Transfer belum ditemukan' : 'Barang tidak diterima',
                $alasan ?: 'Silakan hubungi GMLS dengan menyebutkan kode donasi ini.', 'fa-times-circle', 'red', $oleh);
        });
    }

    /* =========================================================
     |  PENYALURAN (admin)
     ========================================================= */

    /**
     * Mencatat pemakaian dana donasi.
     * Jika diisi barang & jumlah, barang yang dibeli otomatis masuk stok posko / gudang pusat.
     */
    public function salurkanDana(Donasi $donasi, array $data, ?UploadedFile $foto, User $oleh): PenyaluranDonasi
    {
        $this->pastikanBisaDisalurkan($donasi, Donasi::UANG);

        return DB::transaction(function () use ($donasi, $data, $foto, $oleh) {
            $donasi = Donasi::lockForUpdate()->find($donasi->id);

            if ($data['nominal'] > $donasi->sisaDana()) {
                throw ValidationException::withMessages([
                    'nominal' => 'Melebihi sisa dana donasi (Rp ' . number_format($donasi->sisaDana(), 0, ',', '.') . ').',
                ]);
            }

            $keGudang = empty($data['lokasi_id']) && ! empty($data['ke_gudang']);
            $barang   = ! empty($data['logistik_id']) && ! empty($data['jumlah']);

            $penyaluran = $donasi->penyaluran()->create([
                'lokasi_id'       => $data['lokasi_id'] ?? null,
                'gudang_pusat_id' => $keGudang ? GudangPusat::utama()?->id : null,
                'nominal'         => $data['nominal'],
                'logistik_id'     => $barang ? $data['logistik_id'] : null,
                'jumlah'          => $barang ? $data['jumlah'] : null,
                'tanggal'         => $data['tanggal'],
                'keterangan'      => $data['keterangan'] ?? null,
                'foto'            => $foto ? \App\Support\Upload::simpan($foto, 'assets/donasi/penyaluran') : null,
                'user_id'         => $oleh->id,
            ]);

            if ($barang && $penyaluran->lokasi_id) {
                $this->tambahStokPosko($penyaluran->lokasi_id, $penyaluran->logistik_id, $penyaluran->jumlah, $data['tanggal'],
                    'Dibelikan GMLS dari ' . $this->sumber($donasi), "Donasi {$donasi->kode}", $donasi->id, $oleh);
            } elseif ($barang && $penyaluran->gudang_pusat_id) {
                $this->ubahStokGudang($penyaluran->gudang_pusat_id, $penyaluran->logistik_id, $penyaluran->jumlah, [
                    'jenis'                => MutasiGudang::MASUK,
                    'tanggal'              => $data['tanggal'],
                    'sumber'               => 'Dibelikan dari ' . $this->sumber($donasi),
                    'donasi_id'            => $donasi->id,
                    'penyaluran_donasi_id' => $penyaluran->id,
                    'user_id'              => $oleh->id,
                ]);
            }

            $penyaluran->load('logistik', 'lokasi', 'gudang');
            $this->jejak($donasi,
                'Dana disalurkan ke ' . $penyaluran->namaTujuan(),
                'Rp ' . number_format($penyaluran->nominal, 0, ',', '.')
                    . ($barang ? ' dibelikan ' . $penyaluran->logistik->nama_item . ' ' . number_format($penyaluran->jumlah, 0, ',', '.') . ' ' . $penyaluran->logistik->satuan : ' dipakai')
                    . ($penyaluran->keterangan ? '. ' . $penyaluran->keterangan : '.'),
                'fa-hand-holding-usd', 'blue', $oleh, $penyaluran->lokasi_id, $penyaluran->foto);

            $this->perbaruiStatus($donasi);

            return $penyaluran;
        });
    }

    /** Mengirim barang donasi dari gudang pusat ke posko. */
    public function salurkanBarang(Donasi $donasi, array $data, ?UploadedFile $foto, User $oleh): PenyaluranDonasi
    {
        $this->pastikanBisaDisalurkan($donasi, Donasi::LOGISTIK);

        return DB::transaction(function () use ($donasi, $data, $foto, $oleh) {
            $donasi = Donasi::with('items', 'penyaluran')->lockForUpdate()->find($donasi->id);
            $sisa   = (int) ($donasi->sisaBarang()[$data['logistik_id']] ?? 0);

            if ($data['jumlah'] > $sisa) {
                throw ValidationException::withMessages(['jumlah' => "Melebihi sisa barang donasi ini ({$sisa})."]);
            }

            $penyaluran = $donasi->penyaluran()->create([
                'lokasi_id'   => $data['lokasi_id'],
                'logistik_id' => $data['logistik_id'],
                'jumlah'      => $data['jumlah'],
                'tanggal'     => $data['tanggal'],
                'keterangan'  => $data['keterangan'] ?? null,
                'foto'        => $foto ? \App\Support\Upload::simpan($foto, 'assets/donasi/penyaluran') : null,
                'user_id'     => $oleh->id,
            ]);

            $this->kirimKePosko($donasi->gudang_pusat_id ?? GudangPusat::utama()->id, $data, $oleh,
                'Dari ' . $this->sumber($donasi) . ' via GMLS Pusat', $donasi->id, $penyaluran->id);

            $penyaluran->load('logistik', 'lokasi');
            $this->jejak($donasi,
                'Barang dikirim ke ' . $penyaluran->namaTujuan(),
                $penyaluran->logistik->nama_item . ' ' . number_format($penyaluran->jumlah, 0, ',', '.') . ' ' . $penyaluran->logistik->satuan
                    . ' dikirim dari gudang GMLS' . ($penyaluran->keterangan ? '. ' . $penyaluran->keterangan : '.'),
                'fa-truck', 'blue', $oleh, $penyaluran->lokasi_id, $penyaluran->foto);

            $this->perbaruiStatus($donasi);

            return $penyaluran;
        });
    }

    /** Kabar tambahan dari admin (misalnya "Barang sedang dikemas"). */
    public function tambahKabar(Donasi $donasi, string $judul, ?string $keterangan, ?UploadedFile $foto, User $oleh): void
    {
        $this->jejak($donasi, $judul, $keterangan, 'fa-bullhorn', 'slate', $oleh, null,
            $foto ? \App\Support\Upload::simpan($foto, 'assets/donasi/penyaluran') : null);
    }

    /* =========================================================
     |  GUDANG PUSAT
     ========================================================= */

    /** Barang masuk gudang pusat di luar donasi (pengadaan, titipan instansi, dll). */
    public function barangMasukGudang(GudangPusat $gudang, array $data, User $oleh): void
    {
        DB::transaction(fn () => $this->ubahStokGudang($gudang->id, $data['logistik_id'], (int) $data['jumlah'], [
            'jenis'      => MutasiGudang::MASUK,
            'tanggal'    => $data['tanggal'],
            'sumber'     => $data['sumber'],
            'keterangan' => $data['keterangan'] ?? null,
            'user_id'    => $oleh->id,
        ]));
    }

    /** Kirim barang gudang pusat ke posko (tanpa / dengan donasi). Stok gudang berkurang, stok posko bertambah. */
    public function kirimKePosko(int $gudangId, array $data, User $oleh, string $sumber = 'GMLS Pusat', ?int $donasiId = null, ?int $penyaluranId = null): void
    {
        DB::transaction(function () use ($gudangId, $data, $oleh, $sumber, $donasiId, $penyaluranId) {
            $lokasi = Lokasi::findOrFail($data['lokasi_id']);

            $this->ubahStokGudang($gudangId, $data['logistik_id'], -(int) $data['jumlah'], [
                'jenis'                => MutasiGudang::KELUAR,
                'tanggal'              => $data['tanggal'],
                'lokasi_id'            => $lokasi->id,
                'donasi_id'            => $donasiId,
                'penyaluran_donasi_id' => $penyaluranId,
                'keterangan'           => $data['keterangan'] ?? null,
                'user_id'              => $oleh->id,
            ]);

            $this->tambahStokPosko($lokasi->id, $data['logistik_id'], (int) $data['jumlah'], $data['tanggal'],
                $sumber, $data['keterangan'] ?? null, $donasiId, $oleh);

            $barang = Logistik::find($data['logistik_id']);
            $lokasi->relawan?->user?->notify(new PemberitahuanPosko(
                'Kiriman dari GMLS Pusat',
                "{$barang->nama_item} " . number_format($data['jumlah'], 0, ',', '.') . " {$barang->satuan} dikirim ke {$lokasi->nama_lokasi} dan sudah ditambahkan ke stok posko.",
                route('logistik.index', $lokasi->id),
                'fa-truck',
                'green',
                $lokasi->id
            ));
        });
    }

    /* =========================================================
     |  INTERNAL
     ========================================================= */

    /** Tambah/kurangi stok gudang + catat mutasinya. Stok tidak boleh minus. */
    private function ubahStokGudang(int $gudangId, int $logistikId, int $selisih, array $mutasi): void
    {
        $stok = StokGudang::where('gudang_pusat_id', $gudangId)->where('logistik_id', $logistikId)->lockForUpdate()->first()
            ?? StokGudang::create(['gudang_pusat_id' => $gudangId, 'logistik_id' => $logistikId, 'jumlah_stok' => 0]);

        if ($stok->jumlah_stok + $selisih < 0) {
            $barang = Logistik::find($logistikId);
            throw ValidationException::withMessages([
                'jumlah' => "Stok {$barang->nama_item} di gudang pusat tidak cukup. Sisa: {$stok->jumlah_stok} {$barang->satuan}.",
            ]);
        }

        $stok->increment('jumlah_stok', $selisih);

        MutasiGudang::create($mutasi + [
            'gudang_pusat_id' => $gudangId,
            'logistik_id'     => $logistikId,
            'jumlah'          => abs($selisih),
        ]);
    }

    /** Sama seperti "Catat Barang Masuk" di posko: catat barang masuk + tambah stok. */
    private function tambahStokPosko(int $lokasiId, int $logistikId, int $jumlah, string $tanggal, string $sumber, ?string $keterangan, ?int $donasiId, User $oleh): void
    {
        StokMasuk::create([
            'lokasi_id'      => $lokasiId,
            'logistik_id'    => $logistikId,
            'relawan_id'     => $oleh->relawan?->id,
            'donasi_id'      => $donasiId,
            'jumlah_masuk'   => $jumlah,
            'sumber_bantuan' => $sumber,
            'tanggal_masuk'  => $tanggal,
            'keterangan'     => $keterangan,
        ]);

        $stok = StokLogistik::lockForUpdate()->firstOrCreate(
            ['lokasi_id' => $lokasiId, 'logistik_id' => $logistikId],
            ['jumlah_stok' => 0, 'status_ketersediaan' => 'Merah']
        );

        $stok->ubahJumlah($jumlah);
    }

    private function perbaruiStatus(Donasi $donasi): void
    {
        $donasi->load('items', 'penyaluran');

        $habis = $donasi->isUang()
            ? $donasi->sisaDana() === 0
            : $donasi->sisaBarang()->sum() === 0;

        $donasi->update(['status' => $habis ? Donasi::SELESAI : Donasi::SEBAGIAN]);

        if ($habis) {
            $this->jejak($donasi, 'Seluruh donasi sudah tersalurkan', 'Terima kasih atas kepedulian Anda.', 'fa-heart', 'green');
        }
    }

    private function jejak(Donasi $donasi, string $judul, ?string $keterangan, string $ikon, string $warna, ?User $oleh = null, ?int $lokasiId = null, ?string $foto = null): void
    {
        DonasiJejak::create([
            'donasi_id'  => $donasi->id,
            'judul'      => $judul,
            'keterangan' => $keterangan,
            'ikon'       => $ikon,
            'warna'      => $warna,
            'lokasi_id'  => $lokasiId,
            'foto'       => $foto,
            'user_id'    => $oleh?->id,
        ]);
    }

    private function sumber(Donasi $donasi): string
    {
        return 'Donasi ' . $donasi->namaTampil() . " ({$donasi->kode})";
    }

    /**
     * Kunci baris donasi lalu cek statusnya dari database (bukan dari objek lama), supaya
     * tombol yang terklik dua kali / dua petugas bersamaan tidak menambah stok dua kali.
     */
    private function kunciMenunggu(Donasi $donasi): void
    {
        $status = Donasi::whereKey($donasi->id)->lockForUpdate()->value('status');

        if ($status !== Donasi::MENUNGGU) {
            throw ValidationException::withMessages(['status' => 'Donasi ini sudah diproses sebelumnya.']);
        }
    }

    private function pastikanBisaDisalurkan(Donasi $donasi, string $jenis): void
    {
        if ($donasi->jenis !== $jenis || ! $donasi->bisaDisalurkan()) {
            throw ValidationException::withMessages(['status' => 'Donasi ini belum diterima atau sudah selesai disalurkan.']);
        }

        if ($jenis === Donasi::LOGISTIK && $donasi->isKePosko()) {
            throw ValidationException::withMessages(['status' => 'Barang donasi ini sudah langsung masuk ke posko tujuan.']);
        }
    }
}
