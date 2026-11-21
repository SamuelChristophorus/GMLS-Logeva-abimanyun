<?php

namespace App\Http\Controllers\Admin;

use App\Support\Upload;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRelawanRequest;
use App\Http\Requests\UpdateRelawanRequest;
use App\Interfaces\RelawanRepositoryInterface;
use App\Http\Controllers\Admin\Import\RelawanImportController;
use Illuminate\Support\Facades\Hash;
use RealRashid\SweetAlert\Facades\Alert as Swal;

class RelawanController extends Controller
{
    private RelawanRepositoryInterface $relawanRepository;

    public function __construct(RelawanRepositoryInterface $relawanRepository)
    {
        $this->relawanRepository = $relawanRepository;
    }

    public function index()
    {
        $relawans = $this->relawanRepository->getAllRelawans();
        return view('pages.admin.relawan.index', compact('relawans'));
    }

    public function create()
    {
        return view('pages.admin.relawan.create');
    }

    /** Foto default jika admin tidak mengupload foto (sama dengan relawan hasil import) */
    public const AVATAR_DEFAULT = 'assets/default/avatar.png';

    public function store(StoreRelawanRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            // Disimpan ke <folder public>/storage/assets/avatar (otomatis benar di localhost & Hostinger)
            $data['avatar'] = Upload::simpan($request->file('avatar'), 'assets/avatar');
        } else {
            $data['avatar'] = self::AVATAR_DEFAULT;
        }

        $relawan = $this->relawanRepository->createRelawan($data);

        $relawan->user->update([
            'status' => 'approved',
            'is_verified' => true,
            'approved_at' => now(),
        ]);

        Swal::toast('Relawan Berhasil Ditambahkan', 'success')->timerProgressBar();

        return redirect()->route('admin.relawan.index');
    }

    public function show(string $id)
    {
        $relawan = $this->relawanRepository->getRelawanById($id);
        return view('pages.admin.relawan.show', compact('relawan'));
    }

    public function edit(string $id)
    {
        $relawan = $this->relawanRepository->getRelawanById($id);
        // Pilihan posko tugas: semua posko + penanggung jawabnya sekarang.
        $poskos = \App\Models\Lokasi::with(['desa', 'relawan.user', 'latestStatusView'])
            ->orderBy('nama_lokasi')->get();
        $poskoTugas = $relawan->poskoTugas();
        $jumlahPosko = $relawan->lokasi()->count();
        return view('pages.admin.relawan.edit', compact('relawan', 'poskos', 'poskoTugas', 'jumlahPosko'));
    }

    public function update(UpdateRelawanRequest $request, string $id)
    {
        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            // Disimpan ke <folder public>/storage/assets/avatar (otomatis benar di localhost & Hostinger)
            $data['avatar'] = Upload::simpan($request->file('avatar'), 'assets/avatar');
        }

        $this->relawanRepository->updateRelawan($id, $data);

        // Penugasan posko: satu relawan hanya di satu posko pilihan admin.
        // Relawan yang sudah memegang posko tidak bisa dijadikan "belum
        // ditugaskan" (posko wajib punya penanggung jawab): pindahkan saja.
        $pesan = 'Relawan Berhasil Diubah';
        $relawan = $this->relawanRepository->getRelawanById($id);
        if ($request->filled('lokasi_id')) {
            $posko = \App\Models\Lokasi::findOrFail($request->lokasi_id);
            $hasil = $relawan->tugaskanKe($posko);
            $pesan = \App\Models\Relawan::pesanPenugasan($relawan->user->name, $posko, $hasil);
        }

        Swal::toast($pesan, 'success')->timerProgressBar();

        return redirect()->route('admin.relawan.index');
    }

    public function destroy(string $id)
    {
        // Posko wajib punya penanggung jawab: relawan yang masih memegang posko
        // tidak bisa dihapus sebelum posko itu diberi relawan lain.
        $relawan = $this->relawanRepository->getRelawanById($id);
        $posko = $relawan?->lokasi()->pluck('nama_lokasi')->all() ?? [];
        if ($posko) {
            Swal::toast('Relawan masih penanggung jawab ' . implode(', ', $posko)
                . '. Ganti penanggung jawab posko itu dulu (Edit Posko), baru hapus relawan.', 'error')->timerProgressBar();
            return redirect()->route('admin.relawan.index');
        }

        $this->relawanRepository->deleteRelawan($id);

        Swal::toast('Relawan Berhasil Dihapus', 'success')->timerProgressBar();

        return redirect()->route('admin.relawan.index');
    }

    /**
     * Mengembalikan password SATU relawan (sesuai $id) ke password awal (default relawan123).
     * Saat login berikutnya relawan otomatis diminta membuat password baru.
     */
    public function resetPassword(string $id)
    {
        $relawan = $this->relawanRepository->getRelawanById($id);

        abort_unless($relawan && $relawan->user, 404);

        $relawan->user->password = Hash::make(RelawanImportController::passwordDefault());
        $relawan->user->save();

        Swal::toast(
            "Password {$relawan->user->name} direset ke \"" . RelawanImportController::passwordDefault() . '"',
            'success'
        )->timerProgressBar();

        return redirect()->route('admin.relawan.index');
    }

    public function accept(string $id)
    {
        $relawan = $this->relawanRepository->getRelawanById($id);

        $relawan->user->update([
            'status' => 'approved',
            'is_verified' => true,
            'approved_at' => now(),
        ]);

        Swal::toast('Relawan Berhasil Disetujui', 'success')->timerProgressBar();

        return redirect()->route('admin.relawan.index');
    }

    public function reject(string $id)
    {
        $relawan = $this->relawanRepository->getRelawanById($id);

        $relawan->user->update([
            'status' => 'rejected',
            'is_verified' => false,
            'approved_at' => null,
        ]);

        Swal::toast('Relawan Berhasil Ditolak', 'success')->timerProgressBar();

        return redirect()->route('admin.relawan.index');
    }

    public function suspend(string $id)
    {
        $relawan = $this->relawanRepository->getRelawanById($id);

        $relawan->user->update([
            'status' => 'suspended',
            'is_verified' => false,
        ]);

        Swal::toast('Relawan Berhasil Disuspend', 'success')->timerProgressBar();

        return redirect()->route('admin.relawan.index');
    }
}