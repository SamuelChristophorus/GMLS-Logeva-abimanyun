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
| `APP_TIMEZONE`   | tidak perlu diisi         | tidak perlu diisi (default `Asia/Jakarta` / WIB) |
| `TARGET_HARI_BANTUAN` | opsional             | opsional (default `7`): kekurangan logistik posko dihitung agar stok cukup untuk sekian hari |

`.env` **tidak** ikut ke GitHub. Simpan `.env` hosting langsung di server.

`IMPORT_RELAWAN_PASSWORD` dibaca lewat `config/logeva.php`, jadi tetap berlaku walaupun
`php artisan config:cache` dijalankan. Setelah mengubah `.env`, jalankan `php artisan optimize:clear`.

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

## 5. Catatan update Oktober 2026 (status operasional, logistik keluar, ganti password)

### Migration baru
| Migration | Isi |
|---|---|
| `2026_10_15_000000_add_status_operasional_to_lokasis_table` | kolom status operasional posko (siaga / beroperasi / selesai) |
| `2026_10_20_000000_create_stok_keluars_table` | tabel catatan **barang keluar** logistik |
| `2026_10_20_100000_geser_waktu_ke_wib` | zona waktu aplikasi diganti UTC → **WIB**; semua jam lama digeser +7 jam (**hanya boleh jalan sekali**) |
| `2026_10_20_200000_create_notifications_table` | notifikasi di ikon lonceng (admin & relawan) |
| `2026_10_20_200100_create_permintaan_operasionals_table` | permintaan relawan agar admin memulai / menutup operasi posko |

Dengan SSH cukup `php artisan migrate --force`. **Tanpa SSH**, jalankan SQL berikut di
phpMyAdmin (lewati bagian yang sudah ada), lalu catat di tabel `migrations` agar tidak
dijalankan ulang:

```sql
-- 1. Status operasional posko (lewati jika kolom status_operasional sudah ada)
ALTER TABLE `lokasis`
  ADD `status_operasional` ENUM('siaga','beroperasi','selesai') NOT NULL DEFAULT 'siaga' AFTER `longitude`,
  ADD `mulai_beroperasi_at` TIMESTAMP NULL DEFAULT NULL AFTER `status_operasional`,
  ADD `selesai_beroperasi_at` TIMESTAMP NULL DEFAULT NULL AFTER `mulai_beroperasi_at`;

-- 2. Barang keluar
CREATE TABLE `stok_keluars` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lokasi_id` bigint(20) unsigned NOT NULL,
  `logistik_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `jumlah_keluar` int(11) NOT NULL,
  `tujuan` varchar(30) NOT NULL,
  `penerima` varchar(255) DEFAULT NULL,
  `tanggal_keluar` date NOT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stok_keluars_lokasi_id_foreign` (`lokasi_id`),
  KEY `stok_keluars_logistik_id_foreign` (`logistik_id`),
  KEY `stok_keluars_user_id_foreign` (`user_id`),
  CONSTRAINT `stok_keluars_logistik_id_foreign` FOREIGN KEY (`logistik_id`) REFERENCES `logistiks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stok_keluars_lokasi_id_foreign` FOREIGN KEY (`lokasi_id`) REFERENCES `lokasis` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stok_keluars_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Geser semua jam lama dari UTC ke WIB (+7 jam). JALANKAN SEKALI SAJA!
