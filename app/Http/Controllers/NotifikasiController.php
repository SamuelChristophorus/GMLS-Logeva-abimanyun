<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/** Ikon lonceng: buka notifikasi (tandai dibaca) & tandai semua dibaca. */
class NotifikasiController extends Controller
{
    public function buka(Request $request, string $id)
    {
        // Hanya notifikasi milik user yang login
        $notifikasi = $request->user()->notifications()->findOrFail($id);
        $notifikasi->markAsRead();

        $url = $notifikasi->data['url'] ?? null;

        // Hanya arahkan ke alamat di aplikasi ini
        return $url && str_starts_with($url, url('/'))
            ? redirect()->to($url)
            : redirect()->back();
    }

    public function bacaSemua(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
