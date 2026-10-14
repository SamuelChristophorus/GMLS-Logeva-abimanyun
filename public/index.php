<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
| Di hosting  : aplikasi ada di folder ../../logeva_app
| Di localhost: aplikasi ada di folder proyek (satu tingkat di atas public)
*/
$base = is_dir(__DIR__.'/../../logeva_app')
    ? __DIR__.'/../../logeva_app'
    : __DIR__.'/..';

if (file_exists($maintenance = $base.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $base.'/vendor/autoload.php';

$app = require_once $base.'/bootstrap/app.php';

// Pastikan folder public yang dipakai adalah folder file ini
if (method_exists($app, 'usePublicPath')) {
    $app->usePublicPath(__DIR__);
} else {
    $app->bind('path.public', fn () => __DIR__);
}

if (method_exists($app, 'handleRequest')) {
    // Laravel 11 ke atas
    $app->handleRequest(Request::capture());
} else {
    // Laravel 10 ke bawah
    $kernel = $app->make(Kernel::class);
    $response = $kernel->handle($request = Request::capture())->send();
    $kernel->terminate($request, $response);
}