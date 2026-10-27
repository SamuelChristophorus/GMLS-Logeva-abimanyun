# Skema Database & Aturan Bisnis Website GMLS Logeva — Acuan untuk Aplikasi Flutter (`Logeva-Client-new`)

> **Untuk Claude Code di proyek `Logeva-Client-new`.** Dokumen ini adalah **sumber acuan (source of truth)**:
> website Laravel `GMLS-Logeva-abimanyun` (branch `Logeva_CloudDB`) sudah berjalan dan dipakai.
> Tugas di aplikasi: **sesuaikan skema SQLite lokal & perilaku fitur agar sama dengan website**,
> sehingga nanti data bisa dipertukarkan tanpa konversi rumit.
>
> Dibuat 23 Okt 2026 dari kode website commit `e7a7c1f` (skema dibaca dengan menjalankan ke-25 migration
> ke SQLite, lalu dicek ke kode). Jika ragu, kode website di
> `C:\Users\jayas\OneDrive\Documents\github\GMLS-Logeva-abimanyun` adalah acuannya.
>
> **Diperbarui 23 Okt 2026** setelah penyelarasan selesai di kedua sisi:
> - Aplikasi: database SQLite versi 7 (posko, pengungsi, logistik mengikuti website) + ekspor Paket Data Posko.
> - Website: migration `2026_10_23_000000_add_kode_paket_data_posko` (kolom `kode` + tabel `paket_penghapusans`)
>   dan menu admin **Import Paket Data Posko** (`/admin/paket-data`, `PaketDataPoskoService`).
> - Keputusan baru: **Nomor KK opsional** di aplikasi & website (§0, §2, §5, §8.4).
> - Paket mencantumkan **nama & kontak admin (penanggung jawab) dan relawan** aktif di `petugas.csv` (§8.7a);
>   website menyimpannya di tabel `petugas_poskos` (migration `2026_10_23_000100_create_petugas_poskos_table`).
> - **Beberapa HP dalam satu posko** (24 Okt 2026): HP Induk (admin/supervisor) + HP Anggota (relawan),
>   disiapkan & disinkronkan lewat file tanpa internet (§9). Aplikasi: database versi 8.
>   Website: migration `2026_10_24_000000_add_kode_akun_to_petugas_poskos_table`.

---

## 0. Keputusan pemilik proyek (WAJIB diikuti)

| Topik | Keputusan |
|---|---|
| Mode aplikasi | **Khusus OFFLINE.** Tidak ada login ke website, tidak ada API, tidak ada sinkronisasi online. Jika ada internet, relawan memakai **website** langsung. |
| Akun di aplikasi | **Akun lokal** per HP. **Admin lokal tetap ada** di setiap HP dan bisa membuat akun relawan lokal (PIN) saat bencana. Akun aplikasi **tidak** disamakan dengan akun website. |
| Pertukaran data | Tiap posko **mengekspor Paket Data Posko (CSV)**, dibawa **manual lewat USB** ke pusat, lalu **digabung (merge) di website** pusat. Format paket: **§8** (wajib persis). |
| Tujuan penyelarasan | Skema SQLite & fitur aplikasi **mengikuti website** (nama kolom, pilihan nilai, rumus), supaya paket dari banyak posko bisa digabung tanpa konversi dan **tanpa data dobel**. |
| Data pengungsi (23 Okt 2026) | **Tanggal lahir wajib** (usia dihitung darinya). **Nomor KK opsional**: dokumen bisa hilang saat bencana, jadi KK boleh kosong; jika diisi harus 16 digit. Berlaku di aplikasi dan website. NIK tidak dipakai. |
| Hak akses lokal | Hanya **admin lokal** yang boleh menghapus posko dan mengekspor paket. Relawan lokal hanya mengelola data (pengungsi, logistik) di posko tugasnya. |

Latar belakang teknis (kenapa tidak online): website di hosting gratis InfinityFree tidak punya API, InfinityFree
memblokir akses non-browser (anti-bot JavaScript), dan MySQL-nya tidak bisa diakses dari luar server.

1. **Aplikasi tetap offline (SQLite/`sqflite`).** Jangan menambahkan paket HTTP/sinkronisasi.
2. **Gunakan nama kolom & nilai pilihan (enum) yang sama persis** dengan website (huruf besar/kecil, tanda hubung,
   spasi). Contoh: `Laki-laki` (bukan `L`/`Pria`), `Ibu Hamil`, `dapur_umum`.
3. Ikuti pola migrasi aplikasi yang sudah ada (`lib/data/database_tables.dart`): tambah langkah versi baru
   (`_v4`, ...) di `upgradeSteps`, naikkan `version`, **jangan menghapus data pengguna yang sudah ada**.
4. Zona waktu website **WIB (Asia/Jakarta)**. Tanggal: `YYYY-MM-DD`; tanggal-waktu: `YYYY-MM-DD HH:MM:SS` waktu WIB.

---

## 1. Peran pengguna & hak akses

| Peran | Cara login di website | Boleh |
|---|---|---|
| **admin** | email + password | semua: verifikasi laporan posko, mulai/tutup operasi, kelola master data, akun relawan |
| **relawan** | email + password | hanya posko **miliknya** (`lokasis.relawan_id` = id relawannya): lapor posko, kelola pengungsi, catat barang masuk/keluar, **mengajukan** mulai/tutup operasi |
| publik | tanpa login | melihat peta, daftar & detail posko (kecuali yang ditolak), kebutuhan bantuan |

Aturan akun (`users.status`): `pending` → admin `approved` / `rejected`; admin bisa `suspended`.
Login hanya berhasil jika `status = approved` dan `is_verified = 1` (admin dikecualikan).
Ganti password (halaman Profil): minimal **8 karakter, berisi huruf dan angka**, dan harus berbeda dari password lama.
Relawan hasil import diberi password awal `relawan123` dan diarahkan untuk mengganti password saat login.

Peran disimpan dengan paket Spatie: tabel `roles` (`admin`, `relawan`) + `model_has_roles`
(`model_type = App\Models\User`, `model_id = users.id`). Data relawan tambahan ada di tabel `relawans`
(satu baris per user relawan).

---

## 2. Skema tabel (data aplikasi)

Tipe di bawah adalah tipe **SQLite** (hasil migration website di SQLite, cocok untuk `sqflite`).
Di MySQL website: semua `id`/`*_id` = `BIGINT UNSIGNED`, `varchar` = `VARCHAR(255)`,
`kebutuhan_harian` = `DECIMAL(10,2)`, `luas_lokasi` = `FLOAT`, kolom "Pilihan" = `ENUM`.
`deleted_at` = *soft delete* (baris tidak benar-benar dihapus; `deleted_at` terisi = dianggap terhapus).
Tabel bawaan framework (sessions, cache, jobs, migrations, permissions) tidak dicantumkan.

### Relasi utama

