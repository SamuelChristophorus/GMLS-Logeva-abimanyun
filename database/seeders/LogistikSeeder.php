<?php

namespace Database\Seeders;

use App\Services\GabungLogistikService;
use Illuminate\Database\Seeder;

/** Master Logistik baku (sama dengan bawaan aplikasi offline). */
class LogistikSeeder extends Seeder
{
    public function run(): void
    {
        app(GabungLogistikService::class)->standarkan();
    }
}
