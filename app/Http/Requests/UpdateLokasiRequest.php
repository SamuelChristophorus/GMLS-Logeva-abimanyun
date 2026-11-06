<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLokasiRequest extends FormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'desa_id' => 'required|exists:desas,id',
            'relawan_id' => 'required|exists:relawans,id',
            'nama_lokasi'=> 'required',
            'alamat_lokasi'=> 'required',
            'luas_lokasi'=> 'required',
            'kapasitas_pengungsi'=> 'required',
            'gambar_lokasi'=> 'nullable',
            'latitude'=> 'required|string',
            'longitude'=> 'required|string',
            'ketinggian_mdpl' => 'nullable|numeric|between:-50,5000',
        ];
    }
}
