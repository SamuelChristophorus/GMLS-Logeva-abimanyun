<?php

namespace Tests\Feature;

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
use Database\Seeders\KategoriLogistikSeeder;
use Database\Seeders\LogistikSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * ALUR LAPANGAN UJUNG-KE-UJUNG (sisi website): Paket Data Posko ASLI dari
 * aplikasi (tests/Fixtures/paket_alur_lapangan.zip, dibuat oleh
 * test/services/alur_lapangan_test.dart di LOGEVA Client):
 * 2 relawan setor ke HP admin -> admin ekspor ke flashdisk -> diimpor di sini.
 */
class AlurLapanganTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Relawan $relawan;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed([RolePermissionSeeder::class, KategoriLogistikSeeder::class, LogistikSeeder::class]);
        $this->admin = User::create(['name' => 'Admin Pusat', 'email' => 'admin@test.id',
            'password' => bcrypt('rahasia123'), 'status' => 'approved', 'is_verified' => 1]);
        $this->admin->assignRole('admin');
        $u = User::create(['name' => 'Relawan Web', 'email' => 'relawan@test.id',
            'password' => bcrypt('rahasia123'), 'status' => 'approved', 'is_verified' => 1]);
        $u->assignRole('relawan');
        $this->relawan = Relawan::create(['user_id' => $u->id, 'avatar' => 'assets/default/avatar.png']);
        Desa::create(['nama_desa' => 'Panggarangan', 'alamat_desa' => 'Kec. Panggarangan']);
    }

    private function file(): UploadedFile
    {
        return new UploadedFile(base_path('tests/Fixtures/paket_alur_lapangan.zip'),
            'LOGEVA_paket_PoskoAlurLapangan.zip', 'application/zip', null, true);
    }

    private function stok(string $nama): int
    {
        $id = Logistik::where('nama_item', $nama)->value('id');
        return (int) StokLogistik::where('logistik_id', $id)->value('jumlah_stok');
    }

    public function test_paket_dari_hp_admin_digabung_ke_website(): void
    {
        // Tanpa kata sandi posko: ditolak, tidak ada yang tersimpan.
        $this->actingAs($this->admin)->post(route('admin.paket.preview'), ['file' => $this->file()])
            ->assertRedirect(route('admin.paket.index'));
        $this->assertSame(0, Lokasi::count());

        // Kata sandi posko benar -> pratinjau -> gabungkan.
        $html = $this->actingAs($this->admin)
            ->post(route('admin.paket.preview'), ['file' => $this->file(), 'kata_sandi' => 'SandiPosko2026'])
            ->assertOk()->assertSee('Siti Aminah')->assertSee('Mak Inah')->getContent();
        preg_match('/name="relawan\[([0-9a-f-]{36})\]"/', $html, $m);
        $this->actingAs($this->admin)
            ->post(route('admin.paket.confirm'), ['relawan' => [$m[1] => $this->relawan->id]])
            ->assertRedirect(route('admin.paket.index'));

        // Posko & pengungsi dari kedua relawan.
        $posko = Lokasi::sole();
        $this->assertSame('Posko Alur Lapangan', $posko->nama_lokasi);
        $this->assertSame(2, Pengungsi::semuaKeberadaan()->count(), 'Budi Kecil dihapus di HP: tidak dibuat');
        $siti = Pengungsi::where('nama', 'Siti Aminah')->sole();
        $this->assertSame('Sehat', $siti->kondisi_kesehatan, 'perbaikan data dari relawan terbawa');
        $this->assertSame('Ibu Hamil', $siti->kelompok_rentan);
        $this->assertSame('1990-05-17', \Illuminate\Support\Carbon::parse($siti->tanggal_lahir)->toDateString());
        $inah = Pengungsi::where('nama', 'Mak Inah')->sole();
        $this->assertNull($inah->tanggal_lahir, 'usia saja');
        $this->assertSame(72, (int) $inah->usia);

        // Logistik & stok.
        $this->assertSame(2, StokMasuk::count());
        $this->assertSame(1, StokKeluar::count());
        $this->assertSame(50, $this->stok('Beras'));
        $this->assertSame(80, $this->stok('Air Mineral'));

        // Penanggung jawab = admin posko (nama asli) + relawan.
        $petugas = PetugasPosko::where('lokasi_id', $posko->id)->get();
        $this->assertSame('Budi Santoso', $petugas->firstWhere('peran', PetugasPosko::ADMIN)?->nama);
        $this->assertEqualsCanonicalizing(['Andi', 'Rina'],
            $petugas->where('peran', PetugasPosko::RELAWAN)->pluck('nama')->all());

        // Paket yang sama diimpor lagi (kata sandi sudah diingat): tidak dobel.
        $this->actingAs($this->admin)->post(route('admin.paket.preview'), ['file' => $this->file()])->assertOk();
        $this->actingAs($this->admin)->post(route('admin.paket.confirm'), ['relawan' => []]);
        $this->assertSame([1, 2, 2, 1, 50, 80], [
            Lokasi::count(), Pengungsi::semuaKeberadaan()->count(), StokMasuk::count(),
            StokKeluar::count(), $this->stok('Beras'), $this->stok('Air Mineral'),
        ]);
    }
}
