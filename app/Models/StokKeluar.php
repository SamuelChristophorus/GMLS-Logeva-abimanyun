<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StokKeluar extends Model
{
    /** Pilihan "untuk apa barang dikeluarkan": kunci => [label, ikon Font Awesome] */
    public const TUJUAN = [
        'dibagikan'  => ['Dibagikan ke pengungsi',      'fa-hand-holding-heart'],
        'dapur_umum' => ['Dimasak di dapur umum',       'fa-fire-burner'],
        'dikirim'    => ['Dikirim ke posko lain',       'fa-truck'],
        'rusak'      => ['Rusak / kedaluwarsa / hilang', 'fa-trash-can'],
        'lainnya'    => ['Lainnya',                     'fa-ellipsis'],
    ];

    protected $fillable = [
        'lokasi_id',
        'logistik_id',
        'user_id',
        'jumlah_keluar',
        'tujuan',
        'penerima',
        'tanggal_keluar',
        'keterangan',
    ];

    protected $casts = [
        'tanggal_keluar' => 'date',
    ];

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class);
    }

    public function logistik()
    {
        return $this->belongsTo(Logistik::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function labelTujuan(): string
    {
        return self::TUJUAN[$this->tujuan][0] ?? ucfirst($this->tujuan);
    }
}
