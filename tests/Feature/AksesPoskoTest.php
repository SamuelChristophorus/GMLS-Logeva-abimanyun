<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\Lokasi;
use App\Models\Pengungsi;
use App\Models\Relawan;
use App\Models\User;
use Database\Seeders\KategoriLogistikSeeder;
use Database\Seeders\LogistikSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hak akses posko: data pengungsi & logistik hanya untuk ADMIN dan RELAWAN
 * PENANGGUNG JAWAB posko itu. Relawan yang belum ditugaskan (atau bertugas
 * di posko lain) tidak bisa melihat / mengubahnya.
 */
class AksesPoskoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $pj;        // relawan penanggung jawab posko
    private User $lain;      // relawan posko lain
    private User $belum;     // relawan belum ditugaskan
    private Lokasi $posko;
    private Pengungsi $siti;

    private function user(string $nama, string $peran): User
    {
        $u = User::create(['name' => $nama, 'email' => strtolower($nama) . '@t.id',
            'password' => bcrypt('x12345678'), 'status' => 'approved', 'is_verified' => 1]);
        $u->assignRole($peran);
        return $u;
    }

    private function poskoUntuk(User $u, string $nama): Lokasi
    {
        $relawan = Relawan::firstOrCreate(['user_id' => $u->id], ['avatar' => 'assets/default/avatar.png']);
        return Lokasi::create([
            'desa_id' => Desa::first()->id, 'relawan_id' => $relawan->id, 'nama_lokasi' => $nama,
            'alamat_lokasi' => 'Jl. 1', 'luas_lokasi' => 300, 'kapasitas_pengungsi' => 100,
            'gambar_lokasi' => 'x.jpg', 'latitude' => '-6.9', 'longitude' => '106.1',
            'status' => 'approved', 'status_operasional' => Lokasi::OPS_BEROPERASI, 'mulai_beroperasi_at' => now(),
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, KategoriLogistikSeeder::class, LogistikSeeder::class]);
        Desa::create(['nama_desa' => 'Panggarangan', 'alamat_desa' => 'Kec. Panggarangan']);
        $this->admin = $this->user('Admin', 'admin');
        $this->pj = $this->user('Penanggung', 'relawan');
        $this->lain = $this->user('Lain', 'relawan');
        $this->belum = $this->user('Belum', 'relawan');
        Relawan::create(['user_id' => $this->belum->id, 'avatar' => 'assets/default/avatar.png']);

        $this->posko = $this->poskoUntuk($this->pj, 'Posko A');
        $this->poskoUntuk($this->lain, 'Posko B');
        $this->siti = Pengungsi::create(['lokasi_id' => $this->posko->id, 'nama' => 'Siti Aminah', 'asal' => 'X',
            'usia' => 40, 'jenis_kelamin' => 'Perempuan', 'kondisi_kesehatan' => 'Sakit ringan - demam', 'kelompok_rentan' => 'Tidak']);
    }

    public function test_relawan_belum_ditugaskan_dan_posko_lain_ditolak(): void
    {
        foreach ([$this->belum, $this->lain] as $u) {
            $this->actingAs($u)->get(route('pengungsi.index', $this->posko->id))->assertForbidden();
            $this->actingAs($u)->get(route('pengungsi.show', $this->siti->id))->assertForbidden();
            $this->actingAs($u)->get(route('pengungsi.edit', $this->siti->id))->assertForbidden();
            $this->actingAs($u)->get(route('pengungsi.export', $this->posko->id))->assertForbidden();
            $this->actingAs($u)->post(route('pengungsi.store', $this->posko->id), [
                'nama' => 'Penyusup', 'asal' => 'X', 'usia' => 20, 'jenis_kelamin' => 'Laki-laki',
            ])->assertForbidden();
            $this->actingAs($u)->put(route('pengungsi.update', $this->siti->id), [
                'nama' => 'Diubah', 'asal' => 'X', 'usia' => 20, 'jenis_kelamin' => 'Laki-laki',
            ])->assertForbidden();
            $this->actingAs($u)->delete(route('pengungsi.destroy', $this->siti->id))->assertForbidden();
            $this->actingAs($u)->get(route('logistik.index', $this->posko->id))->assertForbidden();
            $this->actingAs($u)->get(route('logistik.create', $this->posko->id))->assertForbidden();

            // Detail posko tetap bisa dibuka, tapi tanpa nama pengungsi & tombol kelola.
            $this->actingAs($u)->get(route('lokasi.show', $this->posko->id))
                ->assertOk()->assertDontSee('Siti Aminah')->assertDontSee('Kelola Pengungsi');
        }
        $this->assertSame('Siti Aminah', $this->siti->fresh()->nama);
        $this->assertSame(1, Pengungsi::count());
    }

    /**
     * Celah lain yang bisa dicoba lewat URL / kiriman form langsung (tanpa tombol):
     * import pengungsi, barang masuk, barang keluar. Semuanya harus ditolak & tidak mengubah data.
     */
    public function test_relawan_lain_tidak_bisa_lewat_url_langsung(): void
    {
        $beras = \App\Models\Logistik::first();
        foreach ([$this->belum, $this->lain] as $u) {
            $this->actingAs($u)->get(route('pengungsi.import.template', $this->posko->id))->assertForbidden();
            $this->actingAs($u)->post(route('pengungsi.import.preview', $this->posko->id))->assertForbidden();
            $this->actingAs($u)->post(route('pengungsi.import.confirm', $this->posko->id))->assertForbidden();

            $this->actingAs($u)->get(route('logistik.keluar', $this->posko->id))->assertForbidden();
            $this->actingAs($u)->post(route('stok-masuk.store'), [
                'lokasi_id' => $this->posko->id, 'logistik_id' => $beras->id, 'jumlah_masuk' => 50,
                'sumber_bantuan' => 'Penyusup', 'tanggal_masuk' => now()->toDateString(),
            ])->assertForbidden();
            $this->actingAs($u)->post(route('logistik.keluar.store', $this->posko->id), [
                'logistik_id' => $beras->id, 'jumlah_keluar' => 1, 'tujuan' => 'dibagikan',
                'tanggal_keluar' => now()->toDateString(),
            ])->assertForbidden();
        }
        $this->assertSame(0, \App\Models\StokMasuk::count());
        $this->assertSame(0, \App\Models\StokKeluar::count());
        $this->assertSame(1, Pengungsi::count());
    }

    public function test_relawan_lain_mendapat_penjelasan_bukan_halaman_kosong(): void
    {
        // Di detail posko: keterangan siapa penanggung jawabnya
        $this->actingAs($this->lain)->get(route('lokasi.show', $this->posko->id))
            ->assertOk()->assertSee('bukan penanggung jawab', false)->assertSee('Penanggung');

        // Halaman 403 berbahasa Indonesia dengan jalan kembali
        $this->actingAs($this->lain)->get(route('pengungsi.index', $this->posko->id))
            ->assertForbidden()
            ->assertSee('Akses ditolak')
            ->assertSee('Anda tidak bertugas di posko ini.')
            ->assertSee(route('lokasi.index'));

        // 403 di luar posko (relawan membuka panel admin): tanpa penjelasan posko, tanpa pesan bahasa Inggris
        $this->actingAs($this->lain)->get(route('admin.dashboard'))
            ->assertForbidden()
            ->assertSee('Anda tidak berhak membuka halaman ini.')
            ->assertDontSee('penanggung jawab posko tersebut')
            ->assertDontSee('User does not have');
    }

    public function test_penanggung_jawab_dan_admin_bisa_kelola(): void
    {
        foreach ([$this->pj, $this->admin] as $u) {
            $this->actingAs($u)->get(route('pengungsi.index', $this->posko->id))->assertOk()->assertSee('Siti Aminah');
            $this->actingAs($u)->get(route('pengungsi.show', $this->siti->id))->assertOk();
            $this->actingAs($u)->get(route('logistik.index', $this->posko->id))->assertOk();
        }
        $this->actingAs($this->pj)->get(route('lokasi.show', $this->posko->id))
            ->assertOk()->assertSee('Kelola Pengungsi');

        // Admin: tombol Pengungsi & Logistik di detail posko panel admin.
        $this->actingAs($this->admin)->get(route('admin.lokasi.show', $this->posko->id))
            ->assertOk()
            ->assertSee(route('pengungsi.index', $this->posko->id))
            ->assertSee(route('logistik.index', $this->posko->id));
    }
}
