<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lokasi;
use App\Models\StatusLokasi;
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

        // Posko yang ditolak tidak boleh tetap beroperasi
        if (! $disetujui && $lokasi->isBeroperasi()) {
            $lokasi->update([
                'status_operasional'    => Lokasi::OPS_SELESAI,
                'selesai_beroperasi_at' => now(),
            ]);
        }

        Swal::toast($disetujui ? 'Laporan posko disetujui' : 'Laporan posko ditolak', 'success')->timerProgressBar();

        return back();
    }
}
