<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/** Satu Paket Data Posko yang sudah digabung (menu Import Paket Posko). */
class RiwayatImportPaket extends Model
{
    protected $fillable = [
        'user_id',
        'nama_file',
        'kode_perangkat',
        'peran_perangkat',
        'diekspor_oleh',
        'paket_dibuat_pada',
        'jumlah',
    ];

    protected $casts = [
        'paket_dibuat_pada' => 'datetime',
        'jumlah'            => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lokasis()
    {
        return $this->belongsToMany(Lokasi::class)->withTrashed();
    }

    /** Barang yang stok HP-nya berbeda dengan stok pusat setelah paket ini digabung. */
    public function selisihStok()
    {
        return $this->hasMany(SelisihStokPaket::class);
    }

    public function labelPerangkat(): string
    {
        return match ($this->peran_perangkat) {
            'induk'   => 'HP Induk (supervisor)',
            'anggota' => 'HP Anggota (relawan)',
            default   => 'HP posko',
        };
    }

    /** "hari ini" / "kemarin" / "3 hari lalu" (berdasarkan tanggal kalender WIB). */
    public static function teksUmur(?CarbonInterface $waktu): ?string
    {
        if (! $waktu) return null;
        $hari = (int) $waktu->copy()->startOfDay()->diffInDays(now()->startOfDay(), false);

        return match (true) {
            $hari <= 0 => 'hari ini',
            $hari === 1 => 'kemarin',
            default => "{$hari} hari lalu",
        };
    }

    /** Warna tanda umur paket: hijau ≤ 1 hari, kuning ≤ 3 hari, merah lebih lama. */
    public static function warnaUmur(?CarbonInterface $waktu): string
    {
        if (! $waktu) return 'slate';
        $hari = (int) $waktu->copy()->startOfDay()->diffInDays(now()->startOfDay(), false);

        return $hari <= 1 ? 'emerald' : ($hari <= 3 ? 'amber' : 'rose');
    }
}
