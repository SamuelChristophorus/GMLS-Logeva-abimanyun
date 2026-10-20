<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use RealRashid\SweetAlert\Facades\Alert as Swal;

class ProfileController extends Controller
{
    public function index(){
        return view('pages.app.profile');
    }

    /** Ganti password sendiri (relawan & admin). */
    public function updatePassword(Request $request)
    {
        $request->validateWithBag('password', [
            'password_lama' => ['required', 'current_password'],
            'password'      => ['required', 'confirmed', 'different:password_lama', Password::min(8)->letters()->numbers()],
        ], [
            'password_lama.required'         => 'Isi password lama Anda.',
            'password_lama.current_password' => 'Password lama salah.',
            'password.required'              => 'Isi password baru.',
            'password.confirmed'             => 'Ulangi password baru dengan benar (keduanya harus sama).',
            'password.different'             => 'Password baru harus berbeda dari password lama.',
            'password.min'                   => 'Password baru minimal 8 karakter.',
            'password.letters'               => 'Password baru harus mengandung huruf.',
            'password.numbers'               => 'Password baru harus mengandung angka.',
        ]);

        $user = $request->user();
        $user->password = Hash::make($request->password);
        $user->save();

        $request->session()->regenerate();

        Swal::toast('Password berhasil diganti', 'success')->timerProgressBar();

        return redirect()->route('profile');
    }
}
