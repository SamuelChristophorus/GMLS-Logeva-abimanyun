<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Stok satu barang di HP berbeda dengan stok pusat setelah paket digabung. */
class SelisihStokPaket extends Model
{
    protected $fillable = [
        'lokasi_id',
        'nama_item',
        'satuan',
        'stok_hp',
        'stok_pusat',
    ];

    protected $casts = [
        'stok_hp'    => 'integer',
        'stok_pusat' => 'integer',
    ];

    public function riwayat()
    {
        return $this->belongsTo(RiwayatImportPaket::class, 'riwayat_import_paket_id');
    }

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class)->withTrashed();
    }

    /**
     * Selisih yang masih berlaku: hanya dari paket TERBARU (waktu dibuat di HP) tiap
     * posko. Paket yang lebih baru menggantikan selisih dari paket sebelumnya.
     *
     * @param  int|null  $lokasiId  batasi ke satu posko
     */
    public static function terkini(?int $lokasiId = null): Collection
    {
        $terbaru = DB::table('lokasi_riwayat_import_paket as p')
            ->join('riwayat_import_pakets as r', 'r.id', '=', 'p.riwayat_import_paket_id')
            ->when($lokasiId, fn ($q) => $q->where('p.lokasi_id', $lokasiId))
            ->get(['p.lokasi_id', 'r.id', 'r.paket_dibuat_pada'])
            ->groupBy('lokasi_id')
            ->map(fn ($baris) => (int) $baris->sortByDesc(fn ($b) => [(string) $b->paket_dibuat_pada, (int) $b->id])->first()->id);

        if ($terbaru->isEmpty()) return collect();

        return static::with(['lokasi', 'riwayat.user'])
            ->whereIn('riwayat_import_paket_id', $terbaru->values()->unique())
            ->get()
            ->filter(fn ($s) => ($terbaru[$s->lokasi_id] ?? 0) === (int) $s->riwayat_import_paket_id)
            ->sortBy(fn ($s) => mb_strtolower(($s->lokasi->nama_lokasi ?? '') . '|' . $s->nama_item))
            ->values();
    }

    /** Positif = HP mencatat lebih banyak dari pusat. */
    public function selisih(): int
    {
        return $this->stok_hp - $this->stok_pusat;
    }

    /** Dugaan penyebab untuk membantu admin menyelidiki. */
    public function dugaan(): string
    {
        if ($this->stok_hp < 0) {
            return 'Stok di HP minus: barang keluar dicatat di HP Anggota sebelum barang masuknya disinkron. Minta posko menyinkronkan semua HP lalu kirim paket baru.';
        }
        if ($this->stok_hp > $this->stok_pusat) {
            return 'Pusat lebih sedikit: ada barang keluar yang dicatat langsung di website untuk posko ini.';
        }
        return 'Pusat lebih banyak: ada barang masuk yang dicatat langsung di website, paket ini dari HP yang belum menerima semua catatan (belum sinkron), atau barang keluar melebihi stok pusat (stok pusat tidak dibuat minus).';
    }
}
