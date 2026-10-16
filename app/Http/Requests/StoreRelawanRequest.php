<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRelawanRequest extends FormRequest
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
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            // Opsional: jika tidak diupload, controller memakai foto default.
            // Hanya gambar (maks. 2 MB) agar file berbahaya tidak bisa diupload.
            'avatar' => 'nullable|image|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.image' => 'Foto profil harus berupa gambar (PNG, JPG, JPEG).',
            'avatar.max'   => 'Ukuran foto profil maksimal 2 MB.',
        ];
    }
}
