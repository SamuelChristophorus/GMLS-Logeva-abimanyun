<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DesaController;
use App\Http\Controllers\Admin\LokasiController;
use App\Http\Controllers\Admin\RelawanController;
use App\Http\Controllers\Admin\StatusLokasiController;
use App\Http\Controllers\Admin\AdminLogistikController;

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

Route::get('/lokasi/{lokasi}/pengungsi',[PengungsiController::class, 'index'])->name('pengungsi.index');
Route::post('/lokasi/{lokasi}/pengungsi',[PengungsiController::class, 'store'])->name('pengungsi.store');
Route::post('/pengungsi/import/{lokasi}',[PengungsiController::class, 'import'])->name('pengungsi.import');
Route::get('/pengungsi/export/{lokasi}',[PengungsiController::class, 'export'])->name('pengungsi.export');
Route::get('/pengungsi/{pengungsi}',[PengungsiController::class, 'show'])->name('pengungsi.show');
Route::get('/pengungsi/{pengungsi}/edit',[PengungsiController::class, 'edit'])->name('pengungsi.edit');
Route::put('/pengungsi/{pengungsi}',[PengungsiController::class, 'update'])->name('pengungsi.update');
Route::delete('/pengungsi/{pengungsi}',[PengungsiController::class, 'destroy'])->name('pengungsi.destroy');

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