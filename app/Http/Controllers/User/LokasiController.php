<?php

namespace App\Http\Controllers\User;

use App\Support\Upload;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLokasiRequest;
use App\Interfaces\DesaRepositoryInterface;
use App\Interfaces\LokasiRepositoryInterface;
use App\Interfaces\SphereLokasiRepositoryInterface;
use App\Models\Relawan;
use App\Services\OperasionalLokasiService;
use Illuminate\Http\Request;

class LokasiController extends Controller
{
    private LokasiRepositoryInterface $lokasiRepository;

    private DesaRepositoryInterface $desaRepository;

    private SphereLokasiRepositoryInterface $sphereLokasiRepository;

    public function __construct(
        LokasiRepositoryInterface $lokasiRepository,
        DesaRepositoryInterface $desaRepository,
        SphereLokasiRepositoryInterface $sphereLokasiRepository
    ) {
        $this->lokasiRepository = $lokasiRepository;
        $this->desaRepository = $desaRepository;
        $this->sphereLokasiRepository = $sphereLokasiRepository;
    }

    public function index(Request $request, OperasionalLokasiService $operasionalService)
    {
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $radius = $request->radius ?? 9999;

        if ($request->desa) {
            $lokasis = $this->lokasiRepository->getLokasiByDesa($request->desa);
        } elseif ($latitude && $longitude) {
            $lokasis = $this->lokasiRepository->getNearbyLokasis($latitude, $longitude, $radius);
        } else {
            $lokasis = $this->lokasiRepository->getAllLokasis();
        }

        $lokasis->load(['desa', 'latestStatusView'])->loadCount('pengungsi');

        // Halaman publik: posko yang ditolak admin tidak ditampilkan
        $lokasis = $lokasis->reject->isDitolak()->values();

        // Status bantuan (segera / pantau / ...) untuk posko yang sedang beroperasi
        $bantuan = $lokasis->filter->isBeroperasi()
            ->mapWithKeys(fn ($l) => [$l->id => $operasionalService->statusBantuan($l)['tingkat']]);

        $ringkasan = [
            'total'      => $lokasis->count(),
            'beroperasi' => $lokasis->filter->isBeroperasi()->count(),
            'kapasitas'  => $lokasis->sum('kapasitas_pengungsi'),
            'pengungsi'  => $lokasis->sum('pengungsi_count'),
        ];

        return view('pages.app.lokasi.index', compact('lokasis', 'ringkasan', 'bantuan'));
    }

    public function myLokasi(Request $request)
    {
        $status = in_array($request->status, ['pending', 'approved', 'rejected'], true)
            ? $request->status
            : 'pending';

        // Admin melihat & memverifikasi laporan semua relawan, relawan hanya miliknya
        $isAdmin   = $request->user()->hasRole('admin');
        $relawanId = $request->user()->relawan?->id;

        if ($isAdmin) {
            $lokasis = $this->lokasiRepository->getLokasiByStatus($status);
            $counts  = $this->lokasiRepository->countLokasiPerStatus();
        } elseif ($relawanId) {
            $lokasis = $this->lokasiRepository->getLokasiByStatus($status, $relawanId);
            $counts  = $this->lokasiRepository->countLokasiPerStatus($relawanId);
        } else {
            $lokasis = collect();
            $counts  = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
        }

        return view('pages.app.lokasi.my-lokasi', compact('lokasis', 'counts', 'status', 'isAdmin'));
    }

    public function show($id, OperasionalLokasiService $operasionalService)
    {
        $lokasi = $this->lokasiRepository->getLokasiById($id);

        abort_unless($lokasi, 404);

        // Posko yang ditolak hanya bisa dilihat admin & relawan pelapornya (untuk melihat alasan penolakan)
        abort_if($lokasi->isDitolak() && ! $lokasi->bisaDikelolaOleh(auth()->user()), 404);

        // Data dashboard operasional (hanya dihitung jika posko sedang beroperasi)
        $operasional = $lokasi->isBeroperasi()
            ? $operasionalService->ringkasan($lokasi)
            : null;

        return view('pages.app.lokasi.show', compact('lokasi', 'operasional'));
    }

    public function create()
    {
        $desas = $this->desaRepository->getAllDesas();

        // Admin tidak punya data relawan sendiri, jadi memilih relawan penanggung jawab
        $relawans = auth()->user()->hasRole('admin')
            ? Relawan::with('user')->whereHas('user')->get()
            : collect();

        return view('pages.app.lokasi.create', compact('desas', 'relawans'));
    }

    public function store(StoreLokasiRequest $request)
    {
        $data = $request->validated();

        // Relawan selalu tercatat sebagai penanggung jawab posko yang ia laporkan;
        // admin memakai relawan yang dipilih di form.
        if (! $request->user()->hasRole('admin')) {
            $relawan = $request->user()->relawan;

            if (! $relawan) {
                return back()->withInput()->withErrors([
                    'relawan_id' => 'Akun Anda belum terdaftar sebagai relawan, sehingga belum dapat melaporkan posko.',
                ]);
            }

            $data['relawan_id'] = $relawan->id;
        }

        if ($request->hasFile('gambar_lokasi')) {
            // Disimpan ke <folder public>/storage/assets/lokasi/gambar (otomatis benar di localhost & Hostinger)
            $data['gambar_lokasi'] = Upload::simpan($request->file('gambar_lokasi'), 'assets/lokasi/gambar');
        }

        $lokasi = $this->lokasiRepository->createLokasi($data);

        $sphereData = $request->only([
            'air_hidup',
            'air_kebersihan',
            'air_memasak',
            'toilet_pendek',
            'toilet_panjang',
            'kalori',
            'protein',
            'lemak'
        ]);

        if (!empty(array_filter($sphereData))) {
            $sphereData['lokasi_id'] = $lokasi->id;
            $this->sphereLokasiRepository->createSphereLokasi($sphereData);
        }

        return redirect()
            ->route('lokasi.success')
            ->with('success', 'Lokasi Berhasil Ditambahkan');
    }

    public function success()
    {
        return view('pages.app.lokasi.success');
    }
}
