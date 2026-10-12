<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Logistik;

class LogistikSeeder extends Seeder
{
    public function run(): void
    {
        Logistik::firstOrCreate(
            ['nama_item' => 'Beras'],
            [
                'kategori_logistik_id' => 1,
                'satuan' => 'kg',
            ]
        );

        Logistik::firstOrCreate(
            ['nama_item' => 'Air Mineral'],
            [
                'kategori_logistik_id' => 2,
                'satuan' => 'liter',
            ]
        );
    }
}