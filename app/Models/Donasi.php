<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Donasi dari donatur: UANG (transfer ke rekening GMLS) atau LOGISTIK (barang dikirim
 * sendiri oleh donatur), untuk GMLS PUSAT atau langsung ke POSKO tertentu.
 *
 * Alur status:
 *   menunggu  -> donatur sudah mengisi form; uang menunggu dicek, barang dalam perjalanan
 *   diterima  -> uang sudah masuk rekening / barang sudah sampai, belum disalurkan
 *   sebagian  -> sebagian sudah disalurkan ke posko
 *   selesai   -> semua sudah disalurkan (logistik langsung ke posko = selesai saat diterima)
 *   ditolak   -> transfer tidak ditemukan / barang tidak datang
 */
class Donasi extends Model
{
    public const UANG     = 'uang';
    public const LOGISTIK = 'logistik';

    public const KE_PUSAT = 'pusat';
    public const KE_POSKO = 'posko';

    public const MENUNGGU = 'menunggu';
    public const DITERIMA = 'diterima';
    public const SEBAGIAN = 'sebagian';
    public const SELESAI  = 'selesai';
    public const DITOLAK  = 'ditolak';

    /** status => [label, warna, ikon] */
    public const STATUS = [
        self::MENUNGGU => ['Menunggu konfirmasi', 'amber', 'fa-hourglass-half'],
        self::DITERIMA => ['Diterima GMLS',       'blue',  'fa-box-open'],
        self::SEBAGIAN => ['Sebagian disalurkan', 'blue',  'fa-truck-loading'],
        self::SELESAI  => ['Sudah disalurkan',    'green', 'fa-check-circle'],
        self::DITOLAK  => ['Tidak terverifikasi', 'red',   'fa-times-circle'],
    ];

    protected $fillable = [
        'kode',
        'nama_donatur',
        'anonim',
        'kontak',
        'jenis',
        'tujuan',
        'lokasi_id',
        'gudang_pusat_id',
        'nominal',
        'nominal_diterima',
        'bukti_transfer',
        'perkiraan_tiba',
        'pengirim',
        'pesan',
        'status',
        'diverifikasi_oleh',
        'diverifikasi_pada',
        'catatan_admin',
    ];

    protected $casts = [
        'anonim'            => 'boolean',
        'perkiraan_tiba'    => 'date',
        'diverifikasi_pada' => 'datetime',
    ];

    /* ================= RELASI ================= */

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class)->withTrashed();
    }

    public function gudang()
    {
        return $this->belongsTo(GudangPusat::class, 'gudang_pusat_id');
    }

    public function items()
    {
        return $this->hasMany(DonasiItem::class);
    }

    public function penyaluran()
    {
        return $this->hasMany(PenyaluranDonasi::class)->orderByDesc('tanggal')->orderByDesc('id');
    }

    public function jejak()
    {
        return $this->hasMany(DonasiJejak::class)->orderBy('created_at')->orderBy('id');
    }

    public function verifikator()
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    /* ================= SCOPE ================= */

    /** Donasi yang sudah benar-benar diterima (dihitung di laporan publik). */
    public function scopeTerverifikasi($query)
    {
        return $query->whereIn('status', [self::DITERIMA, self::SEBAGIAN, self::SELESAI]);
    }

    /* ================= BANTUAN ================= */

    public static function buatKode(): string
    {
        // Tanpa huruf/angka yang mirip (0/O, 1/I/L) supaya mudah dibacakan lewat telepon
        do {
            $kode = 'DN-' . collect(range(1, 8))
                ->map(fn () => Str::of('ABCDEFGHJKMNPQRSTUVWXYZ23456789')->substr(random_int(0, 30), 1))
                ->implode('');
        } while (static::where('kode', $kode)->exists());

        return $kode;
    }

    public function isUang(): bool
    {
        return $this->jenis === self::UANG;
    }

    public function isKePosko(): bool
    {
        return $this->tujuan === self::KE_POSKO;
    }

    public function isMenunggu(): bool
    {
        return $this->status === self::MENUNGGU;
    }

    /** Sudah diterima dan masih bisa disalurkan. */
    public function bisaDisalurkan(): bool
    {
        return in_array($this->status, [self::DITERIMA, self::SEBAGIAN], true);
    }

    /** Nama yang tampil di halaman publik. */
    public function namaTampil(): string
    {
        return $this->anonim ? 'Donatur Anonim' : $this->nama_donatur;
    }

    public function namaTujuan(): string
    {
        return $this->isKePosko()
            ? ($this->lokasi->nama_lokasi ?? 'Posko (sudah dihapus)')
            : ($this->gudang->nama ?? 'GMLS Pusat');
    }

    public function labelStatus(): array
    {
        [$label, $warna, $ikon] = self::STATUS[$this->status] ?? [ucfirst($this->status), 'slate', 'fa-circle'];

        return compact('label', 'warna', 'ikon');
    }

    /** Kontak disamarkan, contoh: 0812****789 / bu****@gmail.com */
    public function kontakSamaran(): string
    {
        $k = $this->kontak;

        if (str_contains($k, '@')) {
            [$nama, $domain] = explode('@', $k, 2);
            return Str::substr($nama, 0, 2) . '****@' . $domain;
        }

        return Str::length($k) > 7 ? Str::substr($k, 0, 4) . '****' . Str::substr($k, -3) : '****';
    }

    /** Dana (donasi uang) yang belum dipakai. */
    public function sisaDana(): int
    {
        $terpakai = $this->relationLoaded('penyaluran')
            ? $this->penyaluran->sum('nominal')
            : $this->penyaluran()->sum('nominal');

        return max(0, (int) $this->nominal_diterima - (int) $terpakai);
    }

    /**
     * Sisa barang per logistik_id (donasi logistik ke pusat) yang belum dikirim ke posko.
     *
     * @return \Illuminate\Support\Collection<int, int>  logistik_id => sisa
     */
    public function sisaBarang()
    {
        $terkirim = $this->penyaluran->groupBy('logistik_id')->map->sum('jumlah');

        return $this->items
            ->groupBy('logistik_id')
            ->map(fn ($baris, $id) => max(0, (int) $baris->sum('jumlah_diterima') - (int) ($terkirim[$id] ?? 0)));
    }

    /** Ringkas isi donasi, contoh: "Rp 500.000" atau "Beras 50 kg, Air Mineral 20 dus". */
    public function ringkasan(): string
    {
        if ($this->isUang()) {
            return 'Rp ' . number_format($this->nominal_diterima ?? $this->nominal, 0, ',', '.');
        }

        return $this->items
            ->map(fn ($i) => ($i->logistik->nama_item ?? 'Barang') . ' '
                . number_format($i->jumlah_diterima ?? $i->jumlah, 0, ',', '.') . ' ' . ($i->logistik->satuan ?? ''))
            ->implode(', ');
    }
}
