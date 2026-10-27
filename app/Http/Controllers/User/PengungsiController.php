<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Support\Upload;
use App\Models\Pengungsi;
use App\Models\Lokasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\PengungsiExport;
use Maatwebsite\Excel\Facades\Excel;

class PengungsiController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index($lokasi)
    {
        $lokasi = Lokasi::findOrFail($lokasi);

        $pengungsis = Pengungsi::where(
            'lokasi_id',
            $lokasi->id
        )->get();

        return view(
            'pages.app.pengungsi.index',
            compact('lokasi', 'pengungsis')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    public function store(Request $request, $lokasi)
    {
        $request->validate([
            'foto' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'nama' => 'required',
            'asal' => 'required',
            'tanggal_lahir' => 'required|date',
            // Boleh kosong (dokumen bisa hilang saat bencana); jika diisi harus 16 digit.
            'nomor_kk' => 'nullable|digits:16',
            'usia' => 'required|integer',
            'jenis_kelamin' => 'required',
            'kondisi_kesehatan' => 'nullable',
            'kelompok_rentan' => 'nullable',
            'riwayat_penyakit' => 'nullable'
        ]);

        $fotoPath = null;

        if ($request->hasFile('foto')) {
            $fotoPath = Upload::simpan($request->file('foto'), 'assets/pengungsi');
        }

        Pengungsi::create([
            'lokasi_id' => $lokasi,
            'foto' => $fotoPath,
            'nama' => $request->nama,
            'asal' => $request->asal,
            'tanggal_lahir' => $request->tanggal_lahir,
            'nomor_kk' => $request->nomor_kk,
            'usia' => $request->usia,
            'jenis_kelamin' => $request->jenis_kelamin,
            // Kosong = Sehat / Tidak (aturan website; kolom database wajib terisi)
            'kondisi_kesehatan' => $request->kondisi_kesehatan ?: 'Sehat',
            'kelompok_rentan' => $request->kelompok_rentan ?: 'Tidak',
            'riwayat_penyakit' => $request->riwayat_penyakit
        ]);

        return redirect()
            ->back()
            ->with(
                'success',
                'Data pengungsi berhasil ditambahkan'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        // Termasuk yang sudah keluar posko (dibuka dari daftar "Sudah keluar posko").
        $pengungsi = Pengungsi::semuaKeberadaan()->findOrFail($id);

        return view(
            'pages.app.pengungsi.show',
            compact('pengungsi')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT
    |--------------------------------------------------------------------------
    */

    public function edit($id)
    {
        $pengungsi = Pengungsi::findOrFail($id);

        return view(
            'pages.app.pengungsi.edit',
            compact('pengungsi')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, $id)
    {
        $pengungsi = Pengungsi::findOrFail($id);

        $request->validate([
            'foto' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'nama' => 'required',
            'asal' => 'required',
            'tanggal_lahir' => 'required|date',
            // Boleh kosong (dokumen bisa hilang saat bencana); jika diisi harus 16 digit.
            'nomor_kk' => 'nullable|digits:16',
            'usia' => 'required|integer',
            'jenis_kelamin' => 'required'
        ]);

        if ($request->hasFile('foto')) {
            $fotoPath = Upload::simpan($request->file('foto'), 'assets/pengungsi');

            $pengungsi->foto = $fotoPath;
        }

        $pengungsi->nama = $request->nama;
        $pengungsi->asal = $request->asal;
        $pengungsi->tanggal_lahir = $request->tanggal_lahir;
        $pengungsi->nomor_kk = $request->nomor_kk;
        $pengungsi->usia = $request->usia;
        $pengungsi->jenis_kelamin = $request->jenis_kelamin;
        $pengungsi->kondisi_kesehatan = $request->kondisi_kesehatan ?: 'Sehat';
        $pengungsi->kelompok_rentan = $request->kelompok_rentan ?: 'Tidak';
        $pengungsi->riwayat_penyakit = $request->riwayat_penyakit;

        $pengungsi->save();

        return redirect()
            ->route(
                'pengungsi.index',
                $pengungsi->lokasi_id
            )
            ->with(
                'success',
                'Data pengungsi berhasil diupdate'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {
        $pengungsi = Pengungsi::findOrFail($id);

        $lokasi = $pengungsi->lokasi_id;

        // Pengungsi dari Paket Data Posko: catat kodenya supaya paket lama yang
        // diimpor belakangan tidak memunculkan lagi data yang sudah dihapus.
        if ($pengungsi->kode) {
            DB::table('paket_penghapusans')->insertOrIgnore([
                'kode' => $pengungsi->kode, 'jenis' => 'pengungsi', 'dihapus_pada' => now(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $pengungsi->delete();

        return redirect()
            ->route(
                'pengungsi.index',
                $lokasi
            )
            ->with(
                'success',
                'Data pengungsi berhasil dihapus'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | EXPORT EXCEL
    |--------------------------------------------------------------------------
    */

    public function export($lokasi)
    {
        return Excel::download(
            new PengungsiExport($lokasi),
            'data_pengungsi.xlsx'
        );
    }
}
