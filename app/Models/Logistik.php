<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Logistik extends Model
{
    protected $fillable = [
        'kategori_logistik_id',
        'nama_item',
        'satuan',
        'kebutuhan_harian',
        'deskripsi'
    ];

    public function kategori()
    {
        return $this->belongsTo(KategoriLogistik::class, 'kategori_logistik_id');
    }

    public function stokLogistiks()
    {
        return $this->hasMany(StokLogistik::class);
    }
}