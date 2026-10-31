<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Kata sandi posko yang pernah berhasil membuka paket. Disimpan TERENKRIPSI
 * (APP_KEY) dan dicoba otomatis untuk paket berikutnya, jadi admin cukup
 * mengetik kata sandi posko sekali.
 */
class KataSandiPaket extends Model
{
    protected $fillable = ['kata_sandi'];

    protected $casts = ['kata_sandi' => 'encrypted'];

    /** @return string[] */
    public static function semua(): array
    {
        return static::latest('id')->get()->pluck('kata_sandi')->all();
    }

    public static function ingat(string $kataSandi): void
    {
        if (! in_array($kataSandi, static::semua(), true)) {
            static::create(['kata_sandi' => $kataSandi]);
        }
    }
}
