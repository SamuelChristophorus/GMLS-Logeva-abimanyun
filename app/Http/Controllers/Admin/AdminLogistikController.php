<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Logistik;
use App\Models\KategoriLogistik;

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

        return view('pages.admin.logistik.edit', compact('logistik', 'kategoris'));
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
        $logistik->delete();

        return redirect()->route('admin.logistik.index')
            ->with('success', 'Item logistik berhasil dihapus');
    }
}