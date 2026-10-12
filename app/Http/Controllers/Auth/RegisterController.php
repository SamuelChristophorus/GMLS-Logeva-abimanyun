<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRelawanRequest;
use App\Interfaces\RelawanRepositoryInterface;

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
            $file = $request->file('avatar');

            $filename = time().'_'.$file->getClientOriginalName();

            $destinationPath = base_path('../logeva/storage/assets/avatar');

            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $file->move($destinationPath, $filename);

            $data['avatar'] = 'assets/avatar/'.$filename;
        }

        $this->relawanRepository->createRelawan($data);

        return redirect()
            ->route('login')
            ->with('success', 'Register Berhasil. Akun Anda menunggu persetujuan admin.');
    }
}