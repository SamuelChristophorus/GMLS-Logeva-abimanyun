<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Relawan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'avatar'
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function lokasi(){
        return $this->hasMany(Lokasi::class);
    }

    /** Posko tempat relawan bertugas (satu relawan = satu posko). */
    public function poskoTugas(): ?Lokasi
    {
        return $this->lokasi()->orderByDesc('updated_at')->first();
    }

    /**
     * Tugaskan relawan ini sebagai penanggung jawab posko [$posko].
     * Aturan: posko WAJIB punya penanggung jawab, satu relawan = satu posko.
     *  - Relawan lama di [$posko] (B) bertukar tempat: B menjadi penanggung
     *    jawab posko asal relawan ini, supaya tidak ada posko yang kosong.
     *  - Relawan ini belum punya posko: B menjadi "belum ditugaskan".
     *
     * @return array{ditukar: ?array{nama: string, posko: string}, dilepas: ?string}
     */
    public function tugaskanKe(Lokasi $posko): array
    {
        return DB::transaction(function () use ($posko) {
            if ((int) $posko->relawan_id === (int) $this->id) {
                return ['ditukar' => null, 'dilepas' => null];
            }
            $lama = $posko->relawan;                       // B (bisa null: data rusak)
            $asal = $this->lokasi()->where('id', '!=', $posko->id)->orderByDesc('updated_at')->first();

            $posko->relawan_id = $this->id;
            $posko->save();

            if ($asal && $lama) {
                $asal->relawan_id = $lama->id;
                $asal->save();
                return ['ditukar' => ['nama' => $lama->user?->name ?? 'Relawan', 'posko' => $asal->nama_lokasi], 'dilepas' => null];
            }
            return ['ditukar' => null, 'dilepas' => $lama?->user?->name];
        });
    }

    /** Ringkasan perubahan penugasan untuk pesan ke admin. */
    public static function pesanPenugasan(string $nama, Lokasi $posko, array $hasil): string
    {
        $pesan = "{$nama} bertugas di {$posko->nama_lokasi}.";
        if ($hasil['ditukar']) {
            $pesan .= " {$hasil['ditukar']['nama']} pindah ke {$hasil['ditukar']['posko']} (bertukar posko).";
        } elseif ($hasil['dilepas']) {
            $pesan .= " {$hasil['dilepas']} kini belum ditugaskan.";
        }
        return $pesan;
    }
}
