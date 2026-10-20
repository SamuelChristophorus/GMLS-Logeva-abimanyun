<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRelawanRequest;
use App\Interfaces\RelawanRepositoryInterface;
use App\Support\Upload;

class RegisterController extends Controller
{
    private RelawanRepositoryInterface $relawanRepository;

    public function __construct(RelawanRepositoryInterface $relawanRepository)
    {
        $this->relawanRepository = $relawanRepository;
    }

    public function index()
    {
        return view("pages.auth.register");
    }

    public function store(StoreRelawanRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            // Disimpan ke <folder public>/storage/assets/avatar (otomatis benar di localhost & Hostinger)
            $data['avatar'] = Upload::simpan($request->file('avatar'), 'assets/avatar');
        }

        $this->relawanRepository->createRelawan($data);

        return redirect()
            ->route('login')
            ->with('success', 'Register Berhasil. Akun Anda menunggu persetujuan admin.');
    }
}