```
users 1─1 relawans 1─* lokasis *─1 desas
                        lokasis 1─* status_lokasis          (riwayat persetujuan; yang TERAKHIR = status aktif)
                        lokasis 1─1 sphere_lokasis          (kebutuhan SPHERE untuk kapasitas penuh)
                        lokasis 1─* pengungsis
                        lokasis 1─* stok_logistiks *─1 logistiks *─1 kategori_logistiks
                        lokasis 1─* stok_masuks    (barang masuk, +stok)
                        lokasis 1─* stok_keluars   (barang keluar, −stok)
                        lokasis 1─* permintaan_operasionals (pengajuan relawan → keputusan admin)
users 1─* notifications (polimorfik: notifiable_type = App\Models\User)
```

### `desas`
| Kolom | Tipe | Wajib | Default | Keterangan |
|---|---|---|---|---|
| id | integer | ya |  | PK |
| nama_desa | varchar | ya |  | dipakai mencocokkan data saat import |
| alamat_desa | text | ya |  |  |
| deleted_at | datetime | tidak |  | soft delete |
| created_at / updated_at | datetime | tidak |  |  |

### `users`
| Kolom | Tipe | Wajib | Default | Keterangan |
|---|---|---|---|---|
| id | integer | ya |  | PK |
| name | varchar | ya |  |  |
| email | varchar | ya |  | UNIQUE, dipakai login |
| email_verified_at | datetime | tidak |  |  |
| password | varchar | ya |  | hash **bcrypt** (bukan SHA/salt) |
| remember_token | varchar | tidak |  |  |
| status | varchar | ya | pending | Pilihan: `pending`, `approved`, `rejected`, `suspended` |
| is_verified | tinyint(1) | ya | 0 |  |
| approved_at | datetime | tidak |  |  |
| deleted_at | datetime | tidak |  | soft delete |
| created_at / updated_at | datetime | tidak |  |  |

### `relawans`
| Kolom | Tipe | Wajib | Default | Keterangan |
|---|---|---|---|---|
| id | integer | ya |  | PK |
| user_id | integer | ya |  | FK → users.id (hapus induk: cascade) |
| avatar | varchar | ya |  | path gambar, contoh `assets/avatar/123_foto.jpg`; default `assets/default/avatar.png` |
| deleted_at | datetime | tidak |  | soft delete |
| created_at / updated_at | datetime | tidak |  |  |

### `lokasis` (posko / TEA)
| Kolom | Tipe | Wajib | Default | Keterangan |
|---|---|---|---|---|
| id | integer | ya |  | PK |
| kode | varchar(36) | tidak |  | UNIQUE. UUID posko dari aplikasi (diisi saat import Paket Data Posko); kosong = posko dibuat di website |
| desa_id | integer | ya |  | FK → desas.id (cascade) |
| relawan_id | integer | ya |  | FK → relawans.id (cascade) — **penanggung jawab** posko |
| nama_lokasi | varchar | ya |  | tidak dibatasi unik di database; saat import, posko dicocokkan dengan **nama + desa** |
| alamat_lokasi | text | ya |  |  |
| luas_lokasi | float | ya |  | m² |
| kapasitas_pengungsi | integer | ya |  | **otomatis** = round(luas ÷ 3,5) |
| gambar_lokasi | varchar | ya |  | path gambar; default `assets/default/lokasi.png` |
| latitude / longitude | varchar | ya |  | teks angka desimal, contoh `-6.9195` / `106.1297` |
| status_operasional | varchar | ya | siaga | Pilihan: `siaga`, `beroperasi`, `selesai` |
| mulai_beroperasi_at | datetime | tidak |  | diisi saat mulai beroperasi |
| selesai_beroperasi_at | datetime | tidak |  | diisi saat operasi ditutup |
| deleted_at | datetime | tidak |  | soft delete |
| created_at / updated_at | datetime | tidak |  |  |

### `status_lokasis` (riwayat persetujuan laporan posko)
| Kolom | Tipe | Wajib | Default | Keterangan |
|---|---|---|---|---|
| id | integer | ya |  | PK |
| lokasi_id | integer | ya |  | FK → lokasis.id (cascade) |
| status | varchar | ya |  | Pilihan: `pending`, `approved`, `rejected` |
| catatan | text | ya |  | contoh "Lokasi Berhasil Diajukan", "Disetujui oleh admin" |
| deleted_at | datetime | tidak |  | soft delete |
| created_at / updated_at | datetime | tidak |  |  |

**Status persetujuan aktif = baris dengan `id` terbesar (yang tidak terhapus) untuk lokasi itu.**
Setiap keputusan admin **menambah baris baru** (riwayat tidak ditimpa). Posko baru dari relawan → baris `pending`.

### `sphere_lokasis` (kebutuhan SPHERE untuk kapasitas penuh)
| Kolom | Tipe | Wajib | Keterangan |
|---|---|---|---|
| id | integer | ya | PK |
| lokasi_id | integer | ya | FK → lokasis.id (cascade) |
| air_hidup / air_kebersihan / air_memasak | integer | ya | liter per hari untuk seluruh kapasitas |
| toilet_pendek / toilet_panjang | integer | ya | unit |
| kalori / protein / lemak | integer | ya | kkal / gram per hari untuk seluruh kapasitas |
| deleted_at, created_at, updated_at | datetime | tidak | |

Rumus (k = kapasitas_pengungsi): air_hidup = round(2,5·k), air_kebersihan = 2·k, air_memasak = 3·k,
toilet_pendek = round(k/50), toilet_panjang = round(k/20), kalori = 2100·k, protein = 53·k, lemak = 40·k.

### `pengungsis`
| Kolom | Tipe | Wajib | Default | Keterangan |
|---|---|---|---|---|
| id | integer | ya |  | PK |
| kode | varchar(36) | tidak |  | UNIQUE. UUID pengungsi dari aplikasi; kosong = dicatat langsung di website |
| lokasi_id | integer | ya |  | FK → lokasis.id (cascade) |
| foto | varchar | tidak |  | path gambar, contoh `assets/pengungsi/...` |
| nama | varchar | ya |  |  |
| asal | varchar | ya |  | asal desa / alamat sebelum mengungsi |
| tanggal_lahir | date | tidak* |  | *wajib di form & import website; boleh kosong hanya untuk data lama dari aplikasi versi sebelumnya (masuk lewat paket) |
| usia | integer | ya |  | tahun; harus sesuai tanggal lahir (selisih ≤ 1) |
| jenis_kelamin | varchar | ya |  | Pilihan: `Laki-laki`, `Perempuan` |
| nomor_kk | varchar | tidak |  | **opsional** (form, import, paket); jika diisi 16 digit angka (simpan sebagai TEKS) |
| kondisi_kesehatan | varchar | ya |  | teks bebas; kosong → `Sehat` |
| kelompok_rentan | varchar | ya | Tidak | Pilihan: `Tidak`, `Ibu Hamil`, `Lansia`, `Bayi`, `Disabilitas`, `Sakit`; kosong → `Tidak` |
| riwayat_penyakit | text | tidak |  |  |
| diubah_pada_perangkat | datetime | tidak |  | `diubah_pada` dari paket saat terakhir diimpor (paket lama tidak menimpa data lebih baru) |
| created_at / updated_at | datetime | tidak |  |  |

