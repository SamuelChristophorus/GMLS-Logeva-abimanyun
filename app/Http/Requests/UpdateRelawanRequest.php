<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRelawanRequest extends FormRequest
{
    

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'nullable|min:8',
            'avatar'=> 'file',
            // Posko tugas (satu relawan = satu posko); dicek lagi di after().
            'lokasi_id' => 'nullable|integer|exists:lokasis,id',
        ];
    }

    /** Kosong hanya boleh untuk relawan yang memang belum memegang posko. */
    public function after(): array
    {
        return [function ($validator) {
            $relawan = \App\Models\Relawan::find($this->route('relawan'));
            if (! $this->filled('lokasi_id') && $relawan && $relawan->lokasi()->exists()) {
                $validator->errors()->add('lokasi_id',
                    'Relawan ini penanggung jawab posko. Pilih posko tujuan (penanggung jawab lama akan bertukar posko).');
            }
        }];
    }
}
