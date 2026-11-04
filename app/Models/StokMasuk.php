<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StokMasuk extends Model
{
    protected $fillable = [
        'lokasi_id',
        'logistik_id',
        'relawan_id',
        'donasi_id',
        'jumlah_masuk',
        'sumber_bantuan',
        'tanggal_masuk',
        'keterangan'
    ];

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class);
    }

    public function logistik()
    {
        return $this->belongsTo(Logistik::class);
    }

    public function donasi()
    {
        return $this->belongsTo(Donasi::class);
    }

    public function relawan()
    {
        return $this->belongsTo(Relawan::class);
    }
}