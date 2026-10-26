<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SphereLokasi extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'lokasi_id',
        'air_hidup',
        'air_kebersihan',
        'air_memasak',
        'toilet_pendek',
        'toilet_panjang',
        'kalori',
        'protein',
        'lemak'
    ];

    public function lokasi(){
        return $this->belongsTo(Lokasi::class);
    }

    /**
     * Hitung & simpan kebutuhan SPHERE harian untuk seluruh kapasitas posko.
     * Rumus sama persis dengan form Tambah Lokasi (pages/admin/lokasi/create.blade.php):
     * air hidup 2,5 L, kebersihan 2 L, memasak 3 L per orang,
     * toilet = round(kapasitas / 50) & round(kapasitas / 20),
     * kalori 2.100 kkal, protein 53 g, lemak 40 g per orang.
     * Dipakai import Lokasi dan import Paket Data Posko.
     */
    public static function hitungUntuk(Lokasi $lokasi): void
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

        $sphere = static::where('lokasi_id', $lokasi->id)->first() ?? new static;
        $sphere->forceFill(['lokasi_id' => $lokasi->id] + $nilai)->save();
    }
}
