<?php

namespace App\Http\Controllers;

use App\Models\Lokasi;
use App\Models\PermintaanOperasional;
use App\Services\StatusOperasionalService;
use DomainException;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert as Swal;

/**
 * Relawan mengajukan mulai / tutup operasi posko -> admin menyetujui atau menolak.
 */
class PermintaanOperasionalController extends Controller
{
    public function __construct(private StatusOperasionalService $service) {}

    /** Relawan penanggung jawab mengajukan permintaan. */
    public function store(Request $request, Lokasi $lokasi)
    {
        abort_unless($lokasi->bisaDikelolaOleh($request->user()), 403, 'Anda bukan penanggung jawab posko ini.');

        $data = $request->validate([
            'aksi'   => 'required|in:mulai,tutup',
            'alasan' => 'nullable|string|max:500',
        ]);

        return $this->jalankan(
            fn () => $this->service->ajukan($lokasi, $data['aksi'], $request->user(), $data['alasan'] ?? null),
            'Permintaan dikirim ke admin. Anda akan mendapat notifikasi setelah diproses.'
        );
    }

    /** Admin menyetujui (status operasional langsung berubah). */
    public function setujui(Request $request, PermintaanOperasional $permintaan)
    {
        return $this->jalankan(
            fn () => $this->service->setujui($permintaan, $request->user()),
            'Permintaan disetujui, status posko sudah diubah'
        );
    }

    /** Admin menolak. */
    public function tolak(Request $request, PermintaanOperasional $permintaan)
    {
        $data = $request->validate(['catatan' => 'nullable|string|max:500']);

        return $this->jalankan(
            fn () => $this->service->tolak($permintaan, $request->user(), $data['catatan'] ?? null),
            'Permintaan ditolak, relawan sudah diberi tahu'
        );
    }

    private function jalankan(callable $aksi, string $pesanSukses)
    {
        try {
            $aksi();
        } catch (DomainException $e) {
            Swal::toast($e->getMessage(), 'error')->timerProgressBar();
            return back();
        }

        Swal::toast($pesanSukses, 'success')->timerProgressBar();
        return back();
    }
}