Pengungsi dianggap **orang yang sama** jika **nama (tanpa beda huruf besar/kecil) + tanggal_lahir** sama di posko yang sama.

### `paket_penghapusans` (kode yang sudah dihapus)
| Kolom | Tipe | Wajib | Keterangan |
|---|---|---|---|
| id | integer | ya | PK |
| kode | varchar(36) | ya | UNIQUE, kode pengungsi yang sudah dihapus |
| jenis | varchar(20) | ya | `pengungsi` |
| dihapus_pada | datetime | tidak | |
| created_at / updated_at | datetime | tidak | |

Diisi saat pengungsi dihapus lewat paket **atau** lewat form website (jika punya `kode`). Kode di tabel ini
tidak pernah ditambahkan lagi, sehingga paket lama yang diimpor belakangan tidak memunculkan orang yang sudah dihapus.

### `kategori_logistiks`
| Kolom | Tipe | Wajib | Keterangan |
|---|---|---|---|
| id | integer | ya | PK |
| nama_kategori | varchar | ya | isi awal (id 1–10): Makanan, Minuman, Pakaian, Perlengkapan Tidur, Kebersihan, Medis, Bayi & Anak, Dapur Umum, Sanitasi, Lainnya |
| created_at / updated_at | datetime | tidak | |

### `logistiks` (master barang)
| Kolom | Tipe | Wajib | Keterangan |
|---|---|---|---|
| id | integer | ya | PK |
| kategori_logistik_id | integer | ya | FK → kategori_logistiks.id (cascade) |
| nama_item | varchar | ya | contoh Beras, Air Mineral |
| satuan | varchar | ya | contoh kg, liter, dus |
| kebutuhan_harian | numeric (DECIMAL 10,2) | tidak | kebutuhan **per orang per hari** dalam `satuan`. Nilai sekarang: Beras 0,3 kg; Air Mineral 3 liter. Kosong = tidak bisa dihitung |
| deskripsi | text | tidak | |
| created_at / updated_at | datetime | tidak | |

Barang **pokok** = barang dengan `kebutuhan_harian > 0`.

### `stok_logistiks` (stok saat ini per posko per barang)
| Kolom | Tipe | Wajib | Default | Keterangan |
|---|---|---|---|---|
| id | integer | ya |  | PK |
| lokasi_id | integer | ya |  | FK → lokasis.id (cascade) |
| logistik_id | integer | ya |  | FK → logistiks.id (cascade) |
| jumlah_stok | integer | ya | 0 | **tidak boleh negatif** |
| status_ketersediaan | varchar | ya | Merah | Pilihan: `Hijau` (>100), `Kuning` (>0), `Merah` (0). **Kolom lama**; status yang ditampilkan dihitung dari daya tahan (§3.3) |
| created_at / updated_at | datetime | tidak |  | `updated_at` = "stok terakhir diperbarui" |

Satu baris per pasangan (lokasi_id, logistik_id). Stok **hanya** berubah lewat barang masuk/keluar.

### `stok_masuks` (barang masuk, menambah stok)
| Kolom | Tipe | Wajib | Keterangan |
|---|---|---|---|
| id | integer | ya | PK |
| kode | varchar(36) | tidak | UNIQUE. UUID catatan dari aplikasi; kosong = dicatat di website |
| lokasi_id | integer | ya | FK → lokasis.id (cascade) |
| logistik_id | integer | ya | FK → logistiks.id (cascade) |
| relawan_id | integer | tidak | FK → relawans.id (set null) — pencatat (kosong untuk catatan dari paket; nama pencatat ditulis di keterangan) |
| jumlah_masuk | integer | ya | ≥ 1 |
| sumber_bantuan | varchar | ya | contoh BNPB, BPBD, nama donatur |
| tanggal_masuk | date | ya | tidak boleh setelah hari ini |
| keterangan | text | tidak | |
| created_at / updated_at | datetime | tidak | |

### `stok_keluars` (barang keluar, mengurangi stok)
| Kolom | Tipe | Wajib | Keterangan |
|---|---|---|---|
| id | integer | ya | PK |
| kode | varchar(36) | tidak | UNIQUE. UUID catatan dari aplikasi; kosong = dicatat di website |
| lokasi_id | integer | ya | FK → lokasis.id (cascade) |
| logistik_id | integer | ya | FK → logistiks.id (cascade) |
| user_id | integer | tidak | FK → users.id (set null) — pencatat (admin juga bisa; kosong untuk catatan dari paket) |
| jumlah_keluar | integer | ya | ≥ 1 dan **≤ stok saat itu** |
| tujuan | varchar(30) | ya | Pilihan: `dibagikan` (Dibagikan ke pengungsi), `dapur_umum` (Dimasak di dapur umum), `dikirim` (Dikirim ke posko lain), `rusak` (Rusak / kedaluwarsa / hilang), `lainnya` (Lainnya) |
| penerima | varchar | tidak | penerima / posko tujuan |
| tanggal_keluar | date | ya | tidak boleh setelah hari ini |
| keterangan | text | tidak | |
| created_at / updated_at | datetime | tidak | |

### `permintaan_operasionals` (pengajuan relawan mulai/tutup operasi)
| Kolom | Tipe | Wajib | Default | Keterangan |
|---|---|---|---|---|
| id | integer | ya |  | PK |
| lokasi_id | integer | ya |  | FK → lokasis.id (cascade) |
| user_id | integer | tidak |  | FK → users.id (set null) — relawan peminta |
| aksi | varchar(10) | ya |  | `mulai` atau `tutup` |
| status | varchar(15) | ya | menunggu | `menunggu`, `disetujui`, `ditolak` |
| alasan | text | tidak |  | alasan dari relawan |
| catatan_admin | text | tidak |  | catatan admin saat menolak |
| diproses_oleh | integer | tidak |  | FK → users.id (set null) — admin |
| diproses_at | datetime | tidak |  |  |
| created_at / updated_at | datetime | tidak |  |  |

Maksimal **satu** permintaan `menunggu` per posko.

### `notifications` (notifikasi dalam aplikasi)
| Kolom | Tipe | Wajib | Keterangan |
|---|---|---|---|
| id | varchar (UUID) | ya | PK |
| type | varchar | ya | `App\Notifications\PemberitahuanPosko` |
| notifiable_type / notifiable_id | varchar / integer | ya | penerima: `App\Models\User` + users.id |
| data | text (JSON) | ya | `{"judul","pesan","url","ikon","warna","lokasi_id"}`; warna: green/red/amber/blue/slate |
| read_at | datetime | tidak | kosong = belum dibaca |
| created_at / updated_at | datetime | tidak | |

