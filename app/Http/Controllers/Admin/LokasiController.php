<?php

namespace App\Http\Controllers\Admin;

use App\Support\Upload;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLokasiRequest;
use App\Http\Requests\UpdateLokasiRequest;
use App\Interfaces\DesaRepositoryInterface;
use App\Interfaces\LokasiRepositoryInterface;
use App\Interfaces\RelawanRepositoryInterface;
use App\Interfaces\SphereLokasiRepositoryInterface;
use RealRashid\SweetAlert\Facades\Alert as Swal;
use App\Exports\LokasiExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\OperasionalLokasiService;

class LokasiController extends Controller
{
    private LokasiRepositoryInterface $lokasiRepository;
    private DesaRepositoryInterface $desaRepository;
    private RelawanRepositoryInterface $relawanRepository;
    private SphereLokasiRepositoryInterface $sphereLokasiRepository;

    public function __construct(
        LokasiRepositoryInterface $lokasiRepository,
        DesaRepositoryInterface $desaRepository,
        RelawanRepositoryInterface $relawanRepository,
        SphereLokasiRepositoryInterface $sphereLokasiRepository
    ) {
        $this->lokasiRepository = $lokasiRepository;
        $this->desaRepository = $desaRepository;
        $this->relawanRepository = $relawanRepository;
        $this->sphereLokasiRepository = $sphereLokasiRepository;
    }

    public function index()
    {
        $lokasis = $this->lokasiRepository->getAllLokasis();
        return view("pages.admin.lokasi.index", compact("lokasis"));
    }

    public function create()
    {
        $relawans = $this->relawanRepository->getAllRelawans();
        $desas = $this->desaRepository->getAllDesas();

        return view('pages.admin.lokasi.create', compact('relawans', 'desas'));
    }

    public function store(StoreLokasiRequest $request)
    {
        $data = $request->validated();

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

        Swal::toast('Lokasi Berhasil Ditambahkan', 'success')->timerProgressBar();

        return redirect()->route('admin.lokasi.index');
    }

    public function show(string $id, OperasionalLokasiService $operasionalService)
    {
        $lokasi = $this->lokasiRepository->getLokasiById((int) $id);
        $sphere = $this->sphereLokasiRepository->getSphereLokasiByLokasiId((int) $id);

        // Data dashboard operasional (hanya dihitung jika posko sedang beroperasi)
        $operasional = $lokasi->isBeroperasi()
            ? $operasionalService->ringkasan($lokasi)
            : null;

        return view('pages.admin.lokasi.show', compact('lokasi', 'sphere', 'operasional'));
    }

    public function edit(string $id)
    {
        $relawans = $this->relawanRepository->getAllRelawans();
        $desas = $this->desaRepository->getAllDesas();
        $lokasi = $this->lokasiRepository->getLokasiById((int) $id);

        return view('pages.admin.lokasi.edit', compact('lokasi', 'relawans', 'desas'));
    }

    public function update(UpdateLokasiRequest $request, string $id)
    {
        $data = $request->validated();

        if ($request->hasFile('gambar_lokasi')) {
            // Disimpan ke <folder public>/storage/assets/lokasi/gambar (otomatis benar di localhost & Hostinger)
            $data['gambar_lokasi'] = Upload::simpan($request->file('gambar_lokasi'), 'assets/lokasi/gambar');
        }

        $this->lokasiRepository->updateLokasi($id, $data);

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
            $this->sphereLokasiRepository->updateSphereLokasiByLokasi($id, $sphereData);
        }

        Swal::toast('Desa Lokasi Berhasil Diubah', 'success')->timerProgressBar();

        return redirect()->route('admin.lokasi.index');
    }

    public function destroy(string $id)
    {
        $this->lokasiRepository->deleteLokasi($id);

        Swal::toast('Lokasi Berhasil Dihapus', 'success')->timerProgressBar();

        return redirect()->route('admin.lokasi.index');
    }

    public function export()
    {
        return Excel::download(new LokasiExport, 'data_lokasi.xlsx');
    }
}