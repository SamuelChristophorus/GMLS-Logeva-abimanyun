<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Petugas lapangan dari aplikasi offline (Paket Data Posko → petugas.csv).
 * admin = penanggung jawab/supervisor di HP posko, relawan = petugas posko.
 * Hanya nama & kontak; bukan akun website.
 */
class PetugasPosko extends Model
{
    public const ADMIN = 'admin';
    public const RELAWAN = 'relawan';

    protected $fillable = [
        'lokasi_id',
        'kode_perangkat',
        'kode_akun',
        'peran',
        'nama',
        'kontak',
        'paket_dibuat_pada',
    ];

    protected $casts = [
        'paket_dibuat_pada' => 'datetime',
    ];

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class);
    }

    public function labelPeran(): string
    {
        return $this->peran === self::ADMIN ? 'Admin (penanggung jawab)' : 'Relawan';
    }
}
