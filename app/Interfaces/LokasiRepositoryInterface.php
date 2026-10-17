<?php

namespace App\Interfaces;

interface LokasiRepositoryInterface
{
    public function getAllLokasis();

    public function getLatestLokasi();

    public function getLokasiByRelawanId(string $status);
    public function getLokasiByStatus(string $status, ?int $relawanId = null);
    public function countLokasiPerStatus(?int $relawanId = null): array;
    public function getLokasiById(int $id);

    public function getLokasiByDesa(string $desa);
    public function createLokasi(array $data);
    public function updateLokasi(int $id, array $data);
    public function deleteLokasi(int $id);

    // Di file interface
    public function getNearbyLokasis($latitude, $longitude, $radius);
}