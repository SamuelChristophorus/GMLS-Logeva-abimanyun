<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRelawanRequest;
use App\Http\Requests\UpdateRelawanRequest;
use App\Interfaces\RelawanRepositoryInterface;
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

    public function store(StoreRelawanRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = time().'_'.$file->getClientOriginalName();

            $destinationPath = base_path('../logeva/storage/assets/avatar');

            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $file->move($destinationPath, $filename);

            $data['avatar'] = 'assets/avatar/'.$filename;
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
        return view('pages.admin.relawan.edit', compact('relawan'));
    }

    public function update(UpdateRelawanRequest $request, string $id)
    {
        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = time().'_'.$file->getClientOriginalName();

            $destinationPath = base_path('../logeva/storage/assets/avatar');

            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $file->move($destinationPath, $filename);

            $data['avatar'] = 'assets/avatar/'.$filename;
        }

        $this->relawanRepository->updateRelawan($id, $data);

        Swal::toast('Relawan Berhasil Diubah', 'success')->timerProgressBar();

        return redirect()->route('admin.relawan.index');
    }

    public function destroy(string $id)
    {
        $this->relawanRepository->deleteRelawan($id);

        Swal::toast('Relawan Berhasil Dihapus', 'success')->timerProgressBar();

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