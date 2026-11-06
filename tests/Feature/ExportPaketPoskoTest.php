<?php

namespace Tests\Feature;

use App\Exceptions\KataSandiPaketException;
use App\Models\Desa;
use App\Models\Logistik;
use App\Models\Lokasi;
use App\Models\Pengungsi;
use App\Models\PetugasPosko;
use App\Models\Relawan;
use App\Models\StokKeluar;
use App\Models\StokLogistik;
use App\Models\StokMasuk;
use App\Models\User;
use App\Services\PaketDataPoskoService;
use App\Support\BerkasTerkunci;
use Database\Seeders\KategoriLogistikSeeder;
use Database\Seeders\LogistikSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Export paket dari website (website -> HP) harus terbaca oleh pembaca paket yang sama,
 * dan bila dikirim balik ke website tidak membuat data dobel.
 */
class ExportPaketPoskoTest extends TestCase
{
    use RefreshDatabase;

    public function test_paket_dari_website_terkunci_bisa_dibaca_ulang_tanpa_data_dobel(): void
    {
        $this->seed([RolePermissionSeeder::class, KategoriLogistikSeeder::class, LogistikSeeder::class]);
        $admin = User::create(['name' => 'Admin Pusat', 'email' => 'admin@test.id', 'password' => bcrypt('x'), 'status' => 'approved', 'is_verified' => 1]);
        $admin->assignRole('admin');
        $u = User::create(['name' => 'Raja', 'email' => 'r@test.id', 'password' => bcrypt('x'), 'status' => 'approved', 'is_verified' => 1]);
        $u->assignRole('relawan');
        $relawan = Relawan::create(['user_id' => $u->id, 'avatar' => 'assets/default/avatar.png']);
        $desa = Desa::create(['nama_desa' => 'Sawarna', 'alamat_desa' => 'Kec. Bayah']);

        // Semua data dibuat langsung di website (belum punya kode)
        $posko = Lokasi::create([
            'desa_id' => $desa->id, 'relawan_id' => $relawan->id, 'nama_lokasi' => 'SDN 1 Sawarna', 'alamat_lokasi' => 'Jl. Sawarna, No. 1',
            'luas_lokasi' => 350, 'kapasitas_pengungsi' => 100, 'gambar_lokasi' => 'x.jpg', 'latitude' => '-6.98', 'longitude' => '106.30',
            'status_operasional' => Lokasi::OPS_BEROPERASI, 'mulai_beroperasi_at' => now()->subDays(3),
        ]);
        $siti = Pengungsi::create(['lokasi_id' => $posko->id, 'nama' => 'Siti Aminah', 'asal' => 'Sawarna', 'tanggal_lahir' => '1990-05-17',
            'nomor_kk' => '3602011234567890', 'usia' => 36, 'jenis_kelamin' => 'Perempuan', 'kondisi_kesehatan' => 'Sehat', 'kelompok_rentan' => 'Ibu Hamil']);
        Pengungsi::create(['lokasi_id' => $posko->id, 'nama' => 'Budi', 'asal' => 'Sawarna', 'usia' => 70, 'jenis_kelamin' => 'Laki-laki',
            'kondisi_kesehatan' => 'Sakit ringan', 'kelompok_rentan' => 'Lansia']);
        $beras = Logistik::where('nama_item', 'Beras')->firstOrFail();
        $masuk = StokMasuk::create(['lokasi_id' => $posko->id, 'logistik_id' => $beras->id, 'relawan_id' => $relawan->id, 'jumlah_masuk' => 50,
            'sumber_bantuan' => 'BPBD Lebak', 'tanggal_masuk' => now()->subDay()->toDateString()]);
        StokKeluar::create(['lokasi_id' => $posko->id, 'logistik_id' => $beras->id, 'user_id' => $u->id, 'jumlah_keluar' => 10,
            'tujuan' => 'dapur_umum', 'tanggal_keluar' => now()->toDateString()]);
        StokLogistik::create(['lokasi_id' => $posko->id, 'logistik_id' => $beras->id, 'jumlah_stok' => 40, 'status_ketersediaan' => 'Kuning']);
        PetugasPosko::create(['lokasi_id' => $posko->id, 'kode_perangkat' => 'hp-1', 'peran' => PetugasPosko::RELAWAN, 'nama' => 'Ujang', 'kontak' => '0812', 'paket_dibuat_pada' => now()]);

        $diubahSiti = $siti->fresh()->updated_at->toDateTimeString();
        $jumlah = fn () => [Lokasi::count(), Pengungsi::semuaKeberadaan()->count(), StokMasuk::count(), StokKeluar::count(), (int) StokLogistik::sum('jumlah_stok')];
        $sebelum = $jumlah();

        // Validasi form
        $this->actingAs($admin)->post(route('admin.paket.export'), ['lokasi' => $posko->id, 'sandi_ekspor' => 'abc', 'sandi_ekspor_confirmation' => 'abc'])
            ->assertSessionHasErrors('sandi_ekspor');
        $this->actingAs($admin)->post(route('admin.paket.export'), ['lokasi' => $posko->id, 'sandi_ekspor' => 'posko123', 'sandi_ekspor_confirmation' => 'beda123'])
            ->assertSessionHasErrors('sandi_ekspor');

        $res = $this->actingAs($admin)->post(route('admin.paket.export'), ['lokasi' => $posko->id, 'sandi_ekspor' => 'posko123', 'sandi_ekspor_confirmation' => 'posko123']);
        $res->assertOk()->assertDownload();
        $file = $res->baseResponse->getFile()->getPathname();

        // Terkunci: tanpa / dengan kata sandi salah tidak bisa dibuka
        $this->assertTrue(BerkasTerkunci::terkunci($file));
        $svc = app(PaketDataPoskoService::class);
        try {
            $svc->bacaZip($file, ['salah123']);
            $this->fail('Paket seharusnya tidak bisa dibuka dengan kata sandi salah');
        } catch (KataSandiPaketException) {
        }

        $isi = $svc->bacaZip($file, ['posko123']);
        $hasil = $svc->periksa($isi);

        // Tidak ada baris error di semua file
        foreach (['posko', 'pengungsi', 'masuk', 'keluar', 'petugas'] as $k) {
            $this->assertSame([], collect($hasil[$k])->where('status', 'error')->map(fn ($b) => $b['error'])->values()->all(), "error di {$k}");
        }
        $this->assertCount(2, $hasil['pengungsi']);
        $this->assertCount(1, $hasil['masuk']);
        $this->assertCount(1, $hasil['keluar']);
        $this->assertSame([], $hasil['posko_baru']);
        $this->assertSame([], $hasil['barang_baru']);
        $this->assertNotContains('tambah', collect($hasil['pengungsi'])->pluck('status')->all());
        $this->assertSame(['lewati'], collect([...$hasil['masuk'], ...$hasil['keluar']])->pluck('status')->unique()->values()->all());

        // Kode tersimpan tanpa mengubah waktu edit data
        $this->assertNotNull($posko->fresh()->kode);
        $this->assertNotNull($masuk->fresh()->kode);
        $this->assertSame($diubahSiti, $siti->fresh()->updated_at->toDateTimeString());

        // Dikirim balik ke website: tidak ada yang dobel / stok tidak berubah
        $svc->simpan($isi, [], 'paket_pusat.zip', $admin->id);
        $this->assertSame($sebelum, $jumlah());

        // Export ulang memakai kode yang sama
        $kodeLama = $posko->fresh()->kode;
        $this->actingAs($admin)->post(route('admin.paket.export'), ['lokasi' => 'semua', 'sandi_ekspor' => 'posko123', 'sandi_ekspor_confirmation' => 'posko123'])->assertOk();
        $this->assertSame($kodeLama, $posko->fresh()->kode);

        $this->actingAs($u)->post(route('admin.paket.export'), ['lokasi' => 'semua', 'sandi_ekspor' => 'posko123', 'sandi_ekspor_confirmation' => 'posko123'])->assertForbidden();
    }
}
