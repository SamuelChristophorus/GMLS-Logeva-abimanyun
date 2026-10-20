<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Notifikasi di ikon lonceng (disimpan di tabel notifications).
 * Dipakai untuk: permintaan mulai/tutup operasi (ke admin) dan
 * perubahan status operasional / hasil permintaan (ke relawan).
 */
class PemberitahuanPosko extends Notification
{
    /**
     * @param string $judul  contoh: 'Permintaan mulai operasi'
     * @param string $pesan  contoh: 'Raja meminta posko Vila Hejo mulai beroperasi.'
     * @param string $url    halaman yang dibuka saat notifikasi diklik
     * @param string $ikon   ikon Font Awesome, contoh: 'fa-play'
     * @param string $warna  green | red | amber | blue | slate
     */
    public function __construct(
        private string $judul,
        private string $pesan,
        private string $url,
        private string $ikon = 'fa-bell',
        private string $warna = 'blue',
        private ?int $lokasiId = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'judul'     => $this->judul,
            'pesan'     => $this->pesan,
            'url'       => $this->url,
            'ikon'      => $this->ikon,
            'warna'     => $this->warna,
            'lokasi_id' => $this->lokasiId,
        ];
    }
}