### `petugas_poskos` (petugas lapangan dari aplikasi offline)
| Kolom | Tipe | Wajib | Keterangan |
|---|---|---|---|
| id | integer | ya | PK |
| lokasi_id | integer | ya | FK → lokasis.id (cascade) |
| kode_perangkat | varchar(36) | ya | dari `info.csv` paket |
| kode_akun | varchar(36) | tidak | kode unik akun di aplikasi (sama di semua HP satu posko); kosong untuk paket lama |
| peran | varchar(10) | ya | `admin` (penanggung jawab) / `relawan` |
| nama | varchar | ya | |
| kontak | varchar | tidak | email / nomor HP |
| paket_dibuat_pada | datetime | tidak | waktu ekspor paket; paket lebih lama tidak menimpa |
| created_at / updated_at | datetime | tidak | |

Hanya nama & kontak untuk dihubungi, **bukan akun website**. Lihat §8.7a.

### Tabel lama yang **tidak dipakai** website (abaikan)
`riwayat_pengungsis` dan `sphere_pengungsis` ada di database tetapi tidak dipakai kode mana pun.
Tidak perlu dibuat di aplikasi.

---

## 3. Aturan bisnis & rumus (wajib sama dengan website)

### 3.1 Siklus posko
1. Relawan melapor posko → `lokasis` + `status_lokasis(pending)`, `status_operasional = siaga`,
   kapasitas = round(luas ÷ 3,5), `sphere_lokasis` dihitung dari kapasitas.
2. Admin **menyetujui** (`approved`) atau **menolak** (`rejected`, dengan catatan). Posko yang ditolak **tidak tampil**
   untuk publik; jika sedang beroperasi, operasinya ditutup otomatis.
3. Operasional (hanya posko `approved`): `siaga → beroperasi → selesai → siaga`.
   - Hanya **admin** yang mengubah status. **Relawan mengajukan** permintaan `mulai`/`tutup`; admin setujui/tolak.
   - Jika admin mengubah status langsung, permintaan yang menunggu otomatis diselesaikan
     (aksi sama → `disetujui`, berbeda → `ditolak` dengan catatan "Status posko sudah diubah langsung oleh admin.").
   - **Hari ke-N** = (tanggal hari ini − tanggal mulai beroperasi) + 1.

### 3.2 Pengungsi
- Kelompok usia: Balita < 5, Anak 5–12, Remaja 13–17, Dewasa 18–59, Lansia ≥ 60.
- Keterisian (%) = min(100, round(jumlah pengungsi ÷ kapasitas × 100)).
- Kebutuhan khusus kelompok rentan (teks yang ditampilkan):
  Bayi → susu formula / MPASI, popok, selimut bayi · Ibu Hamil → makanan bergizi, vitamin, pemeriksaan kehamilan ·
  Lansia → obat-obatan rutin, alas tidur yang layak, makanan lunak · Disabilitas → alat bantu (kursi roda, tongkat),
  akses toilet yang mudah · Sakit → obat-obatan, tenaga kesehatan.
- Nama & data kesehatan pengungsi **hanya untuk pengelola posko** (admin & relawan penanggung jawab);
  publik/relawan lain hanya melihat **jumlah**.

### 3.3 Logistik (rumus utama — harus identik)
Untuk setiap barang di posko (barang yang ada stoknya **ditambah semua barang pokok walaupun belum pernah masuk**, stok = 0):

```
kebutuhan_per_hari = logistiks.kebutuhan_harian × jumlah_pengungsi_posko
cukup_hari         = jumlah_stok ÷ kebutuhan_per_hari          (null jika kebutuhan_per_hari = 0)
status:  null → "netral" (Belum dihitung)
         cukup_hari < 1 → "merah"  (Kritis)
         cukup_hari < 3 → "kuning" (Menipis)
         selainnya      → "hijau"  (Aman)
kekurangan (perlu dikirim) = max(0, ceil(kebutuhan_per_hari × TARGET_HARI − jumlah_stok))   TARGET_HARI = 7
mendesak = status merah atau kuning
```

**Teks jumlah hari untuk pengguna (tanpa desimal):** 0 → "Sudah habis"; < 1 → "Habis hari ini";
≥ 1 → floor(hari) + " hari" (contoh 2,7 → "2 hari", **dibulatkan ke bawah**), atau "Habis dalam 2 hari".

**Status bantuan posko** (hanya posko `beroperasi`):
| Tingkat | Syarat (dicek berurutan) |
|---|---|
| `cukup` | jumlah pengungsi = 0 |
| `segera` (Butuh bantuan segera) | ada barang `merah` |
| `pantau` (Perlu dipantau) | ada barang `kuning`, atau keterisian ≥ 90% |
| `belum` (Belum bisa dihitung) | tidak ada barang, atau semua barang `netral` |
| `cukup` | selainnya |

Urutan daftar "Posko Butuh Bantuan": segera → pantau → belum → cukup, lalu cukup_hari terkecil.

### 3.4 Barang masuk / keluar
- Masuk: stok += jumlah_masuk (buat baris `stok_logistiks` jika belum ada).
- Keluar: tolak jika jumlah_keluar > stok ("Stok tidak cukup. Sisa stok hanya N satuan."); stok −= jumlah_keluar.
- Hanya admin & relawan penanggung jawab posko yang boleh mencatat.
- Tanggal tidak boleh setelah hari ini (WIB).
- Kolom lama `status_ketersediaan`: Hijau jika stok > 100, Kuning jika > 0, Merah jika 0 (tetap diisi agar konsisten).

---

## 4. Perbandingan dengan skema aplikasi (awal: `lib/data/database_tables.dart` versi 3)

> **Status 23 Okt 2026: semua baris di tabel ini sudah dikerjakan** (aplikasi database versi 7).
> Tabel tetap disimpan sebagai catatan alasan perubahan.

