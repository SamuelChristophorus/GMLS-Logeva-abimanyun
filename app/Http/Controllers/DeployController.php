<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;

/**
 * Menjalankan perintah artisan lewat browser, untuk hosting TANPA terminal/SSH
 * dan tanpa akses database dari luar (contoh: InfinityFree).
 *
 *   https://<domain>/_deploy/<DEPLOY_TOKEN>/status    -> php artisan migrate:status
 *   https://<domain>/_deploy/<DEPLOY_TOKEN>/migrate   -> php artisan migrate --force
 *   https://<domain>/_deploy/<DEPLOY_TOKEN>/bersihkan -> php artisan optimize:clear
 *
 * Pengaman:
 *  - Mati jika DEPLOY_TOKEN kosong (404). Kosongkan lagi token setelah selesai deploy.
 *  - Hanya perintah di daftar PERINTAH yang bisa dijalankan.
 *  - Token dibandingkan dengan hash_equals; route dibatasi throttle.
 */
class DeployController extends Controller
{
    private const PERINTAH = [
        'status'    => ['migrate:status', []],
        'migrate'   => ['migrate',        ['--force' => true]],
        'bersihkan' => ['optimize:clear', []],
    ];

    public function __invoke(string $token, string $aksi)
    {
        $rahasia = (string) config('logeva.deploy_token');

        // Token kosong / terlalu pendek / salah -> seolah halaman tidak ada
        abort_if(strlen($rahasia) < 32 || ! hash_equals($rahasia, $token), 404);
        abort_unless(isset(self::PERINTAH[$aksi]), 404);

        [$perintah, $opsi] = self::PERINTAH[$aksi];

        @set_time_limit(120);
        $kode   = Artisan::call($perintah, $opsi);
        $output = Artisan::output();

        return response(
            "$ php artisan $perintah " . implode(' ', array_keys($opsi)) . "\n\n"
            . ($output !== '' ? $output : "(tidak ada output)\n")
            . "\nSelesai dengan kode: $kode" . ($kode === 0 ? ' (berhasil)' : ' (GAGAL)') . "\n"
            . "\nJangan lupa kosongkan DEPLOY_TOKEN di .env setelah selesai.\n",
            $kode === 0 ? 200 : 500,
            ['Content-Type' => 'text/plain; charset=UTF-8', 'X-Robots-Tag' => 'noindex']
        );
    }
}
