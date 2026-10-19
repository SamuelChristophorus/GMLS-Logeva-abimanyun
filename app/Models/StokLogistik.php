<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StokLogistik extends Model
{
    protected $fillable = [
        'lokasi_id',
        'logistik_id',
        'jumlah_stok',
        'status_ketersediaan'
    ];

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class);
    }

    public function logistik()
    {
        return $this->belongsTo(Logistik::class);
    }

    /**
     * Menambah (jumlah positif, barang masuk) atau mengurangi (negatif, barang keluar) stok,
     * lalu memperbarui status ketersediaan. Dipakai bersama oleh barang masuk & keluar.
     */
    public function ubahJumlah(int $selisih): void
    {
        $this->jumlah_stok += $selisih;

        $this->status_ketersediaan = match (true) {
            $this->jumlah_stok > 100 => 'Hijau',
            $this->jumlah_stok > 0   => 'Kuning',
            default                  => 'Merah',
        };

        $this->save();
    }
}