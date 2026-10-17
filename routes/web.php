<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DesaController;
use App\Http\Controllers\Admin\LokasiController;
use App\Http\Controllers\Admin\RelawanController;
use App\Http\Controllers\Admin\StatusLokasiController;
use App\Http\Controllers\Admin\VerifikasiLokasiController;
use App\Http\Controllers\Admin\AdminLogistikController;
use App\Http\Controllers\Admin\DesaImportController;
use App\Http\Controllers\Admin\Import\LogistikImportController;
use App\Http\Controllers\Admin\Import\RelawanImportController;
use App\Http\Controllers\Admin\Import\LokasiImportController;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;

use App\Http\Controllers\User\HomeController;
use App\Http\Controllers\User\LokasiController as UserLokasiController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\PengungsiController;

use App\Http\Controllers\StokMasukController;

/*
|--------------------------------------------------------------------------
| PUBLIC
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class,'index'])->name('home');

Route::get('/lokasis', [UserLokasiController::class,'index'])->name('lokasi.index');
Route::get('/lokasi/{id}', [UserLokasiController::class,'show'])->name('lokasi.show');

/*
|--------------------------------------------------------------------------
| AUTH USER
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');

    Route::patch('/lokasi/{lokasi}/operasional', [\App\Http\Controllers\OperasionalLokasiController::class, 'update'])
        ->name('lokasi.operasional');

    Route::get('/create-lokasi', [UserLokasiController::class,'create'])->name('lokasi.create');
    Route::post('/create-lokasi', [UserLokasiController::class,'store'])->name('lokasi.store');

    Route::get('/lokasi-success', [UserLokasiController::class,'success'])->name('lokasi.success');

    Route::get('/my-lokasi', [UserLokasiController::class,'myLokasi'])->name('lokasi.mylokasi');

});

/*
|--------------------------------------------------------------------------
| PENGUNGSI
|--------------------------------------------------------------------------
*/

// Hanya user yang sudah login (pengunjung publik diarahkan ke halaman login)
Route::middleware(['auth'])->group(function () {

    Route::get('/lokasi/{lokasi}/pengungsi',[PengungsiController::class, 'index'])->name('pengungsi.index');
    Route::post('/lokasi/{lokasi}/pengungsi',[PengungsiController::class, 'store'])->name('pengungsi.store');
    Route::post('/pengungsi/import/{lokasi}',[PengungsiController::class, 'import'])->name('pengungsi.import');
    Route::get('/pengungsi/export/{lokasi}',[PengungsiController::class, 'export'])->name('pengungsi.export');
    Route::get('/pengungsi/{pengungsi}',[PengungsiController::class, 'show'])->name('pengungsi.show');
    Route::get('/pengungsi/{pengungsi}/edit',[PengungsiController::class, 'edit'])->name('pengungsi.edit');
    Route::put('/pengungsi/{pengungsi}',[PengungsiController::class, 'update'])->name('pengungsi.update');
    Route::delete('/pengungsi/{pengungsi}',[PengungsiController::class, 'destroy'])->name('pengungsi.destroy');

});

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/

Route::get('/login', [LoginController::class,'index'])->name('login');
Route::post('/login', [LoginController::class,'store'])->name('login.store');
Route::post('/logout', [LoginController::class,'logout'])->name('logout')->middleware('auth');

Route::get('/register', [RegisterController::class,'index'])->name('register');
Route::post('/register', [RegisterController::class, 'store'])->name('register.store');