| Aplikasi sekarang | Website | Yang perlu disesuaikan |
|---|---|---|
| `posko` (id, nama UNIQUE, alamat) | `lokasis` + `desas` + `status_lokasis` + `sphere_lokasis` | tambah desa, relawan penanggung jawab, luas, **kapasitas otomatis**, koordinat, gambar, status persetujuan (riwayat), `status_operasional` + waktu mulai/selesai |
| `pengungsi.posko` = **nama** posko (TEXT) | `pengungsis.lokasi_id` = **id** (FK) | ganti rujukan nama → id posko (migrasi data: cocokkan nama posko ke id) |
| `pengungsi`: nik, kondisi, domisili | nomor_kk, kondisi_kesehatan, asal, + tanggal_lahir, kelompok_rentan, riwayat_penyakit, foto | `nik` → website menyimpan **nomor_kk** (16 digit); `kondisi` → `kondisi_kesehatan`; `domisili` → `asal`; tambah kolom yang belum ada; pakai pilihan jenis_kelamin & kelompok_rentan persis |
| `logistik` (nama_barang, kategori teks, jumlah, satuan, posko) — satu tabel | dipisah: `kategori_logistiks`, `logistiks` (master + kebutuhan_harian), `stok_logistiks` (stok), `stok_masuks`, `stok_keluars` | pisahkan master barang dari stok per posko; jumlah stok hanya berubah lewat catatan masuk/keluar; tambah tujuan barang keluar |
| `riwayat_transaksi` (log import/ekspor aplikasi) | tidak ada padanan (riwayat logistik = `stok_masuks` + `stok_keluars`) | boleh dipertahankan sebagai log lokal aplikasi |
| `admin` (username, email, password_hash + salt, role, status) | `users` + peran `admin` | website memakai **bcrypt**; hash aplikasi tidak bisa dipakai di website — akun tetap dibuat/diatur di website |
| `relawan` (nama, **pin**, posko nama, kontak, status Aktif) | `users` (email+password, status pending/approved/rejected/suspended) + `relawans` + peran `relawan`; posko lewat `lokasis.relawan_id` | **Diputuskan: tetap akun lokal dengan PIN** (tidak disamakan dengan website). Cukup ubah relasi posko dari nama → id posko lokal. Nama relawan ikut di paket sebagai `dicatat_oleh` / `relawan_penanggung_jawab` (teks saja, lihat §8.9) |
| — | `permintaan_operasionals`, `notifications` | opsional di aplikasi offline; jika dibuat, pakai nilai `aksi`/`status` yang sama |

Saran urutan: (1) master: desa, kategori, logistik; (2) posko (`lokasis`) + status operasional;
(3) pengungsi dengan `lokasi_id`; (4) stok, barang masuk & keluar; (5) rumus §3.3 & tampilan "Butuh Bantuan";
(6) ekspor Paket Data Posko (§8).
Lakukan sebagai migrasi SQLite versi 4+ yang **memindahkan data lama**, bukan membuat ulang tabel kosong.

**Kolom tambahan khusus aplikasi** (belum ada di website, dibutuhkan untuk penggabungan tanpa dobel):
- `kode` (TEXT, UUID v4 huruf kecil, UNIQUE, dibuat **sekali** saat baris dibuat, tidak pernah berubah) di tabel
  posko, pengungsi, barang masuk, barang keluar.
- `diubah_pada` (datetime) di posko & pengungsi, diperbarui setiap kali data diubah.
- `dihapus_pada` (datetime, boleh kosong) di pengungsi: **jangan hapus baris secara fisik**; isi kolom ini agar
  penghapusan ikut terkirim ke pusat.
- Barang masuk & keluar bersifat **catatan tetap** (tidak diedit/dihapus). Koreksi = buat catatan baru
  (misal barang keluar tujuan `lainnya` dengan keterangan "koreksi").

Status persetujuan (`status_lokasis`) & permintaan operasional **tidak perlu** di aplikasi: di posko offline tidak ada
admin website. Admin lokal boleh mengubah `status_operasional` posko (siaga/beroperasi/selesai) di HP.

---

## 5. Format pertukaran data (file) — sama dengan template import website

Website menerima **.xlsx / .xls / .csv** (CSV boleh pemisah `,` atau `;`). Baris pertama = judul kolom; judul tidak
peka huruf besar/kecil. Website menampilkan **pratinjau** dan memvalidasi tiap baris sebelum menyimpan.
Agar file dari aplikasi bisa langsung diimpor, **ekspor dengan judul kolom & urutan berikut**:

**Pengungsi** (diimpor per posko di halaman Kelola Pengungsi)
| Kolom | Wajib | Aturan |
|---|---|---|
| Nama | ya | |
| Asal | ya | |
| Tanggal Lahir | ya | `YYYY-MM-DD` (juga diterima `DD-MM-YYYY`, `DD/MM/YYYY`) |
| Nomor KK | tidak | boleh kosong; jika diisi 16 digit, tulis sebagai **teks** |
| Usia | tidak | kosong = dihitung dari tanggal lahir; jika diisi harus sesuai (±1 tahun) |
| Jenis Kelamin | ya | `Laki-laki` / `Perempuan` (L/P juga diterima) |
| Kondisi Kesehatan | tidak | kosong = Sehat |
| Kelompok Rentan | tidak | Tidak / Ibu Hamil / Lansia / Bayi / Disabilitas / Sakit; kosong = Tidak |
| Riwayat Penyakit | tidak | |

**Master Logistik** (admin): Nama Item*, Kategori* (harus nama kategori yang ada), Satuan*, Kebutuhan Harian (boleh koma desimal, contoh 0,4), Deskripsi.
**Lokasi/Posko** (admin): Nama Lokasi*, Nama Desa* (harus terdaftar), Email Relawan*, Alamat Lokasi*, Luas Lokasi (m2)*, Latitude*, Longitude*. Kapasitas & SPHERE dihitung otomatis.
**Relawan** (admin): Nama Lengkap*, Email*. Akun baru langsung disetujui dengan password awal `relawan123`.
**Desa** (admin): Nama Desa*, Alamat Desa*.

> Template di atas adalah import **per jenis data** yang sudah ada di website (dipakai admin website).
> Untuk mengirim data dari aplikasi ke pusat, pakai **Paket Data Posko (§8)**: satu ZIP berisi posko, pengungsi,
> barang masuk & keluar dengan `kode` unik, sehingga paket dari banyak posko bisa digabung tanpa dobel.

Format angka: website menerima `1.250` (= 1250), `1.250,5`, `2,5`. Koordinat memakai titik/koma desimal.

---

## 6. Fitur aplikasi yang harus diselaraskan dengan website

| Fitur | Di aplikasi (offline) | Acuan |
|---|---|---|
| **Admin lokal** | Tetap ada di tiap HP. Akun bawaan `admin`/`admin123` **wajib ganti password** saat pertama dipakai (semua HP memakai bawaan yang sama → setiap pemegang HP bisa jadi admin jika tidak diganti). Admin lokal: kelola relawan lokal (PIN), kelola posko, master barang, ekspor paket | §1 |
| **Relawan lokal** | Login PIN; hanya mengelola posko yang ditugaskan ke akunnya | §1 |
| **Posko** | Data posko sesuai `lokasis` (desa, alamat, luas → kapasitas otomatis, koordinat, status operasional, hari ke-N) | §2, §3.1 |
| **Pengungsi** | Biodata & kesehatan dengan kolom, pilihan, dan validasi sama dengan website (KK opsional, jika diisi 16 digit; usia sesuai tanggal lahir, jenis kelamin & kelompok rentan persis, deteksi orang yang sama = nama + tanggal lahir) | §2 `pengungsis`, §5 |
| **Komposisi & perhatian medis** | Jumlah per jenis kelamin, kelompok usia, kelompok rentan + kebutuhan khususnya; daftar pengungsi sakit | §3.2 |
| **Master barang** | Nama barang, kategori, satuan, kebutuhan harian **sama dengan website** (isi awal: kategori §2, Beras 0,3 kg, Air Mineral 3 liter). Sebaiknya bisa diperbarui dari file **Export Master Logistik** website | §2 `logistiks` |
| **Barang masuk / keluar** | Stok hanya berubah lewat catatan; keluar tidak boleh melebihi stok; pilihan tujuan sama; tanggal ≤ hari ini | §3.4 |
| **Kebutuhan bantuan** | Status Kritis/Menipis/Aman, "cukup untuk N hari" tanpa desimal, jumlah perlu dikirim untuk 7 hari, status posko segera/pantau/cukup — **rumus identik** | §3.3 |
| **Ekspor Paket Data Posko** | Tombol ekspor untuk dibawa lewat USB ke pusat | §8 |

