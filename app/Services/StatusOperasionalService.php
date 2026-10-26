<?php

namespace App\Services;

use App\Models\Lokasi;
use App\Models\PermintaanOperasional;
use App\Models\User;
use App\Notifications\PemberitahuanPosko;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Satu-satunya tempat status operasional posko diubah.
 *
 *  - ADMIN   : mulai / tutup / siaga langsung   -> relawan penanggung jawab dapat notifikasi.
 *  - RELAWAN : hanya MENGAJUKAN mulai / tutup  -> semua admin dapat notifikasi,
 *              admin menyetujui (status berubah) atau menolak (relawan dapat notifikasi).
 */
class StatusOperasionalService
{
    /* =========================================================
     |  ADMIN: ubah status langsung
     ========================================================= */

    /**
     * @throws DomainException jika aksi tidak bisa dilakukan pada kondisi posko saat ini
     */
    public function ubah(Lokasi $lokasi, string $aksi, User $admin, ?PermintaanOperasional $dariPermintaan = null): void
    {
        $this->pastikanBisa($lokasi, $aksi);

        DB::transaction(function () use ($lokasi, $aksi, $admin, $dariPermintaan) {
            $lokasi->update(match ($aksi) {
                'mulai' => [
                    'status_operasional'    => Lokasi::OPS_BEROPERASI,
                    'mulai_beroperasi_at'   => now(),
                    'selesai_beroperasi_at' => null,
                ],
                'tutup' => [
                    'status_operasional'    => Lokasi::OPS_SELESAI,
                    'selesai_beroperasi_at' => now(),
                ],
                'siaga' => [
                    'status_operasional'    => Lokasi::OPS_SIAGA,
                    'mulai_beroperasi_at'   => null,
                    'selesai_beroperasi_at' => null,
                ],
            });

            // Permintaan yang masih menunggu ikut diselesaikan supaya tidak menggantung
            PermintaanOperasional::where('lokasi_id', $lokasi->id)->menunggu()->get()
                ->each(function ($p) use ($aksi, $admin, $dariPermintaan) {
                    $terpenuhi = $p->aksi === $aksi;
                    $p->update([
                        'status'        => $terpenuhi ? PermintaanOperasional::DISETUJUI : PermintaanOperasional::DITOLAK,
                        'catatan_admin' => $terpenuhi || $dariPermintaan ? $p->catatan_admin : 'Status posko sudah diubah langsung oleh admin.',
                        'diproses_oleh' => $admin->id,
                        'diproses_at'   => now(),
                    ]);
                });
        });

        $this->beritahuRelawan($lokasi, $aksi, $admin, $dariPermintaan);
    }

    /* =========================================================
     |  RELAWAN: ajukan permintaan
     ========================================================= */

    /**
     * @throws DomainException
     */
    public function ajukan(Lokasi $lokasi, string $aksi, User $relawan, ?string $alasan = null): PermintaanOperasional
    {
        if (! in_array($aksi, ['mulai', 'tutup'], true)) {
            throw new DomainException('Permintaan tidak dikenali.');
        }
        if ($lokasi->permintaanMenunggu()->exists()) {
            throw new DomainException('Masih ada permintaan yang menunggu keputusan admin.');
        }
        $this->pastikanBisa($lokasi, $aksi);

        $permintaan = PermintaanOperasional::create([
            'lokasi_id' => $lokasi->id,
            'user_id'   => $relawan->id,
            'aksi'      => $aksi,
            'status'    => PermintaanOperasional::MENUNGGU,
            'alasan'    => $alasan,
        ]);

        $mulai = $aksi === 'mulai';
        Notification::send(
            User::role('admin')->get(),
            new PemberitahuanPosko(
                judul:    $mulai ? 'Permintaan mulai operasi' : 'Permintaan penutupan operasi',
                pesan:    "{$relawan->name} meminta posko {$lokasi->nama_lokasi} " . ($mulai ? 'mulai beroperasi' : 'ditutup')
                          . ($alasan ? ". Alasan: {$alasan}" : '.'),
                url:      route('admin.lokasi.show', $lokasi->id),
                ikon:     $mulai ? 'fa-play' : 'fa-stop',
                warna:    'amber',
                lokasiId: $lokasi->id,
            )
        );

        return $permintaan;
    }

    /* =========================================================
     |  ADMIN: proses permintaan
     ========================================================= */

    public function setujui(PermintaanOperasional $permintaan, User $admin): void
    {
        $this->pastikanMenunggu($permintaan);
        $this->ubah($permintaan->lokasi, $permintaan->aksi, $admin, $permintaan);
    }

    public function tolak(PermintaanOperasional $permintaan, User $admin, ?string $catatan = null): void
    {
        $this->pastikanMenunggu($permintaan);

        $permintaan->update([
            'status'        => PermintaanOperasional::DITOLAK,
            'catatan_admin' => $catatan,
            'diproses_oleh' => $admin->id,
            'diproses_at'   => now(),
        ]);

        $lokasi = $permintaan->lokasi;
        $penerima = $permintaan->peminta ?? optional($lokasi->relawan)->user;
        $penerima?->notify(new PemberitahuanPosko(
            judul:    'Permintaan ' . $permintaan->labelAksi() . ' ditolak',
            pesan:    "Admin menolak permintaan {$permintaan->labelAksi()} posko {$lokasi->nama_lokasi}"
                      . ($catatan ? ". Catatan: {$catatan}" : '.'),
            url:      route('lokasi.show', $lokasi->id),
            ikon:     'fa-times-circle',
            warna:    'red',
            lokasiId: $lokasi->id,
        ));
    }

    /* ---------------------------------------------------------------- */

    private function pastikanBisa(Lokasi $lokasi, string $aksi): void
    {
        match ($aksi) {
            'mulai' => match (true) {
                ! $lokasi->isApproved()  => throw new DomainException('Lokasi harus disetujui admin sebelum beroperasi.'),
                $lokasi->isBeroperasi()  => throw new DomainException('Posko sudah beroperasi.'),
                default                  => null,
            },
            'tutup' => $lokasi->isBeroperasi() ? null : throw new DomainException('Posko sedang tidak beroperasi.'),
            'siaga' => $lokasi->status_operasional === Lokasi::OPS_SELESAI ? null : throw new DomainException('Hanya posko yang sudah ditutup yang bisa dikembalikan ke siaga.'),
            default => throw new DomainException('Aksi tidak dikenali.'),
        };
    }

    private function pastikanMenunggu(PermintaanOperasional $permintaan): void
    {
        if ($permintaan->status !== PermintaanOperasional::MENUNGGU) {
            throw new DomainException('Permintaan ini sudah diproses sebelumnya.');
        }
    }

    /** Relawan penanggung jawab diberi tahu setiap kali admin mengubah status operasional. */
    private function beritahuRelawan(Lokasi $lokasi, string $aksi, User $admin, ?PermintaanOperasional $dariPermintaan): void
    {
        $relawan = optional($lokasi->relawan)->user;
        if (! $relawan || $relawan->id === $admin->id) {
            return;
        }

        [$judul, $pesan, $ikon, $warna] = match ($aksi) {
            'mulai' => ['Posko mulai beroperasi', "Admin telah memulai operasi posko {$lokasi->nama_lokasi}.", 'fa-play', 'green'],
            'tutup' => ['Operasi posko ditutup', "Admin telah menutup operasi posko {$lokasi->nama_lokasi}.", 'fa-stop', 'slate'],
            'siaga' => ['Posko kembali siaga', "Admin mengembalikan posko {$lokasi->nama_lokasi} ke status siaga.", 'fa-undo-alt', 'blue'],
        };

        if ($dariPermintaan) {
            $judul = 'Permintaan ' . $dariPermintaan->labelAksi() . ' disetujui';
            $pesan = 'Admin menyetujui permintaan Anda. ' . $pesan;
        }

        $relawan->notify(new PemberitahuanPosko($judul, $pesan, route('lokasi.show', $lokasi->id), $ikon, $warna, $lokasi->id));
    }
}
