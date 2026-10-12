<?php

namespace App\Repositories;

use App\Interfaces\RelawanRepositoryInterface;
use App\Models\Relawan;
use App\Models\User;

class RelawanRepository implements RelawanRepositoryInterface
{
    public function getAllRelawans() {
        return Relawan::all();
    }

    public function getRelawanById(int $id) {
        return Relawan::where('id', $id)->first();
    }

    public function createRelawan(array $data)
{
    $user = User::create([
        'name' => $data['name'],
        'email' => $data['email'],
        'password' => bcrypt($data['password']),
        'status' => 'pending',
        'is_verified' => false,
        'approved_at' => null,
    ]);

    $user->assignRole('relawan');

    return $user->relawan()->create($data);
}

    public function updateRelawan(int $id, array $data){
        $relawan = $this->getRelawanById($id);
        $relawan->user->update([
            'name'=> $data['name'],
            'password'=> isset($data['password']) ? bcrypt($data['password']) : $relawan->user->password,
        ]);
        return $relawan->update($data);
    }

    public function deleteRelawan(int $id) {
        $relawan = $this->getRelawanById($id);
        $relawan->user()->delete();
        return $relawan->delete();
    }
}