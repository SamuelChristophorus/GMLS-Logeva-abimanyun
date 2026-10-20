<?php

/*
|--------------------------------------------------------------------------
| Pengaturan khusus aplikasi GMLS Logeva
|--------------------------------------------------------------------------
| Nilai .env dibaca DI SINI (bukan langsung di controller) agar tetap terbaca
| setelah "php artisan config:cache" dijalankan di Hostinger.
*/

return [

    // Password awal relawan yang dibuat lewat Import Excel (panel admin).
    // Ubah lewat .env: IMPORT_RELAWAN_PASSWORD=...
    'import_relawan_password' => env('IMPORT_RELAWAN_PASSWORD', 'relawan123'),

    // Kekurangan logistik posko dihitung agar stok cukup untuk sekian hari ke depan.
    // Ubah lewat .env: TARGET_HARI_BANTUAN=...
    'target_hari_bantuan' => (int) env('TARGET_HARI_BANTUAN', 7),

];
