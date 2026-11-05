<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class 
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // 419 "Page Expired": form dibuka terlalu lama / sesi habis. Daripada halaman buntu,
        // kembalikan ke form (isian tetap, kecuali password) dengan pesan yang jelas.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() !== 419 || $request->expectsJson()) {
                return null;
            }

            $pesan = 'Halaman sudah terlalu lama dibuka sehingga sesinya habis. Silakan coba lagi.';

            return redirect()->to(url()->previous() !== $request->fullUrl() ? url()->previous() : route('home'))
                ->withInput($request->except(['password', 'password_confirmation', '_token', 'bukti_transfer', 'jawaban', 'dibuka_pada']))
                // Halaman login hanya menampilkan error di kolom email
                ->withErrors([$request->routeIs('login.store', 'register.store') ? 'email' : 'sesi' => $pesan]);
        });
    })->create();
