<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Batas kirim form donasi (publik, tanpa login) supaya tidak bisa dibanjiri donasi palsu:
        // per alamat IP 5x / 10 menit (termasuk percobaan yang salah isi) & 10x / hari, dan total semua pengirim 300x / hari.
        RateLimiter::for('donasi', function (Request $request) {
            $penuh = fn () => back()->withInput($request->except('bukti_transfer'))->withErrors([
                'jawaban' => 'Terlalu banyak donasi dikirim dalam waktu singkat. Silakan coba lagi nanti atau hubungi GMLS.',
            ]);

            return [
                Limit::perMinutes(10, 5)->by('ip:' . $request->ip())->response($penuh),
                Limit::perDay(10)->by('ip-hari:' . $request->ip())->response($penuh),
                Limit::perDay(300)->by('semua')->response($penuh),
            ];
        });
    }
}
