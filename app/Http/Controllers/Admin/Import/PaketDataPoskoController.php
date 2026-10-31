<?php

namespace App\Http\Controllers\Admin\Import;

use App\Http\Controllers\Controller;
use App\Models\KataSandiPaket;
use App\Models\Relawan;
use App\Models\RiwayatImportPaket;
use App\Models\SelisihStokPaket;
use App\Services\PaketDataPoskoService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RealRashid\SweetAlert\Facades\Alert as Swal;

/**
 * Menu admin "Import Paket Data Posko": upload ZIP dari aplikasi offline
 * -> pratinjau -> konfirmasi. Aturan gabung ada di PaketDataPoskoService.
 *
 * File ZIP disimpan sementara di storage/app/paket-import (bukan folder publik)
 * karena berisi data pribadi, lalu dihapus setelah dikonfirmasi / dibatalkan.
 */
class PaketDataPoskoController extends Controller
{
    private const FOLDER = 'paket-import';
    private const SESSION = 'import.paket';

    public function __construct(private PaketDataPoskoService $paket)
    {
    }

    public function index()
    {
        return view('pages.admin.paket.index', [
            'hasil'   => session('hasil_paket'),
            'riwayat' => RiwayatImportPaket::with(['user', 'lokasis'])
                ->withCount('selisihStok')
                ->latest('id')
                ->paginate(10),
            'jumlahSelisih' => SelisihStokPaket::terkini()->count(),
        ]);
    }

    /** Selisih stok HP vs pusat dari paket terbaru tiap posko, untuk diselidiki. */
    public function selisih()
    {
        return view('pages.admin.paket.selisih', [
            'selisih' => SelisihStokPaket::terkini()->groupBy(fn ($s) => $s->lokasi_id),
        ]);
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:zip|max:10240',
            'kata_sandi' => 'nullable|string|max:100',
        ], [
            'file.required' => 'Pilih file paket (.zip) dari aplikasi.',
            'file.mimes'    => 'File harus berformat .zip (paket_....zip dari aplikasi).',
            'file.max'      => 'Ukuran file maksimal 10 MB.',
        ]);

        $this->bersihkanFileLama();
        $this->hapusFileSesi();

        $path = $request->file('file')->storeAs(self::FOLDER, Str::uuid() . '.zip', 'local');
        session([self::SESSION => [
            'path'     => $path,
            'namaFile' => $request->file('file')->getClientOriginalName(),
        ]]);

        try {
            // Paket terkunci: kata sandi yang diketik dulu, lalu yang pernah berhasil.
            $isi = $this->paket->bacaZip(Storage::disk('local')->path($path),
                array_merge([(string) $request->input('kata_sandi')], KataSandiPaket::semua()));
            session([self::SESSION . '.kataSandi' => $this->paket->kataSandiTerpakai]);
        } catch (DomainException $e) {
            $this->hapusFileSesi();
            Swal::toast($e->getMessage(), 'error')->timerProgressBar();
            return redirect()->route('admin.paket.index');
        }

        return $this->tampilkan($isi);
    }

    public function confirm(Request $request)
    {
        $sesi = session(self::SESSION);
        if (! $sesi || ! Storage::disk('local')->exists($sesi['path'])) {
            Swal::toast('Tidak ada paket untuk diimpor. Silakan upload ulang file.', 'error')->timerProgressBar();
            return redirect()->route('admin.paket.index');
        }

        $data = $request->validate([
            'relawan'   => 'array',
            'relawan.*' => 'nullable|integer|exists:relawans,id',
        ]);

        try {
            $isi = $this->paket->bacaZip(Storage::disk('local')->path($sesi['path']),
                array_merge([(string) ($sesi['kataSandi'] ?? '')], KataSandiPaket::semua()));
            $hitung = $this->paket->simpan($isi, $data['relawan'] ?? [], $sesi['namaFile'], $request->user()?->id);
            // Diingat (terenkripsi) supaya paket berikutnya dari posko ini langsung terbuka.
            if ($this->paket->kataSandiTerpakai) {
                KataSandiPaket::ingat($this->paket->kataSandiTerpakai);
            }
        } catch (DomainException $e) {
            // Misal relawan posko baru belum dipilih: tampilkan lagi pratinjaunya.
            return $this->tampilkan($isi ?? [], $e->getMessage());
        }

        $this->hapusFileSesi();

        Swal::toast('Paket Data Posko berhasil digabung', 'success')->timerProgressBar();
        return redirect()->route('admin.paket.index')->with('hasil_paket', $hitung + ['namaFile' => $sesi['namaFile']]);
    }

    public function batal()
    {
        $this->hapusFileSesi();
        return redirect()->route('admin.paket.index');
    }

    /* ---------------------------------------------------------------- */

    private function tampilkan(array $isi, ?string $error = null)
    {
        if (! $isi) {
            Swal::toast($error ?? 'Paket tidak bisa dibaca.', 'error')->timerProgressBar();
            return redirect()->route('admin.paket.index');
        }

        $hasil = $this->paket->periksa($isi);

        return view('pages.admin.paket.preview', [
            'hasil'    => $hasil,
            'namaFile' => session(self::SESSION . '.namaFile'),
            'error'    => $error,
            'relawans' => Relawan::with('user')
                ->whereHas('user', fn ($q) => $q->where('status', 'approved'))
                ->get()
                ->sortBy(fn ($r) => $r->user->name)
                ->values(),
            'relawanDipilih' => old('relawan', request('relawan', [])),
        ]);
    }

    private function hapusFileSesi(): void
    {
        if ($path = session(self::SESSION . '.path')) {
            Storage::disk('local')->delete($path);
        }
        session()->forget(self::SESSION);
    }

    /** File paket yang tertinggal (pratinjau tidak dilanjutkan) > 1 hari dihapus. */
    private function bersihkanFileLama(): void
    {
        $disk = Storage::disk('local');
        foreach ($disk->files(self::FOLDER) as $file) {
            if ($disk->lastModified($file) < now()->subDay()->getTimestamp()) {
                $disk->delete($file);
            }
        }
    }
}
