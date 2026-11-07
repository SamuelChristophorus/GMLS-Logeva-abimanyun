<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\Lokasi;
use App\Models\Relawan;
use App\Models\StatusLokasi;
use App\Models\User;
use App\Services\ImportRuinriskService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/** Import file "Data TEA" dari aplikasi RUinRISK (format sama dengan data_tea_ruinrisk_20261106.xlsx). */
class ImportRuinriskTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Relawan $relawan;
    private Desa $desa;
    private Lokasi $sdn;

    private const JUDUL = ['No', 'ID Lokasi', 'Nama Lokasi', 'Kapasitas Pengungsi', 'Latitude', 'Longitude', 'Ketinggian (m dpl)',
        'Jenis Tempat', 'Fasilitas', 'Keterangan', 'Diusulkan Oleh', 'Asal Data', 'Status'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->admin = User::create(['name' => 'Admin GMLS', 'email' => 'admin@test.id', 'password' => bcrypt('x'), 'status' => 'approved', 'is_verified' => 1]);
        $this->admin->assignRole('admin');
        $u = User::create(['name' => 'Raja', 'email' => 'r@test.id', 'password' => bcrypt('x'), 'status' => 'approved', 'is_verified' => 1]);
        $u->assignRole('relawan');
        $this->relawan = Relawan::create(['user_id' => $u->id, 'avatar' => 'assets/default/avatar.png']);
        $this->desa = Desa::create(['nama_desa' => 'Sawarna', 'alamat_desa' => 'Kec. Bayah']);
        $this->sdn = Lokasi::create(['desa_id' => $this->desa->id, 'relawan_id' => $this->relawan->id, 'nama_lokasi' => 'SDN 1 Sawarna',
            'alamat_lokasi' => 'Jl. Sawarna', 'luas_lokasi' => 350, 'kapasitas_pengungsi' => 100, 'gambar_lokasi' => 'x.jpg',
            'latitude' => '-6.98120', 'longitude' => '106.30210']);
    }

    /** File seperti export RUinRISK: judul di baris 1-2, header di baris 3. */
    private function file(array $baris): UploadedFile
    {
        $s = new Spreadsheet();
        $sh = $s->getActiveSheet()->setTitle('Data TEA');
        $sh->setCellValue('A1', 'DATA TEMPAT EVAKUASI AKHIR (TEA) RUINRISK');
        $sh->setCellValue('A2', 'Dicetak pada: 06/11/2026 19:59');
        $sh->fromArray([self::JUDUL, ...array_map(fn ($b, $i) => array_merge([$i + 1], $b), $baris, array_keys($baris))], null, 'A3', true);
        $path = tempnam(sys_get_temp_dir(), 'ruin') . '.xlsx';
        (new Xlsx($s))->save($path);

        return new UploadedFile($path, 'data_tea_ruinrisk.xlsx', null, null, true);
    }

    private function barisData(): array
    {
        return [
            // 0. contoh asli dari file RUinRISK (titik di Tangerang Selatan -> di luar Kab. Lebak)
            ['USL-98D304DF', 'kos orang', 5, -6.26562325412047, 106.605610130643, 9, 'Rumah', 'cctv 24 jam', 'kamar bau dikit', '', 'Aplikasi RUinRISK', 'approved'],
            // 1. bukit evakuasi baru di Lebak
            ['USL-BUKIT001', 'Bukit Evakuasi Pasir Gombong', 120, -6.9650, 106.2800, 45.5, 'Bukit', '', '', '', 'Aplikasi RUinRISK', 'approved'],
            // 2. ±30 m dari SDN 1 Sawarna (tempat yang sama, nama berbeda)
            ['USL-SEKOLAH1', 'Sekolah Dasar Sawarna', 80, -6.98140, 106.30230, 12, 'Sekolah', '', '', '', 'Aplikasi RUinRISK', 'approved'],
            // 3. data yang asalnya dari LOGEVA -> dilewati
            ['USL-LOGEVA01', 'Balai Desa Bayah', 60, -6.9300, 106.2500, 20, '', '', '', '', 'LOGEVA', 'approved'],
            // 4. belum disetujui di RUinRISK -> dilewati
            ['USL-PENDING1', 'Masjid Al-Huda', 50, -6.9400, 106.2600, 15, '', '', '', '', 'Aplikasi RUinRISK', 'pending'],
            // 5. koordinat tertukar -> error
            ['USL-SALAH001', 'Lapangan Desa', 30, 106.2600, -6.9400, 10, '', '', '', '', 'Aplikasi RUinRISK', 'approved'],
        ];
    }

    public function test_pratinjau_menerapkan_semua_pengaman(): void
    {
        $svc = app(ImportRuinriskService::class);
        $hasil = $svc->periksa($svc->baca($this->file($this->barisData()))['rows']);

        $this->assertSame(['tambah', 'tambah', 'tambah', 'lewati', 'lewati', 'error'], array_column($hasil, 'status'));

        $this->assertStringContainsString('di luar wilayah Kabupaten Lebak', implode(' ', $hasil[0]['peringatan']));
        $this->assertFalse($hasil[0]['dekat']);
        $this->assertSame([], $hasil[1]['peringatan']);
        $this->assertTrue($hasil[2]['dekat']);
        $this->assertMatchesRegularExpression('/±\d+ m dari posko "SDN 1 Sawarna"/', implode(' ', $hasil[2]['peringatan']));
        $this->assertStringContainsString('LOGEVA', $hasil[3]['alasan']);
        $this->assertStringContainsString('pending', $hasil[4]['alasan']);
        $this->assertNotEmpty($hasil[5]['error']);
        $this->assertSame(9.0, $hasil[0]['data']['ketinggian']);
    }

    public function test_simpan_lewat_halaman_lalu_import_ulang_tidak_dobel(): void
    {
        $this->actingAs($this->admin)->post(route('admin.lokasi.ruinrisk.preview'), ['file' => $this->file($this->barisData())])
            ->assertRedirect(route('admin.lokasi.ruinrisk.index'));
        $this->actingAs($this->admin)->get(route('admin.lokasi.ruinrisk.index'))
            ->assertOk()->assertSee('Bukit Evakuasi Pasir Gombong')->assertSee('SDN 1 Sawarna')
            ->assertSee('name="pilihan[1][ambil]"', false)->assertDontSee('name="pilihan[3][ambil]"', false); // baris "lewati" tidak bisa dicentang

        // Tidak ada yang dicentang -> ditolak, tidak ada yang tersimpan
        $this->actingAs($this->admin)->post(route('admin.lokasi.ruinrisk.confirm'), ['pilihan' => [
            1 => ['desa_id' => $this->desa->id, 'relawan_id' => $this->relawan->id],
        ]])->assertSessionHasErrors('pilihan');

        // Desa & relawan wajib untuk posko baru yang ditambahkan -> tidak ada yang tersimpan
        $this->actingAs($this->admin)->post(route('admin.lokasi.ruinrisk.confirm'), ['pilihan' => [
            1 => ['ambil' => 1, 'desa_id' => $this->desa->id, 'relawan_id' => ''],
        ]])->assertSessionHasErrors('pilihan');
        $this->assertSame(1, Lokasi::count());

        $pilih = ['desa_id' => $this->desa->id, 'relawan_id' => $this->relawan->id];
        $this->actingAs($this->admin)->post(route('admin.lokasi.ruinrisk.confirm'), ['pilihan' => [
            0 => ['ambil' => 1] + $pilih,
            1 => ['ambil' => 1] + $pilih,
            2 => $pilih,                             // tidak dicentang: tempat yang sama dengan SDN 1 Sawarna
        ]])->assertRedirect(route('admin.lokasi.index'));

        $this->assertSame(3, Lokasi::count());
        $bukit = Lokasi::where('id_sumber', 'USL-BUKIT001')->firstOrFail();
        $this->assertSame([ImportRuinriskService::SUMBER, 120, 420.0, 45.5], [$bukit->sumber_data, (int) $bukit->kapasitas_pengungsi, (float) $bukit->luas_lokasi, (float) $bukit->ketinggian_mdpl]);
        $this->assertSame('approved', StatusLokasi::where('lokasi_id', $bukit->id)->value('status'));
        $this->assertNotNull($bukit->sphereLokasi);
        $this->assertSame($this->relawan->id, $bukit->relawan_id);
        $this->assertNull($bukit->kode); // kode tetap khusus UUID aplikasi offline

        // Import ulang file yang sama: tidak ada yang dobel
        $svc = app(ImportRuinriskService::class);
        $ulang = $svc->periksa($svc->baca($this->file($this->barisData()))['rows']);
        $this->assertSame(['sama', 'sama'], [$ulang[0]['status'], $ulang[1]['status']]);
        $this->assertSame([], $ulang[1]['peringatan']); // tidak memperingatkan "dekat dengan dirinya sendiri"

        // Data berubah di RUinRISK: posko yang sama diperbarui (desa & relawan tetap)
        $data = $this->barisData();
        $data[1][2] = 150;
        $data[1][5] = 50;
        $ubah = $svc->periksa($svc->baca($this->file($data))['rows']);
        $this->assertSame('perbarui', $ubah[1]['status']);

        // Pembaruan yang TIDAK dicentang tidak diambil: posko tetap seperti sebelumnya
        $hitung = $svc->simpan($ubah, [], $this->admin);
        $this->assertSame(0, $hitung['perbarui']);
        $this->assertSame(120, (int) $bukit->fresh()->kapasitas_pengungsi);

        $svc->simpan($ubah, [1 => ['ambil' => '1']], $this->admin);
        $this->assertSame(3, Lokasi::count());
        $bukit->refresh();
        $this->assertSame([150, 525.0, 50.0, $this->desa->id], [(int) $bukit->kapasitas_pengungsi, (float) $bukit->luas_lokasi, (float) $bukit->ketinggian_mdpl, $bukit->desa_id]);

        // Ketinggian tampil di detail posko & ikut di export Excel
        $this->actingAs($this->admin)->get(route('admin.lokasi.show', $bukit->id))->assertOk()->assertSee('USL-BUKIT001');
        $this->actingAs($this->admin)->get(route('admin.lokasi.export'))->assertOk();
    }

    public function test_file_bukan_dari_ruinrisk_ditolak(): void
    {
        $s = new Spreadsheet();
        $s->getActiveSheet()->fromArray([['Nama Lokasi', 'Desa'], ['Posko A', 'Sawarna']]);
        $path = tempnam(sys_get_temp_dir(), 'ruin') . '.xlsx';
        (new Xlsx($s))->save($path);

        $this->actingAs($this->admin)->from(route('admin.lokasi.ruinrisk.index'))
            ->post(route('admin.lokasi.ruinrisk.preview'), ['file' => new UploadedFile($path, 'lain.xlsx', null, null, true)])
            ->assertSessionHasErrors('file');
        $this->assertSame(1, Lokasi::count());
    }
}