## 7. Yang **tidak** perlu ditiru di aplikasi
- Migrate lewat browser (`/_deploy/...`), struktur folder hosting, `.htaccess` — khusus server website.
- Grafik dashboard admin, peta Leaflet (opsional; jika dibuat, warna & label status sama seperti §3).
- Tabel `riwayat_pengungsis`, `sphere_pengungsis` (tidak dipakai).
- Verifikasi laporan posko, permintaan mulai/tutup operasi, notifikasi lonceng, login akun website (semua khusus website).

---

## 8. Format Paket Data Posko (ekspor aplikasi → USB → import di website pusat)

> **Format ini wajib diikuti persis.** Website pusat memakai format yang sama di menu admin
> *Import Paket Data Posko* (`/admin/paket-data`), yang menggabungkan paket dari banyak posko.
> Ekspor di aplikasi: menu admin lokal Data → Ekspor Paket Data Posko (`lib/services/paket_data_service.dart`).

### 8.1 Bentuk file
- Satu file **ZIP**: `paket_<nama-posko-tanpa-spasi>_<YYYYMMDD-HHMM>.zip` berisi file CSV di bawah (di akar ZIP).
  Jika admin mengekspor semua posko di HP sekaligus, nama posko diganti `SemuaPosko`.
- CSV: **UTF-8**, pemisah **koma**, baris pertama = judul kolom **persis** seperti di tabel, nilai yang mengandung
  koma/kutip/baris baru dibungkus tanda kutip ganda (`"`) dengan kutip di dalamnya digandakan (`""`).
- Tanggal `YYYY-MM-DD`; tanggal-waktu `YYYY-MM-DD HH:MM:SS` **WIB**; desimal memakai **titik** (`0.3`).
- Kolom yang tidak ada nilainya dikosongkan (bukan `null`/`-`).
- Ekspor selalu berisi **seluruh data posko** (bukan hanya yang baru). Mengimpor paket yang sama dua kali,
  atau paket lama setelah paket baru, **tidak boleh** membuat data dobel — itulah fungsi kolom `kode`.

### 8.2 `info.csv` (1 baris)
| Kolom | Isi |
|---|---|
| versi_format | `1` |
| dibuat_pada | waktu ekspor |
| kode_perangkat | UUID tetap per instalasi aplikasi (dibuat sekali, disimpan di HP) |
| diekspor_oleh | nama akun yang mengekspor |
| peran_perangkat | `induk` / `anggota` (§9). Kolom tambahan: boleh tidak ada di paket lama |

### 8.3 `posko.csv` (1 baris per posko di HP)
| Kolom | Wajib | Isi |
|---|---|---|
| kode_posko | ya | UUID posko |
| nama_lokasi | ya | |
| nama_desa | ya | harus sama dengan nama desa di website (jika tidak ada, pusat menambahkannya) |
| alamat_lokasi | ya | |
| luas_lokasi | ya | m² |
| kapasitas_pengungsi | ya | round(luas ÷ 3,5) |
| latitude / longitude | ya | |
| status_operasional | ya | `siaga` / `beroperasi` / `selesai` |
| mulai_beroperasi_at / selesai_beroperasi_at | tidak | |
| relawan_penanggung_jawab | tidak | nama relawan lokal |
| diubah_pada | ya | |

Pusat mencocokkan posko dengan `kode_posko`; jika belum pernah diimpor, dicocokkan dengan **nama_lokasi + nama_desa**
(posko yang sudah ada di website), jika tidak ketemu dibuat sebagai posko baru.

### 8.4 `pengungsi.csv`
| Kolom | Wajib | Isi |
|---|---|---|
| kode | ya | UUID pengungsi |
| kode_posko | ya | merujuk `posko.csv` |
| nama, asal, tanggal_lahir, nomor_kk, usia, jenis_kelamin, kondisi_kesehatan, kelompok_rentan, riwayat_penyakit | sesuai §5 | nilai & aturan sama dengan template import pengungsi website. **nomor_kk boleh kosong**; **tanggal_lahir boleh kosong** hanya untuk data lama dari aplikasi versi sebelumnya (aplikasi memberi peringatan sebelum ekspor) |
| dicatat_oleh | tidak | nama akun lokal |
| dibuat_pada / diubah_pada | ya | |
| dihapus_pada | tidak | diisi jika pengungsi dihapus di HP |
| status_keberadaan | tidak | `di_posko` / `pulang` / `pindah` / `dirujuk` (sejak 27 Okt 2026; kosong / paket lama = `di_posko`) |
| keluar_pada | tidak | tanggal keluar posko `YYYY-MM-DD` (hanya jika bukan `di_posko`) |
| keterangan_keluar | tidak | posko tujuan / RS / keterangan pulang |

Aturan gabung: `kode` belum ada → tambah; sudah ada → ditimpa **hanya jika** `diubah_pada` paket lebih baru;
`dihapus_pada` terisi → dihapus di pusat. Foto pengungsi **tidak** ikut paket.
Tambahan di website: `kode` yang ada di `paket_penghapusans` dilewati; pengungsi dengan nama + tanggal lahir sama
di posko yang sama (dicatat langsung di website, belum punya kode) **disambungkan**, bukan dibuat dobel.

### 8.5 `barang_masuk.csv`
| Kolom | Wajib | Isi |
|---|---|---|
| kode | ya | UUID catatan |
| kode_posko | ya | |
| nama_item | ya | harus sama dengan nama barang di Master Logistik website |
| kategori | ya | nama kategori (§2) |
| satuan | ya | |
| jumlah_masuk | ya | bilangan bulat ≥ 1 |
| sumber_bantuan | ya | |
| tanggal_masuk | ya | |
| keterangan | tidak | |
| dicatat_oleh | tidak | |
| dibuat_pada | ya | |

### 8.6 `barang_keluar.csv`
| Kolom | Wajib | Isi |
|---|---|---|
| kode | ya | UUID catatan |
| kode_posko | ya | |
| nama_item, kategori, satuan | ya | seperti barang masuk |
| jumlah_keluar | ya | bilangan bulat ≥ 1 |
| tujuan | ya | `dibagikan` / `dapur_umum` / `dikirim` / `rusak` / `lainnya` |
| penerima | tidak | |
| tanggal_keluar | ya | |
| keterangan | tidak | |
| dicatat_oleh | tidak | |
| dibuat_pada | ya | |

