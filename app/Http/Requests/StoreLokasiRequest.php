<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;


class StoreLokasiRequest extends FormRequest
{
   
    public function rules(): array
    {
        return [
            'desa_id' => 'required|exists:desas,id',
            // Relawan: diisi otomatis di controller dari akun yang login. Admin: wajib memilih.
            'relawan_id' => auth()->user()->hasRole('admin') ? 'required|exists:relawans,id' : 'nullable',
            'nama_lokasi'=> 'required',
            'alamat_lokasi'=> 'required',
            'luas_lokasi'=> 'required',
            'kapasitas_pengungsi'=> 'required',
            'gambar_lokasi'=> 'required|image|mimes:jpeg,png,jpg|max:2048',
            'latitude'=> 'required|string',
            'longitude'=> 'required|string',
            'air_hidup' => 'nullable',
            'air_kebersihan' => 'nullable',
            'air_memasak' => 'nullable',
            'toilet_pendek' => 'nullable',
            'toilet_panjang' => 'nullable',
            'kalori' => 'nullable',
            'protein' => 'nullable',
            'lemak' => 'nullable',
        ];
    }
}
