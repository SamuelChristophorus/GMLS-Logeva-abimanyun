<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StokGudang extends Model
{
    protected $fillable = [
        'gudang_pusat_id',
        'logistik_id',
        'jumlah_stok',
    ];

    public function gudang()
    {
        return $this->belongsTo(GudangPusat::class, 'gudang_pusat_id');
    }

    public function logistik()
    {
        return $this->belongsTo(Logistik::class);
    }
}
