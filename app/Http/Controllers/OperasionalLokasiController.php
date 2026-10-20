<?php

namespace App\Http\Controllers;

use App\Models\Lokasi;
use App\Services\StatusOperasionalService;
use DomainException;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert as Swal;

/**
 * Mengubah status operasional posko (mulai / tutup / kembali siaga).
 * KHUSUS ADMIN. Relawan mengajukan permintaan lewat PermintaanOperasionalController.
 */
class OperasionalLokasiController extends Controller
{
    public function update(Request $request, Lokasi $lokasi, StatusOperasionalService $service)
    {
        abort_unless($request->user()->hasRole('admin'), 403, 'Hanya admin yang dapat mengubah status operasional posko. Relawan dapat mengajukan permintaan.');

        $request->validate([
            'aksi' => 'required|in:mulai,tutup,siaga',
        ]);

        try {
            $service->ubah($lokasi, $request->aksi, $request->user());
        } catch (DomainException $e) {
            Swal::toast($e->getMessage(), 'error')->timerProgressBar();
            return back();
        }

        Swal::toast(match ($request->aksi) {
            'mulai' => 'Posko mulai beroperasi',
            'tutup' => 'Operasi posko ditutup',
            'siaga' => 'Posko kembali ke status siaga',
        }, 'success')->timerProgressBar();

        return back();
    }
}
