<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Riwayat barang masuk / keluar gudang pusat. */
class MutasiGudang extends Model
{
    public const MASUK  = 'masuk';
    public const KELUAR = 'keluar';

    protected $fillable = [
        'gudang_pusat_id',
        'logistik_id',
        'jenis',
        'jumlah',
        'tanggal',
        'sumber',
        'lokasi_id',
        'donasi_id',
        'penyaluran_donasi_id',
        'keterangan',
        'user_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function logistik()
    {
        return $this->belongsTo(Logistik::class);
    }

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class)->withTrashed();
    }

    public function donasi()
    {
        return $this->belongsTo(Donasi::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
