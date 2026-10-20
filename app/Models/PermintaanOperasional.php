<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermintaanOperasional extends Model
{
    public const MENUNGGU  = 'menunggu';
    public const DISETUJUI = 'disetujui';
    public const DITOLAK   = 'ditolak';

    protected $fillable = [
        'lokasi_id',
        'user_id',
        'aksi',
        'status',
        'alasan',
        'catatan_admin',
        'diproses_oleh',
        'diproses_at',
    ];

    protected $casts = [
        'diproses_at' => 'datetime',
    ];

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class);
    }

    public function peminta()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeMenunggu($query)
    {
        return $query->where('status', self::MENUNGGU);
    }

    /** "mulai operasi" / "penutupan operasi" */
    public function labelAksi(): string
    {
        return $this->aksi === 'mulai' ? 'mulai operasi' : 'penutupan operasi';
    }
}
