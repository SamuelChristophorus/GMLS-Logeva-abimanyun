# Panduan Deploy GMLS Logeva ke Hostinger

Kode di repository ini **sama untuk localhost dan Hostinger**. Tidak ada file yang perlu
diedit saat pindah server — cukup salin ke folder yang benar dan sesuaikan `.env`.

## 1. Pembagian folder

| Di repository / localhost          | Di Hostinger                       |
|------------------------------------|------------------------------------|
| `public/` (isinya)                 | `public_html/logeva/`              |
| semua folder & file lain (`app`, `bootstrap`, `config`, `database`, `resources`, `routes`, `storage`, `vendor`, `artisan`, `composer.json`, `.env`, …) | `logeva_app/` (sejajar dengan `public_html`, **bukan** di dalamnya) |

```
/home/<user>/
├── logeva_app/            <- aplikasi Laravel
│   ├── app/  bootstrap/  config/  database/  resources/  routes/  storage/  vendor/
│   ├── artisan
│   └── .env               <- .env khusus hosting
└── public_html/
    └── logeva/            <- isi folder public/
        ├── index.php      <- otomatis mendeteksi ../../logeva_app
        ├── assets/
        └── storage/assets/   <- foto upload (lokasi, avatar, default)
```

`public/index.php` otomatis memakai `../../logeva_app` jika folder itu ada, dan memakai
folder proyek biasa jika tidak ada (localhost).

## 2. Foto upload

Semua upload memakai `App\Support\Upload::simpan()` dan disimpan ke
`<folder public>/storage/assets/...`:

- localhost : `public/storage/assets/...`
- Hostinger : `public_html/logeva/storage/assets/...`

Di tampilan dipanggil dengan `asset('storage/' . $path)`.
Gambar default hasil import ada di `public/storage/assets/default/` (ikut di repository).

> Jangan jalankan `php artisan storage:link` — proyek ini tidak memakai symlink storage.

## 3. `.env` yang berbeda antara localhost dan Hostinger

| Kunci            | localhost                 | Hostinger                       |
|------------------|---------------------------|---------------------------------|
| `APP_ENV`        | `local`                   | `production`                    |
| `APP_DEBUG`      | `true`                    | `false`                         |
| `APP_URL`        | `http://127.0.0.1:8000`   | `https://gmls.org/logeva`       |
| `DB_DATABASE`    | `db_logeva`               | (nama database Hostinger)       |
| `DB_USERNAME`    | `root`                    | (user database Hostinger)       |
| `DB_PASSWORD`    | (kosong)                  | (password database Hostinger)   |
| `IMPORT_RELAWAN_PASSWORD` | opsional         | opsional (default `relawan123`) |

`.env` **tidak** ikut ke GitHub. Simpan `.env` hosting langsung di server.

## 4. Langkah update ke Hostinger

1. **Backup database** (phpMyAdmin → Export).
2. Upload file yang berubah:
   - perubahan di `public/` → ke `public_html/logeva/`
   - perubahan lainnya → ke `logeva_app/`
   - perhatikan huruf besar/kecil nama folder (server Linux membedakannya).
3. Jalankan migration (dari folder `logeva_app`, via SSH):
   ```
   php artisan migrate --force
   ```
   Tanpa SSH: jalankan SQL migration secara manual di phpMyAdmin.
4. Bersihkan cache:
   ```
   php artisan optimize:clear
   ```
   Tanpa SSH: hapus file `routes-*.php`, `config.php`, `services.php`, `packages.php`
   di `logeva_app/bootstrap/cache/`.
5. Jika `composer.json` berubah (ada package baru): `composer install --no-dev --optimize-autoloader`.
6. Uji halaman utama, login admin, dan satu import kecil.