Aturan gabung barang masuk/keluar: `kode` yang sudah pernah diimpor **dilewati** (tidak menambah/mengurangi stok
lagi). Stok di pusat ditentukan **dari catatan**, bukan dari angka stok HP: website menambah/mengurangi
`stok_logistiks` sebesar catatan yang **baru** diimpor (tidak menjumlah ulang semua catatan, karena stok lama di
website mungkin ada sebelum tabel `stok_keluars` dibuat). Stok tidak pernah dibuat negatif.
Barang yang belum ada di Master Logistik website (kategori + nama) ditambahkan otomatis tanpa kebutuhan harian.

### 8.7 `stok.csv` (untuk pengecekan saja)
| Kolom | Isi |
|---|---|
| kode_posko, nama_item, satuan | |
| jumlah_stok | stok di HP saat ekspor |

Tidak dipakai untuk menimpa stok pusat; hanya untuk menampilkan peringatan jika hasil hitung ulang berbeda.

### 8.7a `petugas.csv` (siapa yang bertanggung jawab)
Admin lokal = **penanggung jawab / supervisor** di HP posko; relawan lokal = **petugas** posko. Supaya pusat tahu
siapa yang bisa dihubungi, paket mencantumkan nama & kontak akun **aktif** (akun nonaktif tidak ikut).

| Kolom | Wajib | Isi |
|---|---|---|
| kode_posko | tidak | **kosong untuk admin** (penanggung jawab semua posko di paket); untuk relawan = posko tugasnya |
| peran | ya | `admin` / `relawan` |
| nama | ya | nama akun lokal |
| kontak | tidak | admin: email akun; relawan: email / nomor HP dari form relawan |
| kode_akun | tidak | kode unik akun (UUID), sama di HP Induk & HP Anggota. Kolom tambahan: boleh tidak ada di paket lama |

- **Tidak pernah** berisi PIN, password, hash, atau username.
- Paket dari beberapa HP satu posko berisi petugas yang sama; website menyimpan per HP tetapi menampilkan
  setiap akun **satu kali** (berdasarkan `kode_akun`, atau peran + nama + kontak untuk paket lama), dari paket terbaru.
- Aplikasi memberi peringatan sebelum ekspor jika tidak ada admin aktif, kontak admin masih bawaan
  (`admin@gmls.id`), atau ada posko tanpa relawan aktif.
- Website menyimpan ke tabel `petugas_poskos` (lokasi_id, kode_perangkat, peran, nama, kontak, paket_dibuat_pada):
  daftar per (posko, perangkat) **diganti utuh** hanya jika `info.dibuat_pada` paket lebih baru dari paket
  sebelumnya dari perangkat yang sama. Ditampilkan di pratinjau import dan di halaman detail Lokasi admin
  ("Petugas Lapangan (Aplikasi Offline)"). Petugas ini **bukan akun website** (tidak bisa login).
- File ini **opsional** bagi website: paket dari aplikasi versi sebelumnya (tanpa `petugas.csv`) tetap bisa
  diimpor dan tidak menghapus daftar petugas yang sudah ada. `versi_format` tetap `1`.

### 8.8 Keamanan file
Paket berisi data pribadi (nomor KK, tanggal lahir, kondisi kesehatan, serta nama & kontak petugas). Tampilkan peringatan saat ekspor:
simpan USB dengan aman dan **hapus file setelah diimpor di pusat**.
Di website, ZIP yang diunggah disimpan sementara di `storage/app/paket-import` (bukan folder publik) dan dihapus
setelah dikonfirmasi/dibatalkan; sisa file yang lebih dari 1 hari ikut dibersihkan.

### 8.9 Akun di paket (relawan PIN & admin lokal)
- Paket **tidak berisi akun** yang bisa dipakai login: tidak ada PIN, password, atau username akun lokal.
  Akun lokal tetap hanya di HP; akun website (`users`, `relawans`) **tidak diubah** oleh import.
- Yang dikirim hanya **nama & kontak** petugas aktif di `petugas.csv` (§8.7a), untuk dihubungi pusat.
- Nama akun lokal juga ikut sebagai **teks** keterangan: `diekspor_oleh` (info.csv), `relawan_penanggung_jawab`
  (posko.csv), `dicatat_oleh` (pengungsi & barang). Di website, `dicatat_oleh` barang ditulis di `keterangan`
  ("Dicatat oleh Andi (aplikasi offline)"); `dicatat_oleh` pengungsi tidak disimpan karena tabel `pengungsis`
  tidak punya kolom pencatat. Nama-nama ini tidak dihubungkan ke akun website.
- **Posko baru** dari paket wajib punya `lokasis.relawan_id`, jadi saat pratinjau admin pusat **memilih relawan
  website** penanggung jawabnya (nama relawan lokal ditampilkan sebagai bantuan). Posko yang sudah ada tetap memakai
  relawan website yang lama. Setelah itu relawan website tersebut bisa mengelola posko itu secara online seperti biasa.
- Posko baru dari paket langsung berstatus `approved` (ditambahkan oleh admin pusat, sama seperti import Lokasi).

---

## 9. Beberapa HP dalam satu posko (HP Induk & HP Anggota)

> Ditambahkan 24 Okt 2026. Satu posko bisa memakai lebih dari satu HP. Setiap HP punya database lokal sendiri,
> jadi data disatukan lewat **file** yang dikirim **tanpa internet** (Bluetooth / Quick Share / flashdisk).
> Versi rilis aplikasi tidak punya izin internet sama sekali.

### 9.1 Peran HP
| Peran | Dipegang | Boleh |
|---|---|---|
| **HP Induk** | admin lokal = **supervisor** posko | membuat posko, master barang, akun admin & relawan; menyiapkan HP Anggota; menggabungkan data dari HP Anggota; memutuskan pengungsi dobel; ekspor paket ke pusat |
| **HP Anggota** | relawan (admin boleh login untuk memantau) | mencatat pengungsi & barang masuk/keluar posko itu; sinkron dengan HP Induk. Akun, posko, dan master barang **hanya bisa dilihat** ("Dikelola di HP Induk") |

Semua HP yang dipakai sebelum fitur ini otomatis menjadi HP Induk (perilaku tidak berubah). Peran disimpan di
pengaturan HP (`peran_perangkat`) dan dikirim ke pusat di `info.csv` (§8.2).

