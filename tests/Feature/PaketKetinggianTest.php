<?php

namespace Tests\Feature;

use App\Exports\PaketPoskoExport;
use App\Models\Desa;
use App\Models\Lokasi;
use App\Models\Relawan;
use App\Models\User;
use App\Services\PaketDataPoskoService;
use Database\Seeders\KategoriLogistikSeeder;
use Database\Seeders\LogistikSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

/** Kolom ketinggian_mdpl di posko.csv Paket Data Posko (aplikasi -> website). */
class PaketKetinggianTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Lokasi $posko;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, KategoriLogistikSeeder::class, LogistikSeeder::class]);
        $this->admin = User::create(['name' => 'Admin Pusat', 'email' => 'admin@test.id', 'password' => bcrypt('x'), 'status' => 'approved', 'is_verified' => 1]);
        $this->admin->assignRole('admin');
        $u = User::create(['name' => 'Raja', 'email' => 'r@test.id', 'password' => bcrypt('x'), 'status' => 'approved', 'is_verified' => 1]);
        $u->assignRole('relawan');
        $relawan = Relawan::create(['user_id' => $u->id, 'avatar' => 'assets/default/avatar.png']);
        $desa = Desa::create(['nama_desa' => 'Sawarna', 'alamat_desa' => 'Kec. Bayah']);
        $this->posko = Lokasi::create([
            'desa_id' => $desa->id, 'relawan_id' => $relawan->id, 'nama_lokasi' => 'Bukit Evakuasi', 'alamat_lokasi' => 'Jl. Sawarna',
            'luas_lokasi' => 420, 'kapasitas_pengungsi' => 120, 'gambar_lokasi' => 'x.jpg', 'latitude' => '-6.965', 'longitude' => '106.28',
            'ketinggian_mdpl' => 45.5,
        ]);
    }

    /** Paket seperti dari HP: posko.csv dari export website, ketinggian & diubah_pada diganti (atau kolomnya dibuang). */
    private function paketHp(?string $ketinggian, bool $tanpaKolom = false): array
    {
        $path = (new PaketPoskoExport(Lokasi::all(), 'tes'))->buatZipPolos();
        $zip = new ZipArchive();
        $zip->open($path);
        $svc = app(PaketDataPoskoService::class);
        $baris = $svc->csv($zip->getFromName('posko.csv'));

        $kolom = PaketDataPoskoService::KOLOM['posko.csv'];
        if (! $tanpaKolom) $kolom[] = 'ketinggian_mdpl';
        $isi = [];
        foreach ($baris as $b) {
            $b['diubah_pada'] = now()->addHour()->format('Y-m-d H:i:s');
            $b['ketinggian_mdpl'] = $ketinggian;
            $isi[] = array_map(fn ($k) => $b[$k] ?? '', $kolom);
        }
        $zip->addFromString('posko.csv', \App\Exports\PoskoAplikasiExport::csv($kolom, $isi));
        $zip->close();

        $hasil = $svc->bacaZip($path, []);
        @unlink($path);

        return $hasil;
    }

    public function test_ketinggian_dari_hp_disimpan(): void
    {
        $svc = app(PaketDataPoskoService::class);
        $isi = $this->paketHp('60.25');
        $hasil = $svc->periksa($isi);
        $this->assertSame('perbarui', $hasil['posko'][0]['status']);
        $this->assertArrayHasKey('ketinggian_mdpl', $hasil['posko'][0]['lama']);

        $svc->simpan($isi, [], 'paket.zip', $this->admin->id);
        $this->assertSame(60.25, (float) $this->posko->fresh()->ketinggian_mdpl);
        $this->assertSame(1, Lokasi::count());
    }

    public function test_paket_tanpa_kolom_ketinggian_tidak_menghapusnya(): void
    {
        $svc = app(PaketDataPoskoService::class);
        $isi = $this->paketHp(null, tanpaKolom: true);
        $svc->simpan($isi, [], 'paket.zip', $this->admin->id);
        $this->assertSame(45.5, (float) $this->posko->fresh()->ketinggian_mdpl);

        // Kolom ada tapi kosong = ketinggian dikosongkan di HP
        $svc->simpan($this->paketHp(''), [], 'paket.zip', $this->admin->id);
        $this->assertNull($this->posko->fresh()->ketinggian_mdpl);
    }

    public function test_ketinggian_tidak_valid_ditolak(): void
    {
        $hasil = app(PaketDataPoskoService::class)->periksa($this->paketHp('9000'));
        $this->assertSame('error', $hasil['posko'][0]['status']);
        $this->assertStringContainsString('Ketinggian', implode(' ', $hasil['posko'][0]['error']));
    }
}
