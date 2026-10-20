<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lokasi;
use App\Models\StatusLokasi;
use App\Notifications\PemberitahuanPosko;
use App\Services\StatusOperasionalService;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert as Swal;

/**
 * Approve / tolak laporan lokasi posko (khusus admin).
 * Menambah baris baru di status_lokasis sehingga riwayat tetap tersimpan.
 */
class VerifikasiLokasiController extends Controller
{
    public function update(Request $request, Lokasi $lokasi)
    {
        $data = $request->validate([
            'status'  => 'required|in:approved,rejected',
            'catatan' => 'nullable|string|max:1000',
        ]);

        $disetujui = $data['status'] === 'approved';

        StatusLokasi::create([
            'lokasi_id' => $lokasi->id,
            'status'    => $data['status'],
            'catatan'   => ($data['catatan'] ?? null) ?: ($disetujui ? 'Disetujui oleh admin' : 'Ditolak oleh admin'),
        ]);

        // Posko yang ditolak tidak boleh tetap beroperasi (relawan ikut diberi tahu)
        if (! $disetujui && $lokasi->isBeroperasi()) {
            app(StatusOperasionalService::class)->ubah($lokasi, 'tutup', $request->user());
        }

        // Beri tahu relawan pelapor hasil verifikasi laporannya
        optional($lokasi->relawan)->user?->notify(new PemberitahuanPosko(
            judul:    $disetujui ? 'Laporan posko disetujui' : 'Laporan posko ditolak',
            pesan:    "Laporan posko {$lokasi->nama_lokasi} " . ($disetujui ? 'telah disetujui admin.' : 'ditolak admin.')
                      . (($data['catatan'] ?? null) ? " Catatan: {$data['catatan']}" : ''),
            url:      route('lokasi.mylokasi', ['status' => $disetujui ? 'approved' : 'rejected']),
            ikon:     $disetujui ? 'fa-circle-check' : 'fa-circle-xmark',
            warna:    $disetujui ? 'green' : 'red',
            lokasiId: $lokasi->id,
        ));

        Swal::toast($disetujui ? 'Laporan posko disetujui' : 'Laporan posko ditolak', 'success')->timerProgressBar();

        return back();
    }
}
