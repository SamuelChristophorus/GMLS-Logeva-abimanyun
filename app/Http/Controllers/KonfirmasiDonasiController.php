<?php

namespace App\Http\Controllers;

use App\Models\Donasi;
use App\Services\DonasiService;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert as Swal;

/**
 * Relawan penanggung jawab posko mengonfirmasi barang donasi yang diantar
 * langsung oleh donatur ke posko (admin juga bisa).
 */
class KonfirmasiDonasiController extends Controller
{
    public function __construct(private DonasiService $service) {}

    public function show(Request $request, Donasi $donasi)
    {
        $this->izinkan($request, $donasi);

        $donasi->load('items.logistik', 'lokasi', 'jejak');

        return view('pages.donasi.konfirmasi', compact('donasi'));
    }

    public function terima(Request $request, Donasi $donasi)
    {
        $this->izinkan($request, $donasi);

        $data = $request->validate([
            'diterima'   => 'required|array',
            'diterima.*' => 'nullable|integer|min:0',
            'catatan'    => 'nullable|string|max:500',
        ]);

        $this->service->terimaBarang($donasi, $data['diterima'], $request->user(), $data['catatan'] ?? null);

        Swal::toast('Terima kasih, barang donasi sudah masuk stok posko', 'success')->timerProgressBar();

        return redirect()->route('logistik.index', $donasi->lokasi_id);
    }

    public function tolak(Request $request, Donasi $donasi)
    {
        $this->izinkan($request, $donasi);

        $data = $request->validate(
            ['alasan' => 'required|string|max:500'],
            ['alasan.required' => 'Tulis alasannya, misalnya: barang tidak datang.']
        );

        $this->service->tolak($donasi, $request->user(), $data['alasan']);

        Swal::toast('Donasi ditandai tidak diterima', 'success')->timerProgressBar();

        return redirect()->route('logistik.index', $donasi->lokasi_id);
    }

    private function izinkan(Request $request, Donasi $donasi): void
    {
        abort_unless(
            $donasi->isKePosko() && ! $donasi->isUang() && $donasi->lokasi?->bisaDikelolaOleh($request->user()),
            403,
            'Hanya penanggung jawab posko tujuan yang bisa mengonfirmasi donasi ini.'
        );
    }
}
