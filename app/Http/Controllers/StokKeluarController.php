<?php

namespace App\Http\Controllers;

use App\Models\Lokasi;
use App\Models\StokKeluar;
use App\Models\StokLogistik;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RealRashid\SweetAlert\Facades\Alert as Swal;

/**
 * Barang keluar dari posko: dibagikan, dimasak, dikirim ke posko lain, atau rusak.
 * Stok berkurang otomatis dan tidak bisa menjadi minus.
 */
class StokKeluarController extends Controller
{
    public function create(Request $request, Lokasi $lokasi)
    {
        abort_unless($lokasi->bisaDikelolaOleh($request->user()), 403, 'Anda tidak berhak mengelola logistik posko ini.');

        // Hanya barang yang masih ada stoknya yang bisa dikeluarkan
        $stoks = StokLogistik::with('logistik')
            ->where('lokasi_id', $lokasi->id)
            ->where('jumlah_stok', '>', 0)
            ->get()
            ->sortBy(fn ($s) => $s->logistik->nama_item ?? '')
            ->values();

        return view('logistik.keluar', compact('lokasi', 'stoks'));
    }

    public function store(Request $request, Lokasi $lokasi)
    {
        abort_unless($lokasi->bisaDikelolaOleh($request->user()), 403, 'Anda tidak berhak mengelola logistik posko ini.');

        $data = $request->validate([
            'logistik_id'    => 'required|exists:logistiks,id',
            'jumlah_keluar'  => 'required|integer|min:1',
            'tujuan'         => ['required', Rule::in(array_keys(StokKeluar::TUJUAN))],
            'penerima'       => 'nullable|string|max:255',
            'tanggal_keluar' => 'required|date|before_or_equal:today',
            'keterangan'     => 'nullable|string|max:1000',
        ], [
            'logistik_id.required'           => 'Pilih barang yang dikeluarkan.',
            'jumlah_keluar.required'         => 'Isi jumlah barang yang dikeluarkan.',
            'jumlah_keluar.min'              => 'Jumlah minimal 1.',
            'tujuan.required'                => 'Pilih untuk apa barang dikeluarkan.',
            'tanggal_keluar.before_or_equal' => 'Tanggal tidak boleh setelah hari ini.',
        ]);

        DB::transaction(function () use ($data, $lokasi, $request) {
            // Kunci baris stok agar dua petugas yang mencatat bersamaan tidak membuat stok minus
            $stok = StokLogistik::with('logistik')
                ->where('lokasi_id', $lokasi->id)
                ->where('logistik_id', $data['logistik_id'])
                ->lockForUpdate()
                ->first();

            $sisa = (int) ($stok->jumlah_stok ?? 0);

            if ($data['jumlah_keluar'] > $sisa) {
                $satuan = $stok->logistik->satuan ?? '';
                throw ValidationException::withMessages([
                    'jumlah_keluar' => $sisa > 0
                        ? "Stok tidak cukup. Sisa stok hanya {$sisa} {$satuan}."
                        : 'Stok barang ini sudah habis.',
                ]);
            }

            StokKeluar::create($data + [
                'lokasi_id' => $lokasi->id,
                'user_id'   => $request->user()->id,
            ]);

            $stok->ubahJumlah(-$data['jumlah_keluar']);
        });

        Swal::toast('Barang keluar berhasil dicatat, stok sudah dikurangi', 'success')->timerProgressBar();

        return redirect()->route('logistik.index', $lokasi->id);
    }
}
