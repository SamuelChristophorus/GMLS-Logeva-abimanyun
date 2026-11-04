<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Ke mana donasi disalurkan: dana dipakai untuk apa / barang dikirim ke posko mana. */
class PenyaluranDonasi extends Model
{
    protected $fillable = [
        'donasi_id',
        'lokasi_id',
        'gudang_pusat_id',
        'nominal',
        'logistik_id',
        'jumlah',
        'tanggal',
        'keterangan',
        'foto',
        'user_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function donasi()
    {
        return $this->belongsTo(Donasi::class);
    }

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class)->withTrashed();
    }

    public function gudang()
    {
        return $this->belongsTo(GudangPusat::class, 'gudang_pusat_id');
    }

    public function logistik()
    {
        return $this->belongsTo(Logistik::class);
    }

    public function namaTujuan(): string
    {
        return $this->lokasi->nama_lokasi ?? $this->gudang->nama ?? 'Operasional GMLS';
    }
}