### 9.2 File Penyiapan (`penyiapan_<posko>_<YYYYMMDD-HHMM>.zip`)
Dibuat di HP Induk (Data Posko → posko → **Siapkan HP Anggota**), diterapkan di HP baru (layar awal →
**Siapkan HP ini dari HP Induk**). Isi `penyiapan.json`:
- identitas posko dengan **kode yang sama** (semua HP mencatat posko yang sama, tidak bergantung pada nama);
- master barang & kategori;
- akun relawan posko itu: nama, kontak, status, **PIN yang diacak** (PBKDF2-HMAC-SHA256, 20.000 putaran,
  satu salt per HP Induk yang ikut di file). PIN asli **tidak pernah** ikut;
- akun admin: password yang sudah diacak. Admin yang masih memakai password bawaan **tidak ikut**, dan akun
  bawaan `admin`/`admin123` dihapus dari HP Anggota.

Hanya diterima oleh HP yang masih kosong, atau HP Anggota dari HP Induk & posko yang sama (memperbarui akun).
Akun yang dihapus di HP Induk ikut terhapus di HP Anggota.

### 9.3 File Sinkron (`sinkron_<posko>_dari-anggota|dari-induk_<YYYYMMDD-HHMM>.zip`)
Isi = file CSV Paket Data Posko (§8) untuk satu posko; dari HP Induk **ditambah** `penyiapan.json` (pembaruan
akun & master barang). Alur:
1. Setiap HP Anggota **mengirim** datanya ke HP Induk; HP Induk **menggabungkan** (aturan sama dengan §8:
   kode, versi terbaru menang, penghapusan ikut, catatan barang tidak dobel).
2. HP Induk **mengirim** data gabungan ke setiap HP Anggota, sehingga semua HP berisi data & akun yang sama.

Aturan tambahan:
- HP Induk tidak pernah menerima akun dari file. HP Anggota menolak file dari HP Induk / posko lain.
- Paket buatan HP itu sendiri ditolak. Waktu dibandingkan per detik (paket menyimpan sampai detik).
- Peringatan jika file dibuat > 10 menit di depan jam HP penerima: **tanggal & jam semua HP harus benar**.

### 9.4 Stok & barang keluar
- HP Induk: barang keluar tetap ditolak jika melebihi stok (aturan website).
- HP Anggota: boleh melebihi stok yang tercatat di HP itu **setelah konfirmasi**, karena barang masuknya mungkin
  dicatat di HP lain dan belum disinkron.
- Stok minus (keluar > masuk yang tercatat) ditandai di layar Logistik, di Tugas Hari Ini (prioritas tinggi),
  dan setelah sinkron, untuk dicek supervisor. `stok.csv` ke pusat bisa berisi angka minus; website tetap tidak
  membuat stok pusat minus dan menampilkan peringatan selisih.

### 9.5 Pengungsi dobel dari beberapa HP
Orang yang sama (nama + tanggal lahir sama di posko yang sama) yang dicatat di HP berbeda punya kode berbeda.
HP Induk menampilkan "Kemungkinan Dobel"; supervisor memilih:
- **Pakai data N**: data itu dipakai, isian kosong (nomor KK, asal, riwayat penyakit, kelompok rentan) dilengkapi
  dari duplikatnya, duplikatnya **ditandai dihapus** → ikut terhapus di HP Anggota & pusat.
- **Bukan orang yang sama**: disimpan di HP Induk; ditampilkan lagi hanya jika ada data baru yang ikut cocok.

### 9.6 Dampak bagi pusat (website)
- Paket boleh dikirim dari HP Induk (disarankan, datanya paling lengkap) **maupun** HP Anggota: karena kode posko,
  pengungsi, dan catatan barang sama di semua HP, paket kedua tidak membuat data dobel dan stok tidak
  terhitung dua kali (diuji dengan paket asli dari dua HP).
- Petugas yang sama dari beberapa HP ditampilkan satu kali (`kode_akun`, §8.7a).
- Pratinjau import menampilkan asal paket ("dari HP Induk (supervisor)" / "dari HP Anggota (relawan)").
- **Riwayat import** (tabel `riwayat_import_pakets` + `lokasi_riwayat_import_paket`, ditambahkan 25 Okt 2026):
  setiap paket yang digabung dicatat (nama file, HP pengirim & perannya, `diekspor_oleh`, waktu paket dibuat,
  admin pengimpor, jumlah data). Daftar ada di menu Import Paket Posko.
- **Paket terakhir** per posko tampil di daftar & detail Lokasi ("Paket terakhir: 2 hari lalu"; hijau ≤ 1 hari,
  kuning ≤ 3 hari, merah > 3 hari), dihitung dari `info.dibuat_pada` paket terbaru, karena posko offline tidak
  punya tanda lain apakah datanya di pusat masih baru.
- Master Logistik punya tombol **Export CSV (Aplikasi)**: file yang langsung bisa diimpor di aplikasi
  (Master Barang → Import), tanpa membuka Excel dulu. Kolom: Nama Item, Kategori, Satuan, Kebutuhan Harian
  (desimal koma), Deskripsi.
- **Selisih stok** (tabel `selisih_stok_pakets`): setelah paket digabung, barang yang `stok.csv`-nya berbeda dengan
  stok pusat dicatat (stok HP boleh minus). Menu Import Paket Posko → **Lihat selisih** menampilkan selisih dari
  paket **terbaru** tiap posko beserta dugaan penyebabnya; paket yang lebih baru menggantikan selisih lama.
  Stok pusat tidak diubah otomatis.
- **Pencatat pengungsi**: `pengungsi.csv` → `dicatat_oleh` disimpan di kolom `pengungsis.dicatat_oleh` dan tampil
  di detail pengungsi. Paket yang kolomnya kosong tidak menghapus nama yang sudah ada.
- **Keberadaan pengungsi** (`pengungsis.status_keberadaan`, `keluar_pada`, `keterangan_keluar`, 27 Okt 2026):
  pengungsi yang pulang / pindah posko / dirujuk **tidak dihapus**, tapi disembunyikan dari query biasa
  (global scope di model `Pengungsi`) sehingga tidak dihitung untuk kapasitas, kebutuhan, komposisi, dan
  dashboard. Lihat semua: `Pengungsi::semuaKeberadaan()`; query `DB::table` memakai `Pengungsi::tabelDiPosko()`.
  Halaman pengungsi relawan menampilkan daftar "Sudah keluar posko" (hanya lihat).

### 9.7 Kesiapan lapangan aplikasi (27 Okt 2026)
- ID aplikasi Android **`id.gmls.logeva`**, nama **LOGEVA**, APK rilis ditandatangani kunci GMLS
  (`android/key.properties` + `android/app/logeva-rilis.jks`, tidak ikut git; **wajib dicadangkan**).
- **Cadangan & pemulihan** seluruh data HP (`cadangan_logeva_<waktu>.zip`): HP pengganti menjadi sama persis,
  termasuk kode perangkat, jadi HP Anggota tetap mengenali HP Induk pengganti. Hanya dari versi database yang sama.
- **Kunci otomatis** setelah 10 menit tidak dipakai.
- **Laporan harian** berupa teks (SMS/radio), tanpa nama pengungsi.

