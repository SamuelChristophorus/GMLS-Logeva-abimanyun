<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DonasiItem extends Model
{
    protected $fillable = [
        'donasi_id',
        'logistik_id',
        'jumlah',
        'jumlah_diterima',
    ];

    public function donasi()
    {
        return $this->belongsTo(Donasi::class);
    }

    public function logistik()
    {
        return $this->belongsTo(Logistik::class);
    }
}
