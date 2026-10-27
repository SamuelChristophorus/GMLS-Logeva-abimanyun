<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/**
 * Pengungsi. Yang sudah keluar posko (pulang / pindah / dirujuk, dari aplikasi
 * offline) tetap disimpan tapi DISEMBUNYIKAN dari query biasa (global scope),
 * jadi tidak ikut dihitung untuk kapasitas, kebutuhan, dan komposisi.
 * Lihat semua: Pengungsi::semuaKeberadaan().
 */
class Pengungsi extends Model
{
    public const DI_POSKO = 'di_posko';
    public const KEBERADAAN = [
        'di_posko' => 'Di posko',
        'pulang'   => 'Pulang ke rumah',
        'pindah'   => 'Pindah ke posko lain',
        'dirujuk'  => 'Dirujuk ke RS / Puskesmas',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('di_posko', fn (Builder $q) =>
            $q->where($q->getModel()->getTable() . '.status_keberadaan', self::DI_POSKO));
    }

    /** Termasuk yang sudah keluar posko (untuk import paket & riwayat). */
    public static function semuaKeberadaan(): Builder
    {
        return static::withoutGlobalScope('di_posko');
    }

    /** Query DB::table yang hanya menghitung pengungsi yang masih di posko. */
    public static function tabelDiPosko(): QueryBuilder
    {
        return DB::table('pengungsis')->where('pengungsis.status_keberadaan', self::DI_POSKO);
    }

    public function labelKeberadaan(): string
    {
        return self::KEBERADAAN[$this->status_keberadaan] ?? (string) $this->status_keberadaan;
    }

    protected $fillable = [
        'lokasi_id',
        'foto',
        'nama',
        'asal',
        'tanggal_lahir',
        'nomor_kk',
        'usia',
        'jenis_kelamin',
        'kondisi_kesehatan',
        'kelompok_rentan',
        'riwayat_penyakit'
    ];

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class);
    }
}