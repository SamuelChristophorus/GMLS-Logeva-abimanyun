<?php

namespace App\Http\Controllers\User;

use App\Support\Upload;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLokasiRequest;
use App\Interfaces\DesaRepositoryInterface;
use App\Interfaces\LokasiRepositoryInterface;
use App\Interfaces\SphereLokasiRepositoryInterface;
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

    public function index(Request $request)
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

        return view('pages.app.lokasi.index', compact('lokasis'));
    }

    public function myLokasi(Request $request)
    {
        $lokasis = $this->lokasiRepository->getLokasiByRelawanId($request->status);
        return view('pages.app.lokasi.my-lokasi', compact('lokasis'));
    }

    public function show($id, OperasionalLokasiService $operasionalService)
    {
        $lokasi = $this->lokasiRepository->getLokasiById($id);

        // Data dashboard operasional (hanya dihitung jika posko sedang beroperasi)
        $operasional = $lokasi->isBeroperasi()
            ? $operasionalService->ringkasan($lokasi)
            : null;

        return view('pages.app.lokasi.show', compact('lokasi', 'operasional'));
    }

    public function create()
    {
        $desas = $this->desaRepository->getAllDesas();
        return view('pages.app.lokasi.create', compact('desas'));
    }

    public function store(StoreLokasiRequest $request)
    {
        $data = $request->validated();

        $data['relawan_id'] = auth()->user()->relawan->id;

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