--    Cek dulu di tabel `migrations`: jika '2026_10_20_100000_geser_waktu_ke_wib' sudah ada, LEWATI langkah ini.
UPDATE `desas` SET `deleted_at` = `deleted_at` + INTERVAL 7 HOUR, `created_at` = `created_at` + INTERVAL 7 HOUR, `updated_at` = `updated_at` + INTERVAL 7 HOUR;
UPDATE `failed_jobs` SET `failed_at` = `failed_at` + INTERVAL 7 HOUR;
UPDATE `kategori_logistiks` SET `created_at` = `created_at` + INTERVAL 7 HOUR, `updated_at` = `updated_at` + INTERVAL 7 HOUR;
UPDATE `logistiks` SET `created_at` = `created_at` + INTERVAL 7 HOUR, `updated_at` = `updated_at` + INTERVAL 7 HOUR;
UPDATE `lokasis` SET `mulai_beroperasi_at` = `mulai_beroperasi_at` + INTERVAL 7 HOUR, `selesai_beroperasi_at` = `selesai_beroperasi_at` + INTERVAL 7 HOUR, `deleted_at` = `deleted_at` + INTERVAL 7 HOUR, `created_at` = `created_at` + INTERVAL 7 HOUR, `updated_at` = `updated_at` + INTERVAL 7 HOUR;
UPDATE `password_reset_tokens` SET `created_at` = `created_at` + INTERVAL 7 HOUR;
UPDATE `pengungsis` SET `created_at` = `created_at` + INTERVAL 7 HOUR, `updated_at` = `updated_at` + INTERVAL 7 HOUR;
UPDATE `permissions` SET `created_at` = `created_at` + INTERVAL 7 HOUR, `updated_at` = `updated_at` + INTERVAL 7 HOUR;
UPDATE `relawans` SET `deleted_at` = `deleted_at` + INTERVAL 7 HOUR, `created_at` = `created_at` + INTERVAL 7 HOUR, `updated_at` = `updated_at` + INTERVAL 7 HOUR;
UPDATE `riwayat_pengungsis` SET `deleted_at` = `deleted_at` + INTERVAL 7 HOUR, `created_at` = `created_at` + INTERVAL 7 HOUR, `updated_at` = `updated_at` + INTERVAL 7 HOUR;
UPDATE `roles` SET `created_at` = `created_at` + INTERVAL 7 HOUR, `updated_at` = `updated_at` + INTERVAL 7 HOUR;
UPDATE `sphere_lokasis` SET `deleted_at` = `deleted_at` + INTERVAL 7 HOUR, `created_at` = `created_at` + INTERVAL 7 HOUR, `updated_at` = `updated_at` + INTERVAL 7 HOUR;
UPDATE `sphere_pengungsis` SET `deleted_at` = `deleted_at` + INTERVAL 7 HOUR, `created_at` = `created_at` + INTERVAL 7 HOUR, `updated_at` = `updated_at` + INTERVAL 7 HOUR;
UPDATE `status_lokasis` SET `deleted_at` = `deleted_at` + INTERVAL 7 HOUR, `created_at` = `created_at` + INTERVAL 7 HOUR, `updated_at` = `updated_at` + INTERVAL 7 HOUR;
UPDATE `stok_keluars` SET `created_at` = `created_at` + INTERVAL 7 HOUR, `updated_at` = `updated_at` + INTERVAL 7 HOUR;
UPDATE `stok_logistiks` SET `created_at` = `created_at` + INTERVAL 7 HOUR, `updated_at` = `updated_at` + INTERVAL 7 HOUR;
UPDATE `stok_masuks` SET `created_at` = `created_at` + INTERVAL 7 HOUR, `updated_at` = `updated_at` + INTERVAL 7 HOUR;
UPDATE `users` SET `email_verified_at` = `email_verified_at` + INTERVAL 7 HOUR, `approved_at` = `approved_at` + INTERVAL 7 HOUR, `deleted_at` = `deleted_at` + INTERVAL 7 HOUR, `created_at` = `created_at` + INTERVAL 7 HOUR, `updated_at` = `updated_at` + INTERVAL 7 HOUR;

-- 4. Notifikasi (ikon lonceng)
CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) unsigned NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Permintaan mulai / tutup operasi dari relawan
CREATE TABLE `permintaan_operasionals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lokasi_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `aksi` varchar(10) NOT NULL,
  `status` varchar(15) NOT NULL DEFAULT 'menunggu',
  `alasan` text DEFAULT NULL,
  `catatan_admin` text DEFAULT NULL,
  `diproses_oleh` bigint(20) unsigned DEFAULT NULL,
  `diproses_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `permintaan_operasionals_user_id_foreign` (`user_id`),
  KEY `permintaan_operasionals_diproses_oleh_foreign` (`diproses_oleh`),
  KEY `permintaan_operasionals_lokasi_id_status_index` (`lokasi_id`,`status`),
  CONSTRAINT `permintaan_operasionals_diproses_oleh_foreign` FOREIGN KEY (`diproses_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `permintaan_operasionals_lokasi_id_foreign` FOREIGN KEY (`lokasi_id`) REFERENCES `lokasis` (`id`) ON DELETE CASCADE,
  CONSTRAINT `permintaan_operasionals_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Tandai migration sudah dijalankan (sesuaikan batch = angka batch terbesar + 1)
INSERT INTO `migrations` (`migration`, `batch`) VALUES
  ('2026_10_15_000000_add_status_operasional_to_lokasis_table', 99),
  ('2026_10_20_000000_create_stok_keluars_table', 99),
  ('2026_10_20_100000_geser_waktu_ke_wib', 99),
  ('2026_10_20_200000_create_notifications_table', 99),
  ('2026_10_20_200100_create_permintaan_operasionals_table', 99);
```

> **Zona waktu**: aplikasi sekarang memakai WIB (`Asia/Jakarta`) secara default. Jangan isi
> `APP_TIMEZONE` di `.env` Hostinger kecuali ingin zona lain. Upload `config/app.php` yang baru
> **bersamaan** dengan menjalankan migration di atas; jika config sudah WIB tetapi data belum
> digeser (atau sebaliknya), jam yang tampil akan meleset 7 jam.

### File yang wajib ikut diupload
- `config/logeva.php` (**file baru**, tanpa file ini import relawan tetap jalan dengan password `relawan123`).
- View pratinjau import desa diganti nama dari `Import-preview.blade.php` menjadi
  `import-preview.blade.php` (huruf kecil). Di server Linux nama lama menyebabkan error
  *View not found* saat import desa. **Hapus file lama** `Import-preview.blade.php` di
  `logeva_app/resources/views/pages/admin/desa/` setelah file baru diupload.

### Foto pengungsi & avatar pendaftaran
Sebelumnya foto pengungsi tersimpan ke `storage/app/public/` dan avatar dari form daftar
tersimpan ke folder **di luar** `public_html`, sehingga keduanya tidak tampil. Sekarang
keduanya memakai `Upload::simpan()` seperti upload lain (`storage/assets/pengungsi` dan
`storage/assets/avatar`). Foto yang terlanjur tersimpan di lokasi lama perlu disalin manual:
- `logeva_app/storage/app/public/pengungsi/*` → `public_html/logeva/storage/assets/pengungsi/`
  lalu ubah kolom `pengungsis.foto` dari `pengungsi/...` menjadi `assets/pengungsi/...`.
- `/home/<user>/logeva/storage/assets/avatar/*` → `public_html/logeva/storage/assets/avatar/`
  (path di database sudah benar, cukup salin filenya).

### Setelah upload
- Login sebagai relawan yang dibuat lewat import (password `relawan123`) → harus langsung
  diarahkan ke halaman Profil dengan pesan untuk mengganti password.
- **Isi Kebutuhan Harian** setiap barang pokok di Panel Admin → Master Logistik (data ini tidak ikut
  ter-upload karena ada di database). Status "Posko Butuh Bantuan" & jumlah "perlu dikirim" dihitung
  dari angka ini × jumlah pengungsi. Nilai yang dipakai di localhost:
  | Barang | Kebutuhan harian per orang |
  |---|---|
  | Beras | 0,3 kg |
  | Air Mineral | 3 liter (standar air minum SPHERE 2,5–3 L) |
- Buka halaman Monitoring Lokasi: panel **Posko Butuh Bantuan** harus tampil di atas peta, dan titik posko
  yang kekurangan logistik bertanda **!** merah/kuning.

## 6. Deploy ke InfinityFree (hosting gratis, logeva.wuaze.com)

InfinityFree berbeda dari Hostinger: **hanya boleh upload ke dalam `htdocs`**, **tidak ada SSH/terminal**,
dan **database tidak bisa diakses dari luar** (`sql200.infinityfree.com` tidak dikenal dari internet),
jadi `php artisan migrate` dari laptop tidak akan bisa terhubung.

### Struktur folder
```
htdocs/                  <- ISI folder public/ (index.php, .htaccess, assets/, storage/, vendor/, favicon.ico, robots.txt)
htdocs/logeva_app/       <- semua folder & file lain (app, bootstrap, config, database, resources, routes,
                            storage, vendor, artisan, composer.json, .env)
```
`public/index.php` otomatis mengenali `htdocs/logeva_app`. `public/.htaccess` **memblokir** semua akses
browser ke `logeva_app/` dan ke file berawalan titik (`.env`, `.git`), kecuali `.well-known` (verifikasi SSL).

### Langkah pertama kali
1. Hapus file bawaan InfinityFree di `htdocs` (misalnya `index2.html`).
2. Upload sesuai struktur di atas. **Jangan** upload `node_modules/`, `.git/`, `tests/`, dan isi
   `bootstrap/cache/*.php` (cache dari laptop membuat route lama tetap dipakai).
   Tips: kompres jadi .zip, lalu pakai **Upload & Unzip** di File Manager.
3. Upload `.env.production` (ada di folder proyek, tidak ikut git) ke `htdocs/logeva_app/`, lalu **ganti namanya
   menjadi `.env`**.
4. Pastikan folder `htdocs/logeva_app/storage/` (dan subfoldernya) serta `htdocs/storage/` bisa ditulis.
5. Di panel InfinityFree, pilih versi PHP **8.2 atau lebih baru** (Laravel 12 butuh PHP 8.2).
6. **Data awal**: phpMyAdmin → pilih database `if0_43230528_db_logeva` → Import → pilih file SQL hasil
   ekspor database localhost (berisi tabel + data + tabel `migrations`).
7. Cek pengaman: buka `http://logeva.wuaze.com/logeva_app/.env` → **harus 404**. Jika isi file tampil,
   segera hapus `.env` di server dan periksa `.htaccess` di `htdocs`.

### Migrate berikutnya (tanpa terminal)
1. Buat & uji migration di laptop, lalu upload file migration baru ke `htdocs/logeva_app/database/migrations/`.
2. Isi `DEPLOY_TOKEN` di `htdocs/logeva_app/.env` (minimal 32 karakter acak; `.env.production` sudah berisi satu).
3. Buka **lewat browser** (bukan curl — InfinityFree memblokir akses non-browser):
   - `http://logeva.wuaze.com/_deploy/<DEPLOY_TOKEN>/status` → `migrate:status`
   - `http://logeva.wuaze.com/_deploy/<DEPLOY_TOKEN>/migrate` → `migrate --force`
   - `http://logeva.wuaze.com/_deploy/<DEPLOY_TOKEN>/bersihkan` → `optimize:clear`
4. **Kosongkan lagi `DEPLOY_TOKEN`** di `.env` server. Selama kosong, alamat `/_deploy/...` selalu 404.

> Jangan pakai route migrate tanpa `--force`: di `APP_ENV=production` Laravel meminta konfirmasi,
> di browser tidak ada yang menjawab sehingga migrate **dibatalkan diam-diam**.
