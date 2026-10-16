<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/**
 * Menyimpan file upload ke folder "storage" di dalam folder public.
 *
 *   localhost : public/storage/<folder>/...
 *   Hostinger : public_html/logeva/storage/<folder>/...
 *
 * Lokasi folder public diatur oleh public/index.php, jadi kode ini sama
 * untuk keduanya. Di tampilan, file dipanggil dengan asset('storage/' . $path).
 */
class Upload
{
    /**
     * @param  UploadedFile $file
     * @param  string       $folder  contoh: 'assets/lokasi/gambar'
     * @return string       path relatif untuk disimpan di database, contoh: 'assets/lokasi/gambar/1716_foto.jpg'
     */
    /** Ekstensi yang boleh disimpan (gambar saja) */
    private const EKSTENSI_GAMBAR = ['jpg', 'jpeg', 'jfif', 'png', 'gif', 'webp', 'bmp'];

    public static function simpan(UploadedFile $file, string $folder): string
    {
        // Pengaman tambahan: folder ini bisa diakses publik, jadi file selain gambar
        // (misalnya .php) tidak boleh disimpan walaupun lolos validasi form.
        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, self::EKSTENSI_GAMBAR, true)) {
            throw ValidationException::withMessages([
                'file' => 'File harus berupa gambar (JPG, JPEG, PNG, GIF, WEBP).',
            ]);
        }

        $folder = trim($folder, '/');
        $tujuan = public_path('storage/' . $folder);

        if (! is_dir($tujuan)) {
            mkdir($tujuan, 0755, true);
        }

        // Nama file aman (spasi & karakter aneh diganti "_")
        $nama = time() . '_' . preg_replace('/[^A-Za-z0-9._-]+/', '_', $file->getClientOriginalName());

        $file->move($tujuan, $nama);

        return $folder . '/' . $nama;
    }
}
