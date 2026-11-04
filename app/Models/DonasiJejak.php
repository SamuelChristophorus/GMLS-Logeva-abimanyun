<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Satu langkah di jejak donasi (ditampilkan ke donatur di halaman Lacak Donasi). */
class DonasiJejak extends Model
{
    protected $fillable = [
        'donasi_id',
        'judul',
        'keterangan',
        'ikon',
        'warna',
        'lokasi_id',
        'foto',
        'user_id',
    ];

    public function donasi()
    {
        return $this->belongsTo(Donasi::class);
    }

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class)->withTrashed();
    }
}
