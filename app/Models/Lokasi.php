<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lokasi extends Model
{
    use SoftDeletes;

    // Nilai status operasional
    public const OPS_SIAGA      = 'siaga';
    public const OPS_BEROPERASI = 'beroperasi';
    public const OPS_SELESAI    = 'selesai';

    protected $fillable = [
        'desa_id',
        'relawan_id',
        'nama_lokasi',
        'alamat_lokasi',
        'luas_lokasi',
        'kapasitas_pengungsi',
        'gambar_lokasi',
        'latitude',
        'longitude',
        // --- baru: status operasional ---
        'status_operasional',
        'mulai_beroperasi_at',
        'selesai_beroperasi_at',
    ];

    protected $casts = [
        'mulai_beroperasi_at'   => 'datetime',
        'selesai_beroperasi_at' => 'datetime',
    ];

    /* =========================================================
     |  RELASI (tidak diubah)
     ========================================================= */

    public function relawan()
    {
        return $this->belongsTo(Relawan::class);
    }

    public function desa()
    {
        return $this->belongsTo(Desa::class);
    }

    public function statusLokasi()
    {
        return $this->hasMany(StatusLokasi::class);
    }

    public function sphereLokasi()
    {
        return $this->hasOne(SphereLokasi::class);
    }

    public function stokLogistik()
    {
        return $this->hasMany(StokLogistik::class);
    }

    public function riwayatPengungsi()
    {
        return $this->hasMany(RiwayatPengungsi::class);
    }

    public function pengungsi()
    {
        return $this->hasMany(Pengungsi::class);
    }

    /** Pengungsi yang sudah keluar posko (pulang / pindah / dirujuk). */
    public function pengungsiKeluar()
    {
        return $this->hasMany(Pengungsi::class)
            ->withoutGlobalScope('di_posko')
            ->where('status_keberadaan', '!=', Pengungsi::DI_POSKO)
            ->orderByDesc('keluar_pada');
    }

    /** Petugas lapangan dari aplikasi offline (admin dulu, lalu relawan). */
    public function petugas()
    {
        return $this->hasMany(PetugasPosko::class)->orderBy('peran')->orderBy('nama');
    }

    /**
     * Petugas tanpa dobel: satu posko bisa mengirim paket dari beberapa HP
     * (HP Induk & Anggota) dengan akun yang sama. Setiap akun (kode_akun, atau
     * peran + nama + kontak untuk paket lama) ditampilkan sekali, dari paket terbaru.
     */
    public function petugasUnik()
    {
        return $this->petugas
            ->sortByDesc('paket_dibuat_pada')
            ->unique(fn ($t) => $t->kode_akun ?: $t->peran . '|' . mb_strtolower($t->nama) . '|' . $t->kontak)
            ->sortBy(fn ($t) => $t->peran . '|' . mb_strtolower($t->nama))
            ->values();
    }

    /** Paket Data Posko yang pernah digabung untuk posko ini. */
    public function riwayatPaket()
    {
        return $this->belongsToMany(RiwayatImportPaket::class);
    }

    /**
     * Waktu data posko ini terakhir dibuat di HP (dari paket terbaru), atau null jika
     * posko belum pernah mengirim paket. Memakai hasil loadMax() jika sudah dimuat.
     */
    public function paketTerakhirPada(): ?\Carbon\CarbonInterface
    {
        $kolom = 'riwayat_paket_max_paket_dibuat_pada';
        $nilai = array_key_exists($kolom, $this->attributes)
            ? $this->attributes[$kolom]
            : $this->riwayatPaket()->max('paket_dibuat_pada');

        return $nilai ? \Illuminate\Support\Carbon::parse($nilai) : null;
    }

    public function permintaanOperasional()
    {
        return $this->hasMany(PermintaanOperasional::class);
    }

    /** Permintaan mulai/tutup operasi dari relawan yang belum diproses admin (maks. satu). */
    public function permintaanMenunggu()
    {
        return $this->hasOne(PermintaanOperasional::class)
            ->ofMany(['id' => 'max'], fn ($q) => $q->where('status', PermintaanOperasional::MENUNGGU));
    }

    public function latestStatus()
    {
        return $this->hasOne(StatusLokasi::class)
            ->latestForLokasi();
    }

    public function latestStatusView()
    {
        return $this->hasOne(StatusLokasi::class)->latestOfMany();
    }

    /* =========================================================
     |  BARU: STATUS OPERASIONAL
     ========================================================= */

    /** Laporan lokasi ditolak admin (status persetujuan terakhir = rejected). */
    public function isDitolak(): bool
    {
        return optional($this->latestStatusView)->status === 'rejected';
    }

    /**
     * Lokasi yang boleh tampil di halaman publik: semua kecuali yang ditolak admin.
     * Panel admin tidak memakai scope ini sehingga tetap melihat lokasi yang ditolak.
     */
    public function scopeTampilPublik($query)
    {
        return $query->whereNotIn('lokasis.id', function ($ditolak) {
            $ditolak->select('lokasi_id')
                ->from('status_lokasis')
                ->where('status', 'rejected')
                ->whereIn('id', function ($terakhir) {
                    $terakhir->selectRaw('MAX(id)')
                        ->from('status_lokasis')
                        ->whereNull('deleted_at')
                        ->groupBy('lokasi_id');
                });
        });
    }

    /** Lokasi sudah disetujui admin (status persetujuan terakhir = approved). */
    public function isApproved(): bool
    {
        return optional($this->latestStatusView)->status === 'approved';
    }

    /** Posko sedang aktif menampung pengungsi. */
    public function isBeroperasi(): bool
    {
        return $this->status_operasional === self::OPS_BEROPERASI;
    }

    /** Lama posko beroperasi dalam hari (hari pertama = 1). */
    public function hariOperasi(): ?int
    {
        if (! $this->isBeroperasi() || ! $this->mulai_beroperasi_at) {
            return null;
        }

        return (int) $this->mulai_beroperasi_at->copy()->startOfDay()->diffInDays(now()->startOfDay()) + 1;
    }

    /**
     * Apakah user boleh mengubah status operasional posko ini:
     * admin, atau relawan penanggung jawab posko ini.
     */
    public function bisaDikelolaOleh($user): bool
    {
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            return true;
        }

        return $this->relawan && (int) $this->relawan->user_id === (int) $user->id;
    }

    /** Label & warna untuk ditampilkan di tampilan. */
    public function labelOperasional(): array
    {
        return match ($this->status_operasional) {
            self::OPS_BEROPERASI => ['label' => 'Beroperasi', 'warna' => 'green'],
            self::OPS_SELESAI    => ['label' => 'Selesai',    'warna' => 'slate'],
            default              => ['label' => 'Siaga',      'warna' => 'amber'],
        };
    }
}