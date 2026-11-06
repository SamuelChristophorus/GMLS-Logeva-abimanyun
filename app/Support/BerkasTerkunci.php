<?php

namespace App\Support;

use App\Exceptions\KataSandiPaketException;
use ZipArchive;

/**
 * Membuka Paket Data Posko yang DIKUNCI kata sandi posko oleh aplikasi offline
 * (lib/utils/berkas_terkunci.dart). File tetap .zip berisi:
 *   - terkunci.enc : "LGVENC01" | iterasi (uint32 BE) | salt (16) | iv (12) | ciphertext | tag (16)
 *   - BACA.txt
 * Kunci = PBKDF2-HMAC-SHA256(kata sandi, salt, iterasi, 32 byte); AES-256-GCM, AAD = "LGVENC01".
 */
class BerkasTerkunci
{
    public const NAMA_ISI = 'terkunci.enc';
    private const MAGIC = 'LGVENC01';

    /** Iterasi PBKDF2 untuk paket yang dikunci website (pembaca menerima 1.000 – 1.000.000). */
    public const ITERASI = 100000;

    /**
     * Kebalikan dari buka(): kunci isi paket (byte ZIP) dengan kata sandi, lalu bungkus jadi
     * ZIP berisi terkunci.enc + BACA.txt — format yang sama dengan paket terkunci dari aplikasi.
     *
     * @return string path file ZIP terkunci (sementara)
     */
    public static function kunci(string $isiZip, string $kataSandi): string
    {
        $salt  = random_bytes(16);
        $iv    = random_bytes(12);
        $kunci = hash_pbkdf2('sha256', $kataSandi, $salt, self::ITERASI, 32, true);
        $tag   = '';
        $ciphertext = openssl_encrypt($isiZip, 'aes-256-gcm', $kunci, OPENSSL_RAW_DATA, $iv, $tag, self::MAGIC, 16);

        $path = tempnam(sys_get_temp_dir(), 'paket_kunci_');
        $zip  = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString(self::NAMA_ISI, self::MAGIC . pack('N', self::ITERASI) . $salt . $iv . $ciphertext . $tag);
        $zip->setCompressionName(self::NAMA_ISI, ZipArchive::CM_STORE);
        $zip->addFromString('BACA.txt', "Paket Data Posko LOGEVA (dari website GMLS).\r\n"
            . "Isi paket dikunci kata sandi karena berisi data pribadi pengungsi.\r\n"
            . "Buka lewat aplikasi LOGEVA: menu Impor Paket, lalu ketik kata sandi yang diberikan admin GMLS.\r\n");
        $zip->close();

        return $path;
    }

    public static function terkunci(string $path): bool
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            return false;
        }
        $ada = $zip->statName(self::NAMA_ISI) !== false;
        $zip->close();
        return $ada;
    }

    /**
     * Isi asli (byte ZIP paket) dengan mencoba setiap kata sandi di $kandidat.
     *
     * @return array{0: string, 1: string} [isi, kata sandi yang cocok]
     * @throws KataSandiPaketException kata sandi kosong / tidak ada yang cocok
     */
    public static function buka(string $path, array $kandidat): array
    {
        $zip = new ZipArchive;
        $zip->open($path);
        $enc = (string) $zip->getFromName(self::NAMA_ISI);
        $zip->close();

        $awal = 8 + 4 + 16 + 12;
        if (strlen($enc) < $awal + 16 || substr($enc, 0, 8) !== self::MAGIC) {
            throw new KataSandiPaketException('File terkunci rusak atau bukan dari aplikasi LOGEVA.', false);
        }
        $iterasi = unpack('N', substr($enc, 8, 4))[1];
        $salt = substr($enc, 12, 16);
        $iv = substr($enc, 28, 12);
        $ciphertext = substr($enc, $awal, -16);
        $tag = substr($enc, -16);
        if ($iterasi < 1000 || $iterasi > 1000000) {
            throw new KataSandiPaketException('File terkunci rusak atau bukan dari aplikasi LOGEVA.', false);
        }

        $kandidat = array_values(array_unique(array_filter($kandidat, fn ($k) => is_string($k) && $k !== '')));
        if (! $kandidat) {
            throw new KataSandiPaketException('Paket ini dikunci kata sandi posko. Ketik kata sandi posko lalu upload lagi.');
        }
        foreach ($kandidat as $kataSandi) {
            $kunci = hash_pbkdf2('sha256', $kataSandi, $salt, $iterasi, 32, true);
            $isi = openssl_decrypt($ciphertext, 'aes-256-gcm', $kunci, OPENSSL_RAW_DATA, $iv, $tag, self::MAGIC);
            if ($isi !== false) {
                return [$isi, $kataSandi];
            }
        }
        throw new KataSandiPaketException('Kata sandi posko salah. Tanyakan ke supervisor posko (Settings → Kata Sandi Posko di HP Induk).');
    }
}
