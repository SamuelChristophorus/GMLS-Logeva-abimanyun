<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\KategoriLogistik;
use App\Models\Logistik;
use App\Models\Lokasi;
use App\Models\Pengungsi;
use App\Models\PetugasPosko;
use App\Models\Relawan;
use App\Models\RiwayatImportPaket;
use App\Models\SelisihStokPaket;
use App\Models\StokKeluar;
use App\Models\StokLogistik;
use App\Models\StokMasuk;
use App\Models\User;
use Database\Seeders\KategoriLogistikSeeder;
use Database\Seeders\LogistikSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

/**
 * Import Paket Data Posko (§8): paket yang sama boleh diimpor berkali-kali dan
 * paket lama boleh datang setelah paket baru, tanpa data dobel.
 */
class PaketDataPoskoTest extends TestCase
{
    use RefreshDatabase;

    private const POSKO = '11111111-1111-4111-8111-111111111111';
    private const SITI = '22222222-2222-4222-8222-222222222222';
    private const BUDI = '33333333-3333-4333-8333-333333333333';
    private const MASUK = '44444444-4444-4444-8444-444444444444';
    private const KELUAR = '55555555-5555-4555-8555-555555555555';
    private const SELIMUT = '66666666-6666-4666-8666-666666666666';

    private User $admin;
    private Relawan $relawan;

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

        $user = User::create([
            'name' => 'Relawan Web', 'email' => 'relawan@test.id', 'password' => bcrypt('rahasia123'),
            'status' => 'approved', 'is_verified' => 1,
        ]);
        $user->assignRole('relawan');
        $this->relawan = Relawan::create(['user_id' => $user->id, 'avatar' => 'assets/default/avatar.png']);

