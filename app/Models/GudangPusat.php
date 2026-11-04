<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Gudang / posko milik pusat (GMLS). Menampung donasi yang ditujukan ke pusat
 * sebelum dikirim ke posko. Tidak dihitung sebagai posko pengungsian.
 */
class GudangPusat extends Model
{
    protected $fillable = [
        'nama',
        'alamat',
        'latitude',
        'longitude',
        'penanggung_jawab',
        'kontak',
        'nama_bank',
        'nomor_rekening',
        'atas_nama_rekening',
        'keterangan',
    ];

    public function stok()
    {
        return $this->hasMany(StokGudang::class);
    }

    public function mutasi()
    {
        return $this->hasMany(MutasiGudang::class);
    }

    /** Gudang pusat utama (yang pertama dibuat). */
    public static function utama(): ?self
    {
        return static::orderBy('id')->first();
    }

    public function punyaRekening(): bool
    {
        return filled($this->nomor_rekening);
    }
}
