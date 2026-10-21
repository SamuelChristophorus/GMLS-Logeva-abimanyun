<?php

/*
|--------------------------------------------------------------------------
| index.php — SATU VERSI untuk localhost, Hostinger & InfinityFree
|--------------------------------------------------------------------------
| Struktur Hostinger:
|   public_html/logeva/      <- isi folder public/ (file ini ada di sini)
|   logeva_app/              <- seluruh aplikasi Laravel (app, routes, vendor, dst.)
|
| Struktur InfinityFree (hanya boleh upload ke dalam htdocs):
|   htdocs/                  <- isi folder public/ (file ini ada di sini)
|   htdocs/logeva_app/       <- seluruh aplikasi Laravel; akses dari browser
|                               DIBLOKIR oleh .htaccess di htdocs
|
| Struktur localhost:
|   GMLS-Logeva-abimanyun/public/   <- file ini
|   GMLS-Logeva-abimanyun/          <- aplikasi Laravel
|
| File ini mendeteksi sendiri struktur mana yang dipakai, jadi TIDAK PERLU diedit
| saat dipindah server.
*/

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$base = match (true) {
    is_file(__DIR__.'/../../logeva_app/vendor/autoload.php') => __DIR__.'/../../logeva_app', // Hostinger
    is_file(__DIR__.'/logeva_app/vendor/autoload.php')       => __DIR__.'/logeva_app',       // InfinityFree
    default                                                  => __DIR__.'/..',               // localhost
};

if (file_exists($maintenance = $base.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $base.'/vendor/autoload.php';

$app = require_once $base.'/bootstrap/app.php';

// Folder public = folder tempat file ini berada (public/ di laptop, public_html/logeva/ di Hostinger)
if (method_exists($app, 'usePublicPath')) {
    $app->usePublicPath(__DIR__);
} else {
    $app->bind('path.public', fn () => __DIR__);
}

if (method_exists($app, 'handleRequest')) {
    $app->handleRequest(Request::capture());          // Laravel 11+
} else {
    $kernel = $app->make(Kernel::class);               // Laravel 10-
    $response = $kernel->handle($request = Request::capture())->send();
    $kernel->terminate($request, $response);
}
