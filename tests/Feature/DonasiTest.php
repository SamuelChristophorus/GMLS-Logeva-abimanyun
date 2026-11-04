<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\Donasi;
use App\Models\GudangPusat;
use App\Models\Logistik;
use App\Models\Lokasi;
use App\Models\MutasiGudang;
use App\Models\Relawan;
use App\Models\StokGudang;
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
 * Donasi uang / logistik ke GMLS Pusat atau langsung ke posko, sampai tersalurkan
 * dan bisa dilacak donatur dengan kode donasi.
 */
class DonasiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $relawanUser;
    private Lokasi $posko;
    private Logistik $beras;
    private Logistik $air;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed([RolePermissionSeeder::class, KategoriLogistikSeeder::class, LogistikSeeder::class]);

        $this->admin = User::create([
            'name' => 'Admin Pusat', 'email' => 'admin@test.id', 'password' => bcrypt('rahasia123'),
            'status' => 'approved', 'is_verified' => 1,
        ]);
        $this->admin->assignRole('admin');

        $this->relawanUser = User::create([
            'name' => 'Relawan Posko', 'email' => 'relawan@test.id', 'password' => bcrypt('rahasia123'),
            'status' => 'approved', 'is_verified' => 1,
        ]);
        $this->relawanUser->assignRole('relawan');
        $relawan = Relawan::create(['user_id' => $this->relawanUser->id, 'avatar' => 'assets/default/avatar.png']);

        $desa = Desa::create(['nama_desa' => 'Sawarna', 'alamat_desa' => 'Kec. Bayah']);
        $this->posko = Lokasi::create([
            'desa_id' => $desa->id, 'relawan_id' => $relawan->id, 'nama_lokasi' => 'SDN 1 Sawarna',
            'alamat_lokasi' => 'Jl. Sawarna No. 1', 'luas_lokasi' => 350, 'kapasitas_pengungsi' => 100,
            'gambar_lokasi' => 'x.jpg', 'latitude' => '-6.98', 'longitude' => '106.30',
            'status_operasional' => Lokasi::OPS_BEROPERASI, 'mulai_beroperasi_at' => now(),
        ]);

        $this->beras = Logistik::where('nama_item', 'Beras')->firstOrFail();
        $this->air   = Logistik::where('nama_item', 'Air Mineral')->firstOrFail();

        GudangPusat::utama()->update(['nama_bank' => 'BRI', 'nomor_rekening' => '0123456789', 'atas_nama_rekening' => 'GMLS']);
    }

    private function stokPosko(Logistik $barang): int
    {
        return (int) StokLogistik::where('lokasi_id', $this->posko->id)->where('logistik_id', $barang->id)->value('jumlah_stok');
    }

    public function test_halaman_publik_bisa_dibuka_tanpa_login(): void
    {
        $this->get(route('donasi.index'))->assertOk()->assertSee('Donasi Sekarang');
        $this->get(route('donasi.create', ['posko' => $this->posko->id]))->assertOk()->assertSee('SDN 1 Sawarna')->assertSee('0123456789');
        $this->get(route('donasi.laporan'))->assertOk();
        $this->get(route('donasi.lacak', ['kode' => 'DN-TIDAKADA']))->assertOk()->assertSee('tidak ditemukan');
    }

    public function test_donasi_uang_ke_posko_diverifikasi_lalu_dibelikan_barang(): void
    {
        $this->post(route('donasi.store'), [
            'nama_donatur' => 'Budi Santoso', 'kontak' => '081234567890', 'anonim' => 1,
            'jenis' => 'uang', 'tujuan' => 'posko', 'lokasi_id' => $this->posko->id,
            'nominal' => 500000, 'bukti_transfer' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertRedirect();

        $donasi = Donasi::firstOrFail();
        $this->assertSame(Donasi::MENUNGGU, $donasi->status);
        Storage::disk('local')->assertExists($donasi->bukti_transfer);
        $this->assertSame(1, $this->admin->notifications()->count());

        // Donatur bisa melacak; nama tampil anonim, kontak tidak tampil
        $this->get(route('donasi.lacak', ['kode' => $donasi->kode]))
            ->assertOk()->assertSee('Donatur Anonim')->assertDontSee('081234567890');

        // Bukti transfer hanya untuk admin
        $this->get(route('admin.donasi.bukti', $donasi))->assertRedirect(route('login'));
        $this->actingAs($this->admin)->get(route('admin.donasi.bukti', $donasi))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.donasi.show', $donasi))->assertOk()->assertSee('Cek transfer');

        $this->actingAs($this->admin)->patch(route('admin.donasi.verifikasi', $donasi), ['nominal_diterima' => 500000])->assertRedirect();
        $this->assertSame(Donasi::DITERIMA, $donasi->fresh()->status);

        // Tidak boleh melebihi sisa dana
        $this->actingAs($this->admin)->post(route('admin.donasi.salurkan', $donasi), [
            'lokasi_id' => $this->posko->id, 'nominal' => 600000, 'tanggal' => now()->toDateString(), 'keterangan' => 'Beli beras',
        ])->assertSessionHasErrors('nominal');

        // Dibelikan beras 30 kg untuk posko -> stok posko bertambah
        $this->actingAs($this->admin)->post(route('admin.donasi.salurkan', $donasi), [
            'lokasi_id' => $this->posko->id, 'nominal' => 300000, 'logistik_id' => $this->beras->id, 'jumlah' => 30,
            'tanggal' => now()->toDateString(), 'keterangan' => 'Beli beras di Toko Sumber Rezeki',
        ])->assertSessionHasNoErrors();

        $this->assertSame(30, $this->stokPosko($this->beras));
        $this->assertSame(Donasi::SEBAGIAN, $donasi->fresh()->status);
        $this->assertSame($donasi->id, StokMasuk::where('lokasi_id', $this->posko->id)->value('donasi_id'));

        // Sisa dana untuk operasional -> selesai
        $this->actingAs($this->admin)->post(route('admin.donasi.salurkan', $donasi), [
            'nominal' => 200000, 'tanggal' => now()->toDateString(), 'keterangan' => 'Sewa mobil pengangkut',
        ])->assertSessionHasNoErrors();

        $this->assertSame(Donasi::SELESAI, $donasi->fresh()->status);
        $this->assertSame(0, $donasi->fresh()->sisaDana());

        $this->get(route('donasi.lacak', ['kode' => $donasi->kode]))
            ->assertSee('Beli beras di Toko Sumber Rezeki')->assertSee('Sewa mobil pengangkut')->assertSee('Seluruh donasi sudah tersalurkan');
        $this->get(route('donasi.laporan'))->assertSee('Rp 500.000')->assertSee('SDN 1 Sawarna');
    }

    public function test_donasi_barang_ke_pusat_masuk_gudang_lalu_dikirim_ke_posko(): void
    {
        $this->post(route('donasi.store'), [
            'nama_donatur' => 'PT Maju Jaya', 'kontak' => 'csr@majujaya.co.id',
            'jenis' => 'logistik', 'tujuan' => 'pusat',
            'items' => [
                ['logistik_id' => $this->beras->id, 'jumlah' => 100],
                ['logistik_id' => $this->air->id, 'jumlah' => 50],
                ['logistik_id' => $this->beras->id, 'jumlah' => 20],   // digabung jadi 120
                ['logistik_id' => '', 'jumlah' => ''],                 // baris kosong diabaikan
            ],
        ])->assertRedirect();

        $donasi = Donasi::with('items')->firstOrFail();
        $this->assertSame(120, (int) $donasi->items->firstWhere('logistik_id', $this->beras->id)->jumlah);
        $this->assertCount(2, $donasi->items);

        // Air hanya datang 40
        $air = $donasi->items->firstWhere('logistik_id', $this->air->id);
        $berasItem = $donasi->items->firstWhere('logistik_id', $this->beras->id);
        $this->actingAs($this->admin)->patch(route('admin.donasi.terima', $donasi), [
            'diterima' => [$berasItem->id => 120, $air->id => 40],
        ])->assertSessionHasNoErrors();

        $gudang = GudangPusat::utama();
        $this->assertSame(120, (int) StokGudang::where('gudang_pusat_id', $gudang->id)->where('logistik_id', $this->beras->id)->value('jumlah_stok'));
        $this->assertSame(Donasi::DITERIMA, $donasi->fresh()->status);

        // Kirim melebihi sisa donasi ditolak
        $this->actingAs($this->admin)->post(route('admin.donasi.salurkan', $donasi), [
            'lokasi_id' => $this->posko->id, 'logistik_id' => $this->air->id, 'jumlah' => 41, 'tanggal' => now()->toDateString(),
        ])->assertSessionHasErrors('jumlah');

        foreach ([[$this->beras, 120], [$this->air, 40]] as [$barang, $jumlah]) {
            $this->actingAs($this->admin)->post(route('admin.donasi.salurkan', $donasi), [
                'lokasi_id' => $this->posko->id, 'logistik_id' => $barang->id, 'jumlah' => $jumlah, 'tanggal' => now()->toDateString(),
            ])->assertSessionHasNoErrors();
        }

        $this->assertSame(120, $this->stokPosko($this->beras));
        $this->assertSame(40, $this->stokPosko($this->air));
        $this->assertSame(0, (int) StokGudang::where('logistik_id', $this->beras->id)->value('jumlah_stok'));
        $this->assertSame(2, MutasiGudang::where('jenis', MutasiGudang::KELUAR)->where('donasi_id', $donasi->id)->count());
        $this->assertSame(Donasi::SELESAI, $donasi->fresh()->status);

        // Relawan posko diberi tahu ada kiriman dari pusat
        $this->assertTrue($this->relawanUser->notifications()->where('data->judul', 'Kiriman dari GMLS Pusat')->exists());

        $this->actingAs($this->admin)->get(route('admin.gudang.index'))->assertOk()->assertSee($donasi->kode);
        $this->get(route('donasi.lacak', ['kode' => $donasi->kode]))->assertSee('Barang dikirim ke SDN 1 Sawarna');
    }

    public function test_donasi_barang_langsung_ke_posko_dikonfirmasi_relawan_posko(): void
    {
        $this->post(route('donasi.store'), [
            'nama_donatur' => 'Ibu Sari', 'kontak' => '085700001111',
            'jenis' => 'logistik', 'tujuan' => 'posko', 'lokasi_id' => $this->posko->id,
            'items' => [['logistik_id' => $this->air->id, 'jumlah' => 25]],
            'perkiraan_tiba' => now()->addDay()->toDateString(),
        ])->assertRedirect();

        $donasi = Donasi::with('items')->firstOrFail();
        $this->assertTrue($this->relawanUser->notifications()->where('data->judul', 'Kiriman donasi akan datang')->exists());

        // Muncul di halaman logistik posko
        $this->actingAs($this->relawanUser)->get(route('logistik.index', $this->posko->id))->assertOk()->assertSee($donasi->kode);

        // Relawan lain tidak boleh mengonfirmasi
        $lain = User::create(['name' => 'Lain', 'email' => 'lain@test.id', 'password' => bcrypt('x'), 'status' => 'approved', 'is_verified' => 1]);
        $lain->assignRole('relawan');
        $this->actingAs($lain)->get(route('donasi.konfirmasi', $donasi))->assertForbidden();

        $this->actingAs($this->relawanUser)->get(route('donasi.konfirmasi', $donasi))->assertOk();
        $this->actingAs($this->relawanUser)->post(route('donasi.konfirmasi.terima', $donasi), [
            'diterima' => [$donasi->items->first()->id => 25],
        ])->assertRedirect(route('logistik.index', $this->posko->id));

        $this->assertSame(25, $this->stokPosko($this->air));
        $this->assertSame(Donasi::SELESAI, $donasi->fresh()->status);

        // Tidak bisa dikonfirmasi dua kali
        $this->actingAs($this->relawanUser)->post(route('donasi.konfirmasi.terima', $donasi), [
            'diterima' => [$donasi->items->first()->id => 25],
        ])->assertSessionHasErrors('status');
        $this->assertSame(25, $this->stokPosko($this->air));
    }

    public function test_validasi_form_donasi(): void
    {
        // Tanpa rekening, donasi uang ditolak
        GudangPusat::utama()->update(['nomor_rekening' => null]);
        $this->post(route('donasi.store'), [
            'nama_donatur' => 'A', 'kontak' => '0812', 'jenis' => 'uang', 'tujuan' => 'pusat',
            'nominal' => 50000, 'bukti_transfer' => UploadedFile::fake()->image('b.jpg'),
        ])->assertSessionHasErrors('jenis');

        // Barang wajib diisi
        $this->post(route('donasi.store'), [
            'nama_donatur' => 'A', 'kontak' => '0812', 'jenis' => 'logistik', 'tujuan' => 'pusat',
            'items' => [['logistik_id' => '', 'jumlah' => '']],
        ])->assertSessionHasErrors('items');

        // Posko yang tidak beroperasi tidak bisa dipilih
        $this->posko->update(['status_operasional' => Lokasi::OPS_SELESAI]);
        $this->post(route('donasi.store'), [
            'nama_donatur' => 'A', 'kontak' => '0812', 'jenis' => 'logistik', 'tujuan' => 'posko', 'lokasi_id' => $this->posko->id,
            'items' => [['logistik_id' => $this->beras->id, 'jumlah' => 5]],
        ])->assertSessionHasErrors('lokasi_id');

        $this->assertSame(0, Donasi::count());
    }

    public function test_admin_tolak_donasi_dan_halaman_admin_terbuka(): void
    {
        $this->post(route('donasi.store'), [
            'nama_donatur' => 'Donatur Zqxw', 'kontak' => '0811', 'jenis' => 'uang', 'tujuan' => 'pusat',
            'nominal' => 100000, 'bukti_transfer' => UploadedFile::fake()->image('b.jpg'),
        ]);
        $donasi = Donasi::firstOrFail();

        $this->actingAs($this->admin)->get(route('admin.donasi.index'))->assertOk()->assertSee($donasi->kode);
        $this->actingAs($this->relawanUser)->get(route('admin.donasi.index'))->assertForbidden();

        $this->actingAs($this->admin)->patch(route('admin.donasi.tolak', $donasi), ['alasan' => 'Transfer tidak ditemukan'])->assertRedirect();
        $this->assertSame(Donasi::DITOLAK, $donasi->fresh()->status);
        $this->get(route('donasi.lacak', ['kode' => $donasi->kode]))->assertSee('Transfer tidak ditemukan');
        // Donasi yang ditolak tidak masuk laporan publik
        $this->get(route('donasi.laporan'))->assertViewHas('donasis', fn ($p) => $p->total() === 0);
    }
}
