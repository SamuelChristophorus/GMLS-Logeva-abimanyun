<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Nama lain sebuah barang di Master Logistik (biasanya nama barang yang sudah
 * DIGABUNG). Paket Data Posko yang masih memakai nama ini otomatis dicatat ke
 * barang tujuan, jadi tidak muncul barang ganda lagi.
 */
class AliasLogistik extends Model
{
    protected $fillable = ['nama_alias', 'kunci', 'logistik_id'];

    public function logistik()
    {
        return $this->belongsTo(Logistik::class);
    }
}
