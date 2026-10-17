<?php

namespace App\Repositories;

use App\Interfaces\LokasiRepositoryInterface;
use App\Models\Desa;
use App\Models\Lokasi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class LokasiRepository implements LokasiRepositoryInterface
{
    /*
    |--------------------------------------------------------------------------
    | GET ALL LOKASI
    |--------------------------------------------------------------------------
    */
    public function getAllLokasis()
    {
        return Lokasi::all();
    }

    /*
    |--------------------------------------------------------------------------
    | GET NEARBY LOKASI
    |--------------------------------------------------------------------------
    */
   public function getNearbyLokasis($latitude, $longitude, $radius = 9999)
{
    $lokasis = Lokasi::whereNotNull('latitude')
        ->whereNotNull('longitude')
        ->where('latitude', '!=', '')
        ->where('longitude', '!=', '')
        ->get();

    $hasil = $lokasis->map(function ($lokasi) use ($latitude, $longitude) {

        $earthRadius = 6371;

        $latFrom = deg2rad($latitude);
        $lonFrom = deg2rad($longitude);

        $latTo = deg2rad($lokasi->latitude);
        $lonTo = deg2rad($lokasi->longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(
            sqrt(
                pow(sin($latDelta / 2), 2) +
                cos($latFrom) *
                cos($latTo) *
                pow(sin($lonDelta / 2), 2)
            )
        );

        $distance = $earthRadius * $angle;

        $lokasi->distance = round($distance, 1);

        return $lokasi;
    });

    return $hasil
        ->sortBy('distance')
        ->values();
}

    /*
    |--------------------------------------------------------------------------
    | GET LATEST LOKASI
    |--------------------------------------------------------------------------
    */
    public function getLatestLokasi()
    {
        return Lokasi::latest()->take(6)->get();
    }

    /*
    |--------------------------------------------------------------------------
    | GET LOKASI BY RELAWAN
    |--------------------------------------------------------------------------
    */
    public function getLokasiByRelawanId(string $status)
    {
        $user = Auth::user();

        if (!$user || !$user->relawan) {
            return collect();
        }

        return $this->getLokasiByStatus($status, $user->relawan->id);
    }

    /*
    |--------------------------------------------------------------------------
    | GET LOKASI BY STATUS PERSETUJUAN TERAKHIR
    | $relawanId = null -> semua relawan (dipakai admin)
    |--------------------------------------------------------------------------
    */
    public function getLokasiByStatus(string $status, ?int $relawanId = null)
    {
        return Lokasi::with(['desa', 'statusLokasi', 'relawan.user'])
            ->when($relawanId, fn ($q) => $q->where('relawan_id', $relawanId))

            ->whereHas('statusLokasi', function (Builder $query) use ($status) {

                $query->where('status', $status)

                    ->whereIn('id', function ($subQuery) {

                        $subQuery->selectRaw('MAX(id)')
                            ->from('status_lokasis')
                            ->whereNull('deleted_at')
                            ->groupBy('lokasi_id');
                    });
            })

            ->latest()
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | JUMLAH LOKASI PER STATUS PERSETUJUAN TERAKHIR
    |--------------------------------------------------------------------------
    */
    public function countLokasiPerStatus(?int $relawanId = null): array
    {
        $counts = Lokasi::query()
            ->when($relawanId, fn ($q) => $q->where('lokasis.relawan_id', $relawanId))
            ->join('status_lokasis', 'status_lokasis.lokasi_id', '=', 'lokasis.id')
            ->whereIn('status_lokasis.id', function ($subQuery) {
                $subQuery->selectRaw('MAX(id)')
                    ->from('status_lokasis')
                    ->whereNull('deleted_at')
                    ->groupBy('lokasi_id');
            })
            ->selectRaw('status_lokasis.status, COUNT(*) as jumlah')
            ->groupBy('status_lokasis.status')
            ->pluck('jumlah', 'status');

        return [
            'pending'  => (int) ($counts['pending'] ?? 0),
            'approved' => (int) ($counts['approved'] ?? 0),
            'rejected' => (int) ($counts['rejected'] ?? 0),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | GET LOKASI BY ID
    |--------------------------------------------------------------------------
    */
    public function getLokasiById(int $id)
{
    return Lokasi::with([
        'desa',
        'relawan.user',
        'sphereLokasi',
        'statusLokasi',
        'stokLogistik.logistik'
    ])->findOrFail($id);
}

    /*
    |--------------------------------------------------------------------------
    | GET LOKASI BY DESA
    |--------------------------------------------------------------------------
    */
    public function getLokasiByDesa(string $desa)
    {
        $desa = Desa::where('nama_desa', $desa)->first();

        return Lokasi::where('desa_id', $desa->id)->get();
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE LOKASI
    |--------------------------------------------------------------------------
    */
    public function createLokasi(array $data)
    {
        $lokasi = Lokasi::create($data);

        $lokasi->statusLokasi()->create([
            'status'   => 'pending',
            'catatan'  => 'Lokasi Berhasil Diajukan'
        ]);

        return $lokasi;
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE LOKASI
    |--------------------------------------------------------------------------
    */
    public function updateLokasi(int $id, array $data)
    {
        $lokasi = $this->getLokasiById($id);

        return $lokasi->update($data);
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE LOKASI
    |--------------------------------------------------------------------------
    */
    public function deleteLokasi(int $id)
    {
        $lokasi = $this->getLokasiById($id);

        return $lokasi->delete();
    }
}