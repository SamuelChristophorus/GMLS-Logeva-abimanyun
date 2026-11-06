<?php

namespace Tests\Feature;

use App\Exports\PoskoAplikasiExport;
use App\Models\Desa;
use App\Models\Lokasi;
use App\Models\Relawan;
use App\Models\StatusLokasi;
use App\Models\User;
use App\Services\PaketDataPoskoService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

/** Export lokasi untuk aplikasi offline: ZIP berisi posko.csv (format Paket Data Posko) + foto. */
class ExportAplikasiTest extends TestCase
{
    use RefreshDatabase;

    private string $foto = 'assets/lokasi/gambar/_uji_export_aplikasi.jpg';

    protected function tearDown(): void
    {
        @unlink(public_path('storage/' . $this->foto));
        parent::tearDown();
    }

    public function test_zip_berisi_posko_csv_dan_foto_dengan_kapasitas_dihitung_ulang(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@test.id', 'password' => bcrypt('x'), 'status' => 'approved', 'is_verified' => 1]);
        $admin->assignRole('admin');
        $userRelawan = User::create(['name' => 'Raja Bimasakti', 'email' => 'r@test.id', 'password' => bcrypt('x'), 'status' => 'approved', 'is_verified' => 1]);
        $userRelawan->assignRole('relawan');
        $relawan = Relawan::create(['user_id' => $userRelawan->id, 'avatar' => 'assets/default/avatar.png']);
        $desa = Desa::create(['nama_desa' => 'Sawarna', 'alamat_desa' => 'Kec. Bayah']);

        @mkdir(dirname(public_path('storage/' . $this->foto)), 0755, true);
        file_put_contents(public_path('storage/' . $this->foto), 'isi-foto-uji');

        $dasar = ['desa_id' => $desa->id, 'relawan_id' => $relawan->id, 'alamat_lokasi' => 'Jl. Sawarna, No. 1',
                  'latitude' => '-6.98', 'longitude' => '106.30', 'status_operasional' => Lokasi::OPS_BEROPERASI];
        // Kapasitas tersimpan sengaja salah (999): export harus menghitung ulang dari luas
        $sdn = Lokasi::create($dasar + ['nama_lokasi' => 'SDN 1 Sawarna', 'luas_lokasi' => 350, 'kapasitas_pengungsi' => 999, 'gambar_lokasi' => $this->foto]);
        $tanpaFoto = Lokasi::create($dasar + ['nama_lokasi' => 'Balai Desa', 'luas_lokasi' => 70, 'kapasitas_pengungsi' => 20, 'gambar_lokasi' => 'tidak/ada.jpg']);
        $ditolak = Lokasi::create($dasar + ['nama_lokasi' => 'Posko Ditolak', 'luas_lokasi' => 35, 'kapasitas_pengungsi' => 10, 'gambar_lokasi' => 'x.jpg']);
        StatusLokasi::create(['lokasi_id' => $ditolak->id, 'status' => 'rejected', 'catatan' => 'Data tidak lengkap']);

        $res = $this->actingAs($admin)->get(route('admin.lokasi.export-aplikasi'));
        $res->assertOk()->assertDownload();

        $zip = new ZipArchive();
        $zip->open($res->baseResponse->getFile()->getPathname());

        $baris = array_map(fn ($b) => str_getcsv($b, ',', '"', ''), array_filter(explode("\n", $zip->getFromName('posko.csv'))));
        $judul = array_shift($baris);
        $data  = collect($baris)->map(fn ($b) => array_combine($judul, $b))->keyBy('nama_lokasi');

        $this->assertSame(PoskoAplikasiExport::KOLOM_POSKO, $judul);
        // Kolom wajib posko.csv di Paket Data Posko semuanya ada -> alur HP <-> website nyambung
        $this->assertEmpty(array_diff(PaketDataPoskoService::KOLOM['posko.csv'], $judul));
        $this->assertSame(['Balai Desa', 'SDN 1 Sawarna'], $data->keys()->all());

        $this->assertSame('100', $data['SDN 1 Sawarna']['kapasitas_pengungsi']);
        $this->assertSame('Raja Bimasakti', $data['SDN 1 Sawarna']['relawan_penanggung_jawab']);
        $this->assertSame('Sawarna', $data['SDN 1 Sawarna']['nama_desa']);

        // Posko dari website diberi kode UUID yang tersimpan (dipakai lagi saat paket HP kembali)
        $this->assertSame($sdn->fresh()->kode, $data['SDN 1 Sawarna']['kode_posko']);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $sdn->fresh()->kode);

        $this->assertSame("foto/{$sdn->fresh()->kode}.jpg", $data['SDN 1 Sawarna']['foto']);
        $this->assertSame('isi-foto-uji', $zip->getFromName($data['SDN 1 Sawarna']['foto']));
        $this->assertSame('', $data['Balai Desa']['foto']);
        $this->assertStringContainsString('jumlah_posko', $zip->getFromName('info.csv'));

        // Export ulang: kode tidak berubah
        $kodeLama = $sdn->fresh()->kode;
        $this->actingAs($admin)->get(route('admin.lokasi.export-aplikasi', ['lokasi' => $sdn->id]))->assertOk();
        $this->assertSame($kodeLama, $sdn->fresh()->kode);
        $this->assertNotNull($tanpaFoto->fresh()->kode);
    }
}
