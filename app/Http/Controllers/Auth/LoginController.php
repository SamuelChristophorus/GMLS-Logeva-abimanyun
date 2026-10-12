<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLoginRequest;
use App\Interfaces\AuthRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{

    private AuthRepositoryInterface $authRepository;

    public function __construct(AuthRepositoryInterface $authRepository)
    {
        $this->authRepository = $authRepository;
    }

    public function index()
    {
        return view('pages.auth.login');
    }

    public function store(StoreLoginRequest $request)
{
    $credentials = $request->validated();

    if ($this->authRepository->login($credentials)) {

        $user = Auth::user();

        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->status === 'pending') {
            $this->authRepository->logout();

            return redirect()->route('login')->withErrors([
                'email' => 'Akun Anda masih menunggu persetujuan admin.',
            ]);
        }

        if ($user->status === 'rejected') {
            $this->authRepository->logout();

            return redirect()->route('login')->withErrors([
                'email' => 'Pendaftaran akun Anda ditolak oleh admin.',
            ]);
        }

        if ($user->status === 'suspended') {
            $this->authRepository->logout();

            return redirect()->route('login')->withErrors([
                'email' => 'Akun Anda sedang disuspend oleh admin.',
            ]);
        }

        if ($user->status !== 'approved' || !$user->is_verified) {
            $this->authRepository->logout();

            return redirect()->route('login')->withErrors([
                'email' => 'Akun Anda belum terverifikasi.',
            ]);
        }

        return redirect()->route('home');
    }

    return redirect()->route('login')->withErrors([
        'email' => 'Email atau password salah',
    ]);
}

    public function logout(){
        $this->authRepository->logout();
        return redirect()->route('login');
    }
}
