<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Logistik;
use App\Models\KategoriLogistik;
use App\Services\GabungLogistikService;
use DomainException;

class AdminLogistikController extends Controller
{
    public function index()
    {
        $logistiks = Logistik::latest()->get();
        return view('pages.admin.logistik.index', compact('logistiks'));
    }

    public function create()
    {
        $kategoris = KategoriLogistik::all();
        return view('pages.admin.logistik.create', compact('kategoris'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_item' => 'required',
            'kategori_logistik_id' => 'required',
            'satuan' => 'required',
            'kebutuhan_harian' => 'nullable|numeric|min:0',
        ]);

        Logistik::create([
            'kategori_logistik_id' => $request->kategori_logistik_id,
            'nama_item' => $request->nama_item,
            'satuan' => $request->satuan,
            'kebutuhan_harian' => $request->kebutuhan_harian,
        ]);

        return redirect()->route('admin.logistik.index')
            ->with('success', 'Item logistik berhasil ditambahkan');
    }

    public function edit($id)
    {
        $logistik = Logistik::findOrFail($id);
        $kategoris = KategoriLogistik::all();
        // Untuk "Gabungkan ke barang lain" & daftar nama lama (alias).
        $barangLain = Logistik::whereKeyNot($logistik->id)->orderBy('nama_item')->get();
        $alias = \App\Models\AliasLogistik::where('logistik_id', $logistik->id)->orderBy('nama_alias')->pluck('nama_alias');

        return view('pages.admin.logistik.edit', compact('logistik', 'kategoris', 'barangLain', 'alias'));
    }

    /**
     * Gabungkan barang ganda ke barang lain: catatan masuk/keluar & stok tiap posko
     * dipindahkan, nama lama diingat (paket dari HP dengan nama ini otomatis masuk
     * ke barang tujuan), lalu barang ini dihapus.
     */
    public function gabung(Request $request, $id, GabungLogistikService $service)
    {
        $dari = Logistik::findOrFail($id);
        $data = $request->validate(['tujuan_id' => 'required|integer|exists:logistiks,id']);
        $ke = Logistik::findOrFail($data['tujuan_id']);

        try {
            $hasil = $service->gabung($dari, $ke);
        } catch (DomainException $e) {
            return back()->withErrors(['tujuan_id' => $e->getMessage()]);
        }

        return redirect()->route('admin.logistik.edit', $ke->id)->with('success',
            "\"{$dari->nama_item}\" digabung ke \"{$ke->nama_item}\": {$hasil['masuk']} catatan masuk, "
            . "{$hasil['keluar']} catatan keluar, stok {$hasil['stok']} posko dipindahkan.");
    }

public function update(Request $request, $id)
{
    $logistik = Logistik::findOrFail($id);

    $request->validate([
        'nama_item' => 'required',
        'kategori_logistik_id' => 'required|exists:kategori_logistiks,id',
        'satuan' => 'required',
        'kebutuhan_harian' => 'nullable|numeric|min:0',
    ]);

    $logistik->update([
        'kategori_logistik_id' => $request->kategori_logistik_id,
        'nama_item' => $request->nama_item,
        'satuan' => $request->satuan,
        'kebutuhan_harian' => $request->kebutuhan_harian,
    ]);

    return redirect()
        ->route('admin.logistik.index')
        ->with('success', 'Item logistik berhasil diupdate');
}

    public function destroy($id)
    {
        $logistik = Logistik::findOrFail($id);

        // Menghapus barang ikut menghapus SEMUA catatan masuk/keluar & stoknya di
        // semua posko (cascade). Barang yang sudah dipakai harus digabung, bukan dihapus.
        $dipakai = \App\Models\StokMasuk::where('logistik_id', $logistik->id)->count()
            + \App\Models\StokKeluar::where('logistik_id', $logistik->id)->count()
            + \App\Models\StokLogistik::where('logistik_id', $logistik->id)->count();
        if ($dipakai > 0) {
            return redirect()->route('admin.logistik.index')->with('error',
                "\"{$logistik->nama_item}\" tidak bisa dihapus: sudah dipakai di {$dipakai} catatan/stok posko. "
                . 'Kalau barangnya ganda, buka Edit lalu pakai "Gabungkan ke barang lain" supaya catatan & stok tidak hilang.');
        }

        $logistik->delete();

        return redirect()->route('admin.logistik.index')
            ->with('success', 'Item logistik berhasil dihapus');
    }
}