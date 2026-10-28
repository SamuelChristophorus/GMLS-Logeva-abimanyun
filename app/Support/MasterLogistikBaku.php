<?php

namespace App\Support;

/**
 * DAFTAR MASTER LOGISTIK BAKU: sama persis dengan bawaan aplikasi offline
 * (lib/core/data_options.dart -> DataOptions.barangAwal). Ubah di kedua tempat.
 *
 * kebutuhan = per orang per hari; null = bukan barang habis pakai harian
 * (tidak dihitung "cukup berapa hari", tidak memicu peringatan stok kritis).
 * Id kategori = urutan KategoriLogistikSeeder.
 */
class MasterLogistikBaku
{
    /** [nama_item, kategori_logistik_id, satuan, kebutuhan_harian] */
    public const DAFTAR = [
        ['Beras', 1, 'kg', 0.3],
        ['Mie Instan', 1, 'bungkus', null],
        ['Telur Ayam', 1, 'butir', null],
        ['Air Mineral', 2, 'liter', 2.0],
        ['Baju', 3, 'potong', null],
        ['Celana', 3, 'potong', null],
        ['Selimut', 4, 'lembar', null],
        ['Tikar', 4, 'lembar', null],
        ['Pembalut', 5, 'pak', null],
        ['Sabun Mandi', 5, 'batang', null],
        ['Masker', 6, 'kotak', null],
        ['Paracetamol', 6, 'strip', null],
        ['Popok Bayi', 7, 'pak', null],
        ['Susu Formula', 7, 'kaleng', null],
        ['Minyak Goreng', 8, 'liter', null],
    ];

    /** Nama lama -> nama baku. Paket dengan nama lama otomatis masuk ke barang baku. */
    public const ALIAS = [
        'Pakaian Layak Pakai' => 'Baju',
    ];
}
