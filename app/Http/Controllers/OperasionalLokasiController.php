<?php

namespace App\Http\Controllers;

use App\Models\Lokasi;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert as Swal;

/**
 * Mengubah status operasional posko.
 * Dipakai bersama oleh ADMIN dan RELAWAN penanggung jawab posko.
 */
class OperasionalLokasiController extends Controller
{
    public function update(Request $request, Lokasi $lokasi)
    {
        // Hanya admin atau relawan pemilik posko
        abort_unless($lokasi->bisaDikelolaOleh($request->user()), 403, 'Anda tidak berhak mengelola posko ini.');

        $request->validate([
            'aksi' => 'required|in:mulai,tutup,siaga',
        ]);

        switch ($request->aksi) {
            case 'mulai':
                if (! $lokasi->isApproved()) {
                    Swal::toast('Lokasi harus disetujui admin sebelum beroperasi', 'error')->timerProgressBar();
                    return back();
                }

                $lokasi->update([
                    'status_operasional'    => Lokasi::OPS_BEROPERASI,
                    'mulai_beroperasi_at'   => now(),
                    'selesai_beroperasi_at' => null,
                ]);
                Swal::toast('Posko mulai beroperasi', 'success')->timerProgressBar();
                break;

            case 'tutup':
                $lokasi->update([
                    'status_operasional'    => Lokasi::OPS_SELESAI,
                    'selesai_beroperasi_at' => now(),
                ]);
                Swal::toast('Operasi posko ditutup', 'success')->timerProgressBar();
                break;

            case 'siaga':
                $lokasi->update([
                    'status_operasional'    => Lokasi::OPS_SIAGA,
                    'mulai_beroperasi_at'   => null,
                    'selesai_beroperasi_at' => null,
                ]);
                Swal::toast('Posko kembali ke status siaga', 'success')->timerProgressBar();
                break;
        }

        return back();
    }
}