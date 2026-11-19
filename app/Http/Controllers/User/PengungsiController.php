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
        $this->pastikanBoleh($lokasi);

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
        $this->pastikanBoleh(Lokasi::findOrFail($lokasi));

        $request->validate([
            'foto' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'nama' => 'required',
            'asal' => 'required',
            // Isi tanggal lahir ATAU usia (sama dengan aplikasi LOGEVA Client).
            'tanggal_lahir' => 'nullable|date|before_or_equal:today|after:1899-12-31',
            // Boleh kosong (dokumen bisa hilang saat bencana); jika diisi harus 16 digit.
            'nomor_kk' => 'nullable|digits:16',
            'usia' => 'required_without:tanggal_lahir|nullable|integer|min:0|max:130',
            'jenis_kelamin' => 'required',
            'kondisi_kesehatan' => 'nullable',
            'kondisi_pilihan' => ['nullable', \Illuminate\Validation\Rule::in(\App\Support\KondisiKesehatan::PILIHAN)],
            'kondisi_keterangan' => 'nullable|string|max:150',
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
            'tanggal_lahir' => $request->tanggal_lahir ?: null,
            'nomor_kk' => $request->nomor_kk,
            'usia' => $this->usia($request),
            'jenis_kelamin' => $request->jenis_kelamin,
            // Kosong = Sehat / Tidak (aturan website; kolom database wajib terisi)
            'kondisi_kesehatan' => $this->kondisi($request),
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
        $this->pastikanBoleh($pengungsi->lokasi);

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
        $this->pastikanBoleh($pengungsi->lokasi);

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
        $this->pastikanBoleh($pengungsi->lokasi);

        $request->validate([
            'foto' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'nama' => 'required',
            'asal' => 'required',
            // Isi tanggal lahir ATAU usia (sama dengan aplikasi LOGEVA Client).
            'tanggal_lahir' => 'nullable|date|before_or_equal:today|after:1899-12-31',
            // Boleh kosong (dokumen bisa hilang saat bencana); jika diisi harus 16 digit.
            'nomor_kk' => 'nullable|digits:16',
            'usia' => 'required_without:tanggal_lahir|nullable|integer|min:0|max:130',
            'jenis_kelamin' => 'required',
            'kondisi_pilihan' => ['nullable', \Illuminate\Validation\Rule::in(\App\Support\KondisiKesehatan::PILIHAN)],
            'kondisi_keterangan' => 'nullable|string|max:150',
        ]);

        if ($request->hasFile('foto')) {
            $fotoPath = Upload::simpan($request->file('foto'), 'assets/pengungsi');

            $pengungsi->foto = $fotoPath;
        }

        $pengungsi->nama = $request->nama;
        $pengungsi->asal = $request->asal;
        $pengungsi->tanggal_lahir = $request->tanggal_lahir ?: null;
        $pengungsi->nomor_kk = $request->nomor_kk;
        $pengungsi->usia = $this->usia($request);
        $pengungsi->jenis_kelamin = $request->jenis_kelamin;
        $pengungsi->kondisi_kesehatan = $this->kondisi($request);
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
        $this->pastikanBoleh($pengungsi->lokasi);

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
        $this->pastikanBoleh(Lokasi::findOrFail($lokasi));

        return Excel::download(
            new PengungsiExport($lokasi),
            'data_pengungsi.xlsx'
        );
    }

    /** Usia dari tanggal lahir kalau diisi; kalau tidak, usia yang diketik. */
    private function usia(Request $request): int
    {
        return $request->filled('tanggal_lahir')
            ? (int) \Carbon\Carbon::parse($request->tanggal_lahir)->diffInYears(now())
            : (int) $request->usia;
    }

    /**
     * Kondisi kesehatan: pilihan + keterangan (form baru). Form/klien lama yang
     * masih mengirim kondisi_kesehatan berupa teks tetap diterima.
     */
    private function kondisi(Request $request): string
    {
        if ($request->filled('kondisi_pilihan')) {
            return \App\Support\KondisiKesehatan::gabung($request->kondisi_pilihan, $request->kondisi_keterangan);
        }
        return \App\Support\KondisiKesehatan::rapikan($request->kondisi_kesehatan) ?: 'Sehat';
    }

    /**
     * Data pengungsi (data pribadi) hanya untuk ADMIN dan RELAWAN PENANGGUNG
     * JAWAB posko itu. Relawan yang belum ditugaskan / posko lain: ditolak.
     */
    private function pastikanBoleh(?Lokasi $lokasi): void
    {
        abort_unless($lokasi && $lokasi->bisaDikelolaOleh(auth()->user()), 403,
            'Anda tidak bertugas di posko ini.');
    }
}