        Desa::create(['nama_desa' => 'Panggarangan', 'alamat_desa' => 'Kec. Panggarangan']);
    }

    /* ---------------- pembuat paket ---------------- */

    private function csv(array $kolom, array $baris): string
    {
        $f = fopen('php://memory', 'r+');
        fputcsv($f, $kolom, ',', '"', '');
        foreach ($baris as $b) {
            fputcsv($f, array_map(fn ($k) => $b[$k] ?? '', $kolom), ',', '"', '');
        }
        rewind($f);
        return stream_get_contents($f);
    }

    private function paket(array $ubah = [], array $tanpa = []): UploadedFile
    {
        $k = \App\Services\PaketDataPoskoService::KOLOM + \App\Services\PaketDataPoskoService::KOLOM_OPSIONAL;
        $data = array_replace_recursive([
            'info.csv' => [['versi_format' => '1', 'dibuat_pada' => '2026-10-23 14:05:00',
                'kode_perangkat' => '99999999-9999-4999-8999-999999999999', 'diekspor_oleh' => 'Admin GMLS']],
            'posko.csv' => [[
                'kode_posko' => self::POSKO, 'nama_lokasi' => 'Balai Desa', 'nama_desa' => 'Desa Panggarangan',
                'alamat_lokasi' => 'Jl. Raya, No. 1', 'luas_lokasi' => '1050', 'kapasitas_pengungsi' => '300',
                'latitude' => '-6.9195', 'longitude' => '106.1297', 'status_operasional' => 'beroperasi',
                'mulai_beroperasi_at' => '2026-10-20 08:00:00', 'relawan_penanggung_jawab' => 'Andi',
                'diubah_pada' => '2026-10-20 08:00:00',
            ]],
            'pengungsi.csv' => [
                ['kode' => self::SITI, 'kode_posko' => self::POSKO, 'nama' => 'Siti Aminah', 'asal' => 'Cikaret',
                 'tanggal_lahir' => '1958-03-12', 'nomor_kk' => '', 'usia' => '68', 'jenis_kelamin' => 'Perempuan',
                 'kondisi_kesehatan' => 'Sehat', 'kelompok_rentan' => 'Lansia', 'dicatat_oleh' => 'Andi',
                 'dibuat_pada' => '2026-10-20 09:00:00', 'diubah_pada' => '2026-10-20 09:00:00'],
                ['kode' => self::BUDI, 'kode_posko' => self::POSKO, 'nama' => 'Budi', 'asal' => 'X',
                 'tanggal_lahir' => '', 'nomor_kk' => '3602011203580001', 'usia' => '40', 'jenis_kelamin' => 'Laki-laki',
                 'kondisi_kesehatan' => 'Demam', 'kelompok_rentan' => 'Tidak',
                 'dibuat_pada' => '2026-10-20 09:00:00', 'diubah_pada' => '2026-10-20 09:00:00'],
            ],
            'barang_masuk.csv' => [
                ['kode' => self::MASUK, 'kode_posko' => self::POSKO, 'nama_item' => 'Beras', 'kategori' => 'Makanan',
                 'satuan' => 'kg', 'jumlah_masuk' => '50', 'sumber_bantuan' => 'BPBD', 'tanggal_masuk' => '2026-10-20',
                 'dicatat_oleh' => 'Andi', 'dibuat_pada' => '2026-10-20 10:00:00'],
                ['kode' => self::SELIMUT, 'kode_posko' => self::POSKO, 'nama_item' => 'Selimut', 'kategori' => 'Perlengkapan Tidur',
                 'satuan' => 'buah', 'jumlah_masuk' => '40', 'sumber_bantuan' => 'BNPB', 'tanggal_masuk' => '2026-10-20',
                 'dibuat_pada' => '2026-10-20 10:00:00'],
            ],
            'barang_keluar.csv' => [
                ['kode' => self::KELUAR, 'kode_posko' => self::POSKO, 'nama_item' => 'Beras', 'kategori' => 'Makanan',
                 'satuan' => 'kg', 'jumlah_keluar' => '12', 'tujuan' => 'dapur_umum', 'tanggal_keluar' => '2026-10-21',
                 'dibuat_pada' => '2026-10-21 10:00:00'],
            ],
            'stok.csv' => [
                ['kode_posko' => self::POSKO, 'nama_item' => 'Beras', 'satuan' => 'kg', 'jumlah_stok' => '38'],
                ['kode_posko' => self::POSKO, 'nama_item' => 'Selimut', 'satuan' => 'buah', 'jumlah_stok' => '40'],
            ],
            'petugas.csv' => [
                ['kode_posko' => '', 'peran' => 'admin', 'nama' => 'Bu Rina', 'kontak' => 'rina@gmls.id'],
                ['kode_posko' => self::POSKO, 'peran' => 'relawan', 'nama' => 'Andi', 'kontak' => '0812-3456-7890'],
            ],
        ], $ubah);

        $path = tempnam(sys_get_temp_dir(), 'paket') . '.zip';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach ($k as $nama => $kolom) {
            if (in_array($nama, $tanpa, true)) continue;
            // Kolom tambahan (misal peran_perangkat, kode_akun) ikut ditulis.
            foreach ($data[$nama] ?? [] as $baris) {
                $kolom = array_values(array_unique(array_merge($kolom, array_keys($baris))));
            }
            $zip->addFromString($nama, $this->csv($kolom, $data[$nama] ?? []));
        }
        $zip->close();

        return new UploadedFile($path, 'paket_BalaiDesa_20261023-1405.zip', 'application/zip', null, true);
    }

    private function impor(UploadedFile $file, bool $denganRelawan = true)
    {
        $this->actingAs($this->admin)
            ->post(route('admin.paket.preview'), ['file' => $file])
            ->assertOk();

        return $this->actingAs($this->admin)->post(route('admin.paket.confirm'), [
            'relawan' => $denganRelawan ? [self::POSKO => $this->relawan->id] : [],
        ]);
    }

    private function stokBeras(): int
    {
        $beras = Logistik::where('nama_item', 'Beras')->first();
        return (int) StokLogistik::where('logistik_id', $beras->id)->value('jumlah_stok');
    }

    /* ---------------- tes ---------------- */

    public function test_hanya_admin_yang_bisa_membuka_menu(): void
    {
        $this->get(route('admin.paket.index'))->assertRedirect(route('login'));
        $this->actingAs($this->admin)->get(route('admin.paket.index'))
            ->assertOk()->assertSee('Import Paket Data Posko');
    }

    public function test_pratinjau_menampilkan_isi_paket(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.paket.preview'), ['file' => $this->paket()])
            ->assertOk()
            ->assertSee('Pratinjau Paket')
            ->assertSee('Balai Desa')
            ->assertSee('Siti Aminah')
            ->assertSee('Pilih relawan')
            ->assertSee('Selimut (Perlengkapan Tidur, buah)');

        // Pratinjau tidak menyimpan apa pun.
        $this->assertSame(0, Lokasi::count());
        $this->assertSame(0, Pengungsi::count());
    }

    public function test_posko_baru_wajib_dipilihkan_relawan(): void
    {
        $this->impor($this->paket(), false)->assertOk()->assertSee('Pilih relawan penanggung jawab');
        $this->assertSame(0, Lokasi::count());
    }

    public function test_gabung_paket_baru(): void
    {
        $this->impor($this->paket())->assertRedirect(route('admin.paket.index'));

        $lokasi = Lokasi::where('kode', self::POSKO)->firstOrFail();
        $this->assertSame('Balai Desa', $lokasi->nama_lokasi);
        $this->assertSame(Desa::where('nama_desa', 'Panggarangan')->value('id'), $lokasi->desa_id, 'desa dicocokkan tanpa kata "Desa"');
        $this->assertSame(300, (int) $lokasi->kapasitas_pengungsi);
        $this->assertSame($this->relawan->id, $lokasi->relawan_id);
        $this->assertTrue($lokasi->isApproved());
        $this->assertTrue($lokasi->isBeroperasi());
        $this->assertNotNull($lokasi->sphereLokasi);

        $this->assertSame(2, Pengungsi::count());
        $siti = Pengungsi::where('kode', self::SITI)->first();
        $this->assertNull($siti->nomor_kk, 'KK boleh kosong');
        $this->assertSame('Lansia', $siti->kelompok_rentan);
        $this->assertNull(Pengungsi::where('kode', self::BUDI)->value('tanggal_lahir'), 'data lama tanpa tanggal lahir diterima');

        $this->assertSame(2, StokMasuk::count());
        $this->assertSame(1, StokKeluar::count());
        $this->assertStringContainsString('Dicatat oleh Andi', StokMasuk::where('kode', self::MASUK)->value('keterangan'));
        $this->assertSame(38, $this->stokBeras());
        $this->assertDatabaseHas('logistiks', ['nama_item' => 'Selimut', 'satuan' => 'buah']);
    }

    public function test_paket_yang_sama_diimpor_dua_kali_tidak_dobel(): void
    {
        $this->impor($this->paket());
        $this->impor($this->paket())->assertRedirect();

        $this->assertSame(1, Lokasi::count());
        $this->assertSame(2, Pengungsi::count());
        $this->assertSame(2, StokMasuk::count());
        $this->assertSame(1, StokKeluar::count());
        $this->assertSame(38, $this->stokBeras(), 'stok tidak bertambah lagi');
        $this->assertSame(1, Logistik::where('nama_item', 'Selimut')->count());
    }

    public function test_paket_baru_memperbarui_dan_menghapus_lalu_paket_lama_tidak_mengembalikan(): void
    {
        $this->impor($this->paket());

        // Paket baru: Siti diedit, Budi dihapus, ada barang keluar baru.
        $baru = $this->paket([
            'pengungsi.csv' => [
                ['nama' => 'Siti Aminah Baru', 'diubah_pada' => '2026-10-22 09:00:00'],
                ['dihapus_pada' => '2026-10-22 09:00:00', 'diubah_pada' => '2026-10-22 09:00:00'],
            ],
            'barang_keluar.csv' => [
                [],
                ['kode' => '77777777-7777-4777-8777-777777777777', 'kode_posko' => self::POSKO, 'nama_item' => 'Beras',
                 'kategori' => 'Makanan', 'satuan' => 'kg', 'jumlah_keluar' => '8', 'tujuan' => 'dibagikan',
                 'tanggal_keluar' => '2026-10-22', 'dibuat_pada' => '2026-10-22 10:00:00'],
            ],
        ]);
        $this->impor($baru);

        $this->assertSame('Siti Aminah Baru', Pengungsi::where('kode', self::SITI)->value('nama'));
        $this->assertNull(Pengungsi::where('kode', self::BUDI)->first(), 'Budi dihapus di pusat');
        $this->assertDatabaseHas('paket_penghapusans', ['kode' => self::BUDI]);
        $this->assertSame(30, $this->stokBeras());

        // Paket LAMA datang belakangan.
        $this->impor($this->paket());
        $this->assertSame('Siti Aminah Baru', Pengungsi::where('kode', self::SITI)->value('nama'), 'versi lama tidak menimpa');
        $this->assertNull(Pengungsi::where('kode', self::BUDI)->first(), 'yang sudah dihapus tidak muncul lagi');
        $this->assertSame(30, $this->stokBeras());
    }

    public function test_posko_dan_pengungsi_yang_sudah_ada_di_website_disambungkan(): void
    {
        $lokasi = new Lokasi;
        $lokasi->forceFill([
            'desa_id' => Desa::first()->id, 'relawan_id' => $this->relawan->id, 'nama_lokasi' => 'balai desa',
            'alamat_lokasi' => 'Jl. Lama', 'luas_lokasi' => 700, 'kapasitas_pengungsi' => 200,
            'gambar_lokasi' => 'assets/default/lokasi.png', 'latitude' => '-6.9', 'longitude' => '106.1',
        ])->save();
        $siti = new Pengungsi;
        $siti->forceFill([
            'lokasi_id' => $lokasi->id, 'nama' => 'SITI  AMINAH', 'asal' => 'Cikaret', 'tanggal_lahir' => '1958-03-12',
            'usia' => 68, 'jenis_kelamin' => 'Perempuan', 'kondisi_kesehatan' => 'Sehat', 'kelompok_rentan' => 'Lansia',
        ])->save();

        // Posko sudah ada -> tidak perlu memilih relawan.
        $this->impor($this->paket(), false)->assertRedirect();

        $this->assertSame(1, Lokasi::count());
        $this->assertSame(self::POSKO, $lokasi->fresh()->kode);
        $this->assertSame(2, Pengungsi::count(), 'Siti tidak dobel');
        $this->assertSame(self::SITI, $siti->fresh()->kode);
    }

    public function test_baris_salah_dilewati_baris_lain_tetap_masuk(): void
    {
        $paket = $this->paket([
            'pengungsi.csv' => [
                ['nomor_kk' => '123', 'jenis_kelamin' => 'L'],
            ],
        ]);
        $this->actingAs($this->admin)->post(route('admin.paket.preview'), ['file' => $paket])
            ->assertOk()
            ->assertSee('Nomor KK harus 16 digit angka')
            ->assertSee('Jenis kelamin harus Laki-laki atau Perempuan');

        $this->actingAs($this->admin)->post(route('admin.paket.confirm'), ['relawan' => [self::POSKO => $this->relawan->id]]);
        $this->assertSame(1, Pengungsi::count());
        $this->assertSame(self::BUDI, Pengungsi::value('kode'));
    }

    public function test_petugas_admin_dan_relawan_tersimpan_dan_tampil(): void
    {
        $this->actingAs($this->admin)->post(route('admin.paket.preview'), ['file' => $this->paket()])
            ->assertOk()
            ->assertSee('Petugas Lapangan')
            ->assertSee('Admin (penanggung jawab)')
            ->assertSee('0812-3456-7890');

        $this->impor($this->paket());
        $lokasi = Lokasi::first();
        $this->assertSame(
            [['admin', 'Bu Rina', 'rina@gmls.id'], ['relawan', 'Andi', '0812-3456-7890']],
            $lokasi->petugas->map(fn ($t) => [$t->peran, $t->nama, $t->kontak])->all()
        );

        $this->actingAs($this->admin)->get(route('admin.lokasi.show', $lokasi->id))
            ->assertOk()
            ->assertSee('Petugas Lapangan (Aplikasi Offline)')
            ->assertSee('Bu Rina')
            ->assertSee('rina@gmls.id');

        // Impor ulang paket yang sama: tidak dobel.
        $this->impor($this->paket());
        $this->assertSame(2, PetugasPosko::count());
    }

    public function test_daftar_petugas_hanya_diganti_paket_yang_lebih_baru(): void
    {
        $this->impor($this->paket());

        // Paket lebih baru: relawan berganti.
        $baru = $this->paket([
            'info.csv'    => [['dibuat_pada' => '2026-10-24 08:00:00']],
            'petugas.csv' => [[], ['nama' => 'Budi Relawan', 'kontak' => '0813']],
        ]);
        $this->impor($baru);
        $this->assertSame(['Bu Rina', 'Budi Relawan'], PetugasPosko::orderBy('peran')->pluck('nama')->all());

        // Paket lama datang belakangan: tidak menimpa.
        $this->impor($this->paket());
        $this->assertSame(['Bu Rina', 'Budi Relawan'], PetugasPosko::orderBy('peran')->pluck('nama')->all());

        // Paket tanpa petugas.csv (aplikasi versi lama) tetap bisa diimpor & tidak menghapus daftar.
        $this->impor($this->paket([], ['petugas.csv']))->assertRedirect(route('admin.paket.index'));
        $this->assertSame(2, PetugasPosko::count());
    }

    public function test_beberapa_hp_satu_posko_tidak_dobel(): void
    {
        $akunAdmin = '77777777-7777-4777-8777-777777777771';
        $akunAndi = '77777777-7777-4777-8777-777777777772';
        $petugas = [
            ['kode_akun' => $akunAdmin],
            ['kode_akun' => $akunAndi],
        ];

        // Paket dari HP Induk.
        $this->impor($this->paket([
            'info.csv'    => [['peran_perangkat' => 'induk']],
            'petugas.csv' => $petugas,
        ]));

        // Paket dari HP Anggota posko yang sama: kode posko, pengungsi, catatan, dan
        // akun sama (sudah disinkronkan), tapi kode perangkat berbeda.
        $dariAnggota = $this->paket([
            'info.csv'    => [['kode_perangkat' => '88888888-8888-4888-8888-888888888888',
                               'dibuat_pada' => '2026-10-24 09:00:00', 'peran_perangkat' => 'anggota']],
            'petugas.csv' => $petugas,
        ]);
        $this->actingAs($this->admin)->post(route('admin.paket.preview'), ['file' => $dariAnggota])
            ->assertOk()
            ->assertSee('dari HP Anggota (relawan)');
        $this->impor($this->paket([
            'info.csv'    => [['kode_perangkat' => '88888888-8888-4888-8888-888888888888',
                               'dibuat_pada' => '2026-10-24 09:00:00', 'peran_perangkat' => 'anggota']],
            'petugas.csv' => $petugas,
        ]), false)->assertRedirect(route('admin.paket.index'));

        $this->assertSame(1, Lokasi::count());
        $this->assertSame(2, Pengungsi::count());
        $this->assertSame(2, StokMasuk::count());
        $this->assertSame(38, $this->stokBeras(), 'stok tidak terhitung dua kali');

        $lokasi = Lokasi::first();
        $this->assertSame(4, $lokasi->petugas->count(), 'disimpan per HP');
        $this->assertSame(['Bu Rina', 'Andi'], $lokasi->petugasUnik()->pluck('nama')->all());

        $halaman = $this->actingAs($this->admin)->get(route('admin.lokasi.show', $lokasi->id))->assertOk();
        $this->assertSame(1, substr_count($halaman->getContent(), 'rina@gmls.id'),
            'setiap petugas tampil satu kali');
    }

    public function test_riwayat_import_dan_paket_terakhir_tercatat(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 25)->setTime(10, 0));

        $this->impor($this->paket([
            'info.csv' => [['peran_perangkat' => 'induk']],
        ]))->assertRedirect(route('admin.paket.index'));

        $r = RiwayatImportPaket::sole();
        $this->assertSame($this->admin->id, $r->user_id);
        $this->assertSame('paket_BalaiDesa_20261023-1405.zip', $r->nama_file);
        $this->assertSame('induk', $r->peran_perangkat);
        $this->assertSame('Admin GMLS', $r->diekspor_oleh);
        $this->assertSame('2026-10-23 14:05:00', $r->paket_dibuat_pada->format('Y-m-d H:i:s'));
        $this->assertSame(2, $r->jumlah['pengungsi']);
        $this->assertSame([Lokasi::first()->id], $r->lokasis->pluck('id')->all());

        // Paket yang sama diimpor lagi tetap dicatat (untuk ditelusuri), tanpa data dobel.
        $this->impor($this->paket([
            'info.csv' => [['peran_perangkat' => 'induk']],
        ]));
        $this->assertSame(2, RiwayatImportPaket::count());
        $this->assertSame(0, RiwayatImportPaket::latest('id')->first()->jumlah['pengungsi']);

        $this->actingAs($this->admin)->get(route('admin.paket.index'))
            ->assertOk()
            ->assertSee('Riwayat import paket')
            ->assertSee('paket_BalaiDesa_20261023-1405.zip')
            ->assertSee('HP Induk (supervisor)')
            ->assertSee('Admin Pusat');

        $lokasi = Lokasi::first();
        $this->actingAs($this->admin)->get(route('admin.lokasi.index'))
            ->assertOk()
            ->assertSee('Paket terakhir: 2 hari lalu');
        $this->actingAs($this->admin)->get(route('admin.lokasi.show', $lokasi->id))
            ->assertOk()
            ->assertSee('Kiriman Data Aplikasi Offline')
            ->assertSee('Paket terakhir: 2 hari lalu')
            ->assertDontSee('hubungi petugas posko');

        // Lebih dari 3 hari tanpa paket baru: diberi tanda untuk dihubungi.
        $this->travelTo(now()->setDate(2026, 10, 28)->setTime(10, 0));
        $this->actingAs($this->admin)->get(route('admin.lokasi.show', $lokasi->id))
            ->assertOk()
            ->assertSee('Paket terakhir: 5 hari lalu')
            ->assertSee('hubungi petugas posko');
    }

    public function test_posko_tanpa_paket_tidak_menampilkan_tanda_paket(): void
    {
        $this->impor($this->paket());
        DB::table('lokasi_riwayat_import_paket')->delete();

        $this->actingAs($this->admin)->get(route('admin.lokasi.index'))
            ->assertOk()
            ->assertDontSee('Paket terakhir');
        $this->actingAs($this->admin)->get(route('admin.lokasi.show', Lokasi::first()->id))
            ->assertOk()
            ->assertDontSee('Kiriman Data Aplikasi Offline');
    }

    public function test_export_master_logistik_csv_untuk_aplikasi(): void
    {
        $kategori = KategoriLogistik::first();
        Logistik::create([
            'kategori_logistik_id' => $kategori->id,
            'nama_item' => 'Air, kemasan "gelas"', 'satuan' => 'liter', 'kebutuhan_harian' => 2.5,
        ]);

        $res = $this->actingAs($this->admin)->get(route('admin.logistik.export.csv'))->assertOk();
        $this->assertStringContainsString('text/csv', $res->headers->get('Content-Type'));
        $isi = $res->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF\"Nama Item\",Kategori,Satuan,\"Kebutuhan Harian\",Deskripsi\n", $isi);
        $this->assertStringContainsString(
            '"Air, kemasan ""gelas""",' . $kategori->nama_kategori . ',liter,"2,5",', $isi);
        $this->assertSame(Logistik::count() + 1, substr_count(trim($isi), "\n") + 1, 'satu baris per barang + judul');

        $this->actingAs($this->admin)->get(route('admin.logistik.index'))
            ->assertOk()
            ->assertSee('Export CSV (Aplikasi)');
    }

    public function test_pencatat_pengungsi_dari_aplikasi_disimpan(): void
    {
        $this->impor($this->paket());

        $siti = Pengungsi::where('kode', self::SITI)->sole();
        $this->assertSame('Andi', $siti->dicatat_oleh);
        $this->assertNull(Pengungsi::where('kode', self::BUDI)->sole()->dicatat_oleh);

        $this->actingAs($this->relawan->user)->get(route('pengungsi.show', $siti->id))
            ->assertOk()
            ->assertSee('Dicatat oleh')
            ->assertSee('Andi');

        // Paket lebih baru tanpa nama pencatat tidak menghapus nama yang sudah ada.
        $this->impor($this->paket([
            'info.csv'      => [['dibuat_pada' => '2026-10-24 08:00:00']],
            'pengungsi.csv' => [['dicatat_oleh' => '', 'asal' => 'Cikaret Hilir', 'diubah_pada' => '2026-10-24 07:00:00']],
        ]));
        $siti->refresh();
        $this->assertSame('Cikaret Hilir', $siti->asal);
        $this->assertSame('Andi', $siti->dicatat_oleh);
    }

    public function test_selisih_stok_disimpan_dan_diganti_paket_lebih_baru(): void
    {
        // Paket 1: HP mencatat stok beras 30, padahal dari catatannya pusat jadi 38.
        $this->impor($this->paket([
            'stok.csv' => [['jumlah_stok' => '30']],
        ]))->assertSessionHas('hasil_paket', fn ($h) => $h['selisih_stok'] === 1);

        $s = SelisihStokPaket::sole();
        $this->assertSame('Beras', $s->nama_item);
        $this->assertSame(30, $s->stok_hp);
        $this->assertSame(38, $s->stok_pusat);
        $this->assertSame(38, $this->stokBeras(), 'stok pusat tidak diubah');

        $this->actingAs($this->admin)->get(route('admin.paket.index'))
            ->assertOk()
            ->assertSee('1 barang')
            ->assertSee('1 selisih stok');
        $this->actingAs($this->admin)->get(route('admin.paket.selisih'))
            ->assertOk()
            ->assertSee('Selisih Stok Posko')
            ->assertSee('Balai Desa')
            ->assertSee('Beras')
            ->assertSee('-8')
            ->assertSee('Pusat lebih banyak');
        $this->actingAs($this->admin)->get(route('admin.lokasi.show', Lokasi::first()->id))
            ->assertOk()
            ->assertSee('Stok Beras di HP berbeda dengan stok pusat.');

        // Paket 2 (lebih baru) stoknya sudah cocok: selisih tidak berlaku lagi.
        $this->impor($this->paket([
            'info.csv' => [['dibuat_pada' => '2026-10-24 08:00:00']],
        ]));
        $this->assertCount(0, SelisihStokPaket::terkini());

        // Paket lama yang datang belakangan tidak memunculkan lagi selisih lama.
        $this->impor($this->paket([
            'info.csv' => [['dibuat_pada' => '2026-10-22 08:00:00']],
            'stok.csv' => [['jumlah_stok' => '30']],
        ]));
        $this->assertSame(2, SelisihStokPaket::count(), 'tetap tercatat di riwayat');
        $this->assertCount(0, SelisihStokPaket::terkini());

        $this->actingAs($this->admin)->get(route('admin.paket.selisih'))
            ->assertOk()
            ->assertSee('Tidak ada selisih');
    }

    public function test_stok_minus_dari_hp_anggota_tercatat_sebagai_selisih(): void
    {
        $this->impor($this->paket([
            'info.csv' => [['peran_perangkat' => 'anggota']],
            'stok.csv' => [['jumlah_stok' => '-5']],
        ]));

        $s = SelisihStokPaket::sole();
        $this->assertSame(-5, $s->stok_hp);
        $this->actingAs($this->admin)->get(route('admin.paket.selisih'))
            ->assertOk()
            ->assertSee('Stok di HP minus');
    }

    public function test_file_bukan_paket_ditolak(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'zip') . '.zip';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('lain.txt', 'halo');
        $zip->close();

        $this->actingAs($this->admin)
            ->post(route('admin.paket.preview'), ['file' => new UploadedFile($path, 'x.zip', 'application/zip', null, true)])
            ->assertRedirect(route('admin.paket.index'));
        $this->assertSame([], Storage::disk('local')->files('paket-import'), 'file sementara dihapus');
    }

    public function test_kk_boleh_kosong_di_form_website_dan_penghapusan_dicatat(): void
    {
        $this->impor($this->paket());
        $lokasi = Lokasi::first();

        $this->actingAs($this->admin)->post(route('pengungsi.store', $lokasi->id), [
            'nama' => 'Tanpa KK', 'asal' => 'X', 'tanggal_lahir' => '1990-01-01', 'nomor_kk' => '',
            'usia' => 36, 'jenis_kelamin' => 'Laki-laki',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('pengungsis', ['nama' => 'Tanpa KK', 'nomor_kk' => null]);

        $this->actingAs($this->admin)->post(route('pengungsi.store', $lokasi->id), [
            'nama' => 'KK Salah', 'asal' => 'X', 'tanggal_lahir' => '1990-01-01', 'nomor_kk' => '123',
            'usia' => 36, 'jenis_kelamin' => 'Laki-laki',
        ])->assertSessionHasErrors('nomor_kk');

        $siti = Pengungsi::where('kode', self::SITI)->first();
        $this->actingAs($this->admin)->delete(route('pengungsi.destroy', $siti->id));
        $this->assertDatabaseHas('paket_penghapusans', ['kode' => self::SITI]);

        // Paket diimpor lagi: Siti yang dihapus di website tidak muncul lagi.
        $this->impor($this->paket());
        $this->assertNull(Pengungsi::where('kode', self::SITI)->first());
        $this->assertSame(0, DB::table('pengungsis')->where('nama', 'Siti Aminah')->count());
    }
}
