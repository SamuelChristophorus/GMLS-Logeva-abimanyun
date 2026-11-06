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

    /** Isi form donasi seperti manusia: buka form dulu, tunggu, jawab soal hitungan. */
    private function kirimDonasi(array $data, array $ubahPengaman = [], ?string $ip = null)
    {
        // Setiap kiriman dari IP berbeda, kecuali tes batas per IP (supaya tidak kena rate limit)
        $this->withServerVariables(['REMOTE_ADDR' => $ip ?? '10.0.' . random_int(0, 255) . '.' . random_int(1, 254)]);

        $this->get(route('donasi.create'))->assertOk();

        return $this->post(route('donasi.store'), $data + $ubahPengaman + [
            'jawaban'     => session('donasi_jawaban'),
            'dibuka_pada' => encrypt(now()->timestamp - 30),
        ]);
    }

    private function donasiBarangValid(array $ubah = []): array
    {
        return $ubah + [
            'nama_donatur' => 'Andi Wijaya', 'kontak' => '081211112222', 'jenis' => 'logistik', 'tujuan' => 'pusat',
            'items' => [['logistik_id' => $this->beras->id, 'jumlah' => 5]],
        ];
    }

    public function test_pengaman_form_donasi_dari_bot_dan_orang_iseng(): void
    {
        // Jawaban hitungan salah
        $this->kirimDonasi($this->donasiBarangValid(), ['jawaban' => '999'])->assertSessionHasErrors('jawaban');

        // Dikirim terlalu cepat (bot)
        $this->kirimDonasi($this->donasiBarangValid(), ['dibuka_pada' => encrypt(now()->timestamp)])->assertSessionHasErrors('jawaban');

        // Form kedaluwarsa / dipalsukan
        $this->kirimDonasi($this->donasiBarangValid(), ['dibuka_pada' => encrypt(now()->timestamp - 4 * 3600)])->assertSessionHasErrors('jawaban');
        $this->kirimDonasi($this->donasiBarangValid(), ['dibuka_pada' => 'palsu'])->assertSessionHasErrors('jawaban');

        // Langsung POST tanpa membuka form (tidak ada soal di session)
        $this->post(route('donasi.store'), $this->donasiBarangValid() + ['jawaban' => '5', 'dibuka_pada' => encrypt(now()->timestamp - 30)])
            ->assertSessionHasErrors('jawaban');

        // Kolom jebakan bot terisi
        $this->kirimDonasi($this->donasiBarangValid(['situs_web' => 'http://spam.test']))->assertStatus(422);

        // Kontak asal-asalan & link spam
        $this->kirimDonasi($this->donasiBarangValid(['kontak' => 'asdf']))->assertSessionHasErrors('kontak');
        $this->kirimDonasi($this->donasiBarangValid(['pesan' => 'promo murah https://spam.test']))->assertSessionHasErrors('pesan');
        $this->kirimDonasi($this->donasiBarangValid(['nama_donatur' => 'www.judi.test']))->assertSessionHasErrors('nama_donatur');

        $this->assertSame(0, Donasi::count());
    }

    public function test_batas_donasi_menunggu_per_kontak_dan_per_ip(): void
    {
        // Kontak ditulis dengan format berbeda tetap dianggap sama
        foreach (['0812-1111-2222', '0812 1111 2222', '081211112222'] as $kontak) {
            $this->kirimDonasi($this->donasiBarangValid(['kontak' => $kontak]))->assertSessionHasNoErrors();
        }
        $this->assertSame(['081211112222'], Donasi::distinct()->pluck('kontak')->all());

        // Donasi ke-4 dari kontak yang sama ditahan sampai yang lama dicek admin
        $this->kirimDonasi($this->donasiBarangValid())->assertSessionHasErrors('kontak');
        $this->assertSame(3, Donasi::count());

        // Dari satu IP: 5 kiriman dalam 10 menit boleh, ke-6 diblokir
        foreach (range(1, 5) as $i) {
            $this->kirimDonasi($this->donasiBarangValid(['kontak' => "08129999000{$i}"]), [], '10.9.9.9')->assertSessionHasNoErrors();
        }
        $this->kirimDonasi($this->donasiBarangValid(['kontak' => '081299990006']), [], '10.9.9.9')
            ->assertSessionHasErrors(['jawaban' => 'Terlalu banyak donasi dikirim dalam waktu singkat. Silakan coba lagi nanti atau hubungi GMLS.']);
        $this->assertSame(8, Donasi::count());
    }

    public function test_konfirmasi_barang_tidak_bisa_menggelembungkan_stok(): void
    {
        $this->kirimDonasi([
            'nama_donatur' => 'Ibu Sari', 'kontak' => '085700001111', 'jenis' => 'logistik', 'tujuan' => 'posko',
            'lokasi_id' => $this->posko->id, 'items' => [['logistik_id' => $this->air->id, 'jumlah' => 10]],
        ]);
        $donasi = Donasi::with('items')->firstOrFail();

        $this->actingAs($this->relawanUser)->post(route('donasi.konfirmasi.terima', $donasi), [
            'diterima' => [$donasi->items->first()->id => 1000],
        ])->assertSessionHasErrors('diterima');

        $this->assertSame(0, $this->stokPosko($this->air));
        $this->assertSame(Donasi::MENUNGGU, $donasi->fresh()->status);
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
        $this->kirimDonasi([
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
        $this->kirimDonasi([
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
        $this->kirimDonasi([
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
        $this->kirimDonasi([
            'nama_donatur' => 'Andi', 'kontak' => '081211112222', 'jenis' => 'uang', 'tujuan' => 'pusat',
            'nominal' => 50000, 'bukti_transfer' => UploadedFile::fake()->image('b.jpg'),
        ])->assertSessionHasErrors('jenis');

        // Barang wajib diisi
        $this->kirimDonasi([
            'nama_donatur' => 'Andi', 'kontak' => '081211112222', 'jenis' => 'logistik', 'tujuan' => 'pusat',
            'items' => [['logistik_id' => '', 'jumlah' => '']],
        ])->assertSessionHasErrors('items');

        // Posko yang tidak beroperasi tidak bisa dipilih
        $this->posko->update(['status_operasional' => Lokasi::OPS_SELESAI]);
        $this->kirimDonasi([
            'nama_donatur' => 'Andi', 'kontak' => '081211112222', 'jenis' => 'logistik', 'tujuan' => 'posko', 'lokasi_id' => $this->posko->id,
            'items' => [['logistik_id' => $this->beras->id, 'jumlah' => 5]],
        ])->assertSessionHasErrors('lokasi_id');

        $this->assertSame(0, Donasi::count());
    }

    public function test_pantau_stok_semua_posko_hanya_membaca_data(): void
    {
        StokLogistik::create(['lokasi_id' => $this->posko->id, 'logistik_id' => $this->beras->id, 'jumlah_stok' => 75, 'status_ketersediaan' => 'Kuning']);
        StokGudang::create(['gudang_pusat_id' => GudangPusat::utama()->id, 'logistik_id' => $this->air->id, 'jumlah_stok' => 240]);

        $tutup = Lokasi::create($this->posko->only(['desa_id', 'relawan_id', 'alamat_lokasi', 'luas_lokasi', 'kapasitas_pengungsi', 'gambar_lokasi', 'latitude', 'longitude'])
            + ['nama_lokasi' => 'Posko Sudah Tutup', 'status_operasional' => Lokasi::OPS_SELESAI]);

        $jumlahData = [StokLogistik::count(), StokGudang::count(), StokMasuk::count(), MutasiGudang::count()];

        $this->actingAs($this->admin)->get(route('admin.gudang.stok-posko'))
            ->assertOk()->assertSee('SDN 1 Sawarna')->assertSee('75')->assertSee('240')->assertDontSee('Posko Sudah Tutup');
        $this->actingAs($this->admin)->get(route('admin.gudang.stok-posko', ['semua' => 1]))
            ->assertOk()->assertSee('Posko Sudah Tutup');

        // Tombol "Kirim" membuka gudang pusat dengan posko sudah terpilih
        $this->actingAs($this->admin)->get(route('admin.gudang.index', ['posko' => $tutup->id]))
            ->assertOk()->assertSee('value="' . $tutup->id . '" selected', false);

        // Halaman pantau tidak mengubah / menggandakan data apa pun
        $this->assertSame($jumlahData, [StokLogistik::count(), StokGudang::count(), StokMasuk::count(), MutasiGudang::count()]);

        $this->actingAs($this->relawanUser)->get(route('admin.gudang.stok-posko'))->assertForbidden();
    }

    public function test_kirim_banyak_barang_dari_halaman_stok_semua_posko(): void
    {
        $gudang = GudangPusat::utama();
        StokGudang::create(['gudang_pusat_id' => $gudang->id, 'logistik_id' => $this->beras->id, 'jumlah_stok' => 100]);
        StokGudang::create(['gudang_pusat_id' => $gudang->id, 'logistik_id' => $this->air->id, 'jumlah_stok' => 20]);
        $stokGudang = fn (Logistik $l) => (int) StokGudang::where('logistik_id', $l->id)->value('jumlah_stok');

        $this->actingAs($this->admin)->get(route('admin.gudang.stok-posko'))->assertOk()->assertSee('kirimPosko(', false);

        // Air melebihi stok gudang -> tidak ada yang terkirim sama sekali (beras juga batal)
        $this->actingAs($this->admin)->from(route('admin.gudang.stok-posko'))->post(route('admin.gudang.kirim-banyak', $gudang), [
            'lokasi_id' => $this->posko->id, 'items' => [$this->beras->id => 40, $this->air->id => 25], 'tanggal' => now()->toDateString(),
        ])->assertRedirect(route('admin.gudang.stok-posko'))->assertSessionHasErrors('jumlah');
        $this->assertSame([100, 20, 0, 0], [$stokGudang($this->beras), $stokGudang($this->air), $this->stokPosko($this->beras), $this->stokPosko($this->air)]);

        // Semua 0 -> ditolak
        $this->actingAs($this->admin)->post(route('admin.gudang.kirim-banyak', $gudang), [
            'lokasi_id' => $this->posko->id, 'items' => [$this->beras->id => 0, $this->air->id => 0], 'tanggal' => now()->toDateString(),
        ])->assertSessionHasErrors('items');

        // Kirim dua barang sekaligus
        $this->actingAs($this->admin)->post(route('admin.gudang.kirim-banyak', $gudang), [
            'lokasi_id' => $this->posko->id, 'items' => [$this->beras->id => 40, $this->air->id => 20], 'tanggal' => now()->toDateString(),
            'keterangan' => 'Mobil GMLS',
        ])->assertSessionHasNoErrors();

        $this->assertSame([60, 0, 40, 20], [$stokGudang($this->beras), $stokGudang($this->air), $this->stokPosko($this->beras), $this->stokPosko($this->air)]);
        $this->assertSame(2, MutasiGudang::where('jenis', MutasiGudang::KELUAR)->where('lokasi_id', $this->posko->id)->count());
        $this->assertSame(2, StokMasuk::where('lokasi_id', $this->posko->id)->count());

        $this->actingAs($this->relawanUser)->post(route('admin.gudang.kirim-banyak', $gudang), [
            'lokasi_id' => $this->posko->id, 'items' => [$this->beras->id => 1], 'tanggal' => now()->toDateString(),
        ])->assertForbidden();
    }

    public function test_admin_tolak_donasi_dan_halaman_admin_terbuka(): void
    {
        $this->kirimDonasi([
            'nama_donatur' => 'Donatur Zqxw', 'kontak' => '081133334444', 'jenis' => 'uang', 'tujuan' => 'pusat',
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