/*
|--------------------------------------------------------------------------
| ADMIN PANEL
|--------------------------------------------------------------------------
*/

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth','role:admin'])
    ->group(function () {

        Route::get('/dashboard',[DashboardController::class,'index'])
            ->name('dashboard');

        /*
        |--------------------------------------------------------------------------
        | APPROVAL RELAWAN
        |--------------------------------------------------------------------------
        */

        Route::patch('/relawan/{relawan}/accept', [RelawanController::class, 'accept'])
            ->name('relawan.accept');

        Route::patch('/relawan/{relawan}/reject', [RelawanController::class, 'reject'])
            ->name('relawan.reject');

        Route::patch('/relawan/{relawan}/suspend', [RelawanController::class, 'suspend'])
            ->name('relawan.suspend');

        /*
        |--------------------------------------------------------------------------
        | LOKASI
        |--------------------------------------------------------------------------
        */

        Route::get('/lokasi/export', [LokasiController::class, 'export'])
            ->name('lokasi.export');

        // Approve / tolak laporan posko
        Route::patch('/lokasi/{lokasi}/verifikasi', [VerifikasiLokasiController::class, 'update'])
            ->name('lokasi.verifikasi');

        /*
        |--------------------------------------------------------------------------
        | IMPORT / EXPORT DESA
        | (harus di atas Route::resource('desa') agar tidak tertangkap route show)
        |--------------------------------------------------------------------------
        */

        Route::get('/desa/export', [DesaImportController::class, 'export'])
            ->name('desa.export');
        Route::get('/desa/import/template', [DesaImportController::class, 'template'])
            ->name('desa.import.template');
        Route::post('/desa/import/preview', [DesaImportController::class, 'preview'])
            ->name('desa.import.preview');
        Route::post('/desa/import/confirm', [DesaImportController::class, 'confirm'])
            ->name('desa.import.confirm');

        /*
        |--------------------------------------------------------------------------
        | IMPORT / EXPORT LOGISTIK, RELAWAN, LOKASI
        | (harus di atas Route::resource agar tidak tertangkap route show)
        |--------------------------------------------------------------------------
        */

        Route::get('/logistik/export', [LogistikImportController::class, 'export'])
            ->name('logistik.export');
        Route::get('/logistik/import/template', [LogistikImportController::class, 'template'])
            ->name('logistik.import.template');
        Route::post('/logistik/import/preview', [LogistikImportController::class, 'preview'])
            ->name('logistik.import.preview');
        Route::post('/logistik/import/confirm', [LogistikImportController::class, 'confirm'])
            ->name('logistik.import.confirm');

        Route::get('/relawan/export', [RelawanImportController::class, 'export'])
            ->name('relawan.export');
        Route::get('/relawan/import/template', [RelawanImportController::class, 'template'])
            ->name('relawan.import.template');
        Route::post('/relawan/import/preview', [RelawanImportController::class, 'preview'])
            ->name('relawan.import.preview');
        Route::post('/relawan/import/confirm', [RelawanImportController::class, 'confirm'])
            ->name('relawan.import.confirm');

        // Export lokasi sudah ada (admin.lokasi.export)
        Route::get('/lokasi/import/template', [LokasiImportController::class, 'template'])
            ->name('lokasi.import.template');
        Route::post('/lokasi/import/preview', [LokasiImportController::class, 'preview'])
            ->name('lokasi.import.preview');
        Route::post('/lokasi/import/confirm', [LokasiImportController::class, 'confirm'])
            ->name('lokasi.import.confirm');

        /*
        |--------------------------------------------------------------------------
        | RESOURCE
        |--------------------------------------------------------------------------
        */

        Route::resource('relawan', RelawanController::class);
        Route::resource('desa', DesaController::class);
        Route::resource('lokasi', LokasiController::class);

        /*
        |--------------------------------------------------------------------------
        | STATUS LOKASI
        |--------------------------------------------------------------------------
        */

        Route::get('/status-lokasi/{lokasiId}/create', [StatusLokasiController::class,'create'])
            ->name('status-lokasi.create');

        Route::resource('status-lokasi', StatusLokasiController::class)
            ->except('create');

        /*
        |--------------------------------------------------------------------------
        | LOGISTIK ADMIN
        |--------------------------------------------------------------------------
        */

        Route::resource('logistik', AdminLogistikController::class)
            ->except(['show']);
    });

/*
|--------------------------------------------------------------------------
| USER LOGISTIK (STOK MASUK)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    Route::get('/logistik/{lokasi}', [StokMasukController::class, 'index'])
        ->name('logistik.index');

    Route::get('/logistik/{lokasi}/create', [StokMasukController::class, 'create'])
        ->name('logistik.create');

    Route::post('/stok-masuk/store', [StokMasukController::class, 'store'])
        ->name('stok-masuk.store');

});