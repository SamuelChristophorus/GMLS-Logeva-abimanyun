<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\Lokasi;
use App\Models\Relawan;
use App\Models\StatusLokasi;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Penugasan posko: posko WAJIB punya satu relawan penanggung jawab, satu
 * relawan hanya di satu posko. Hanya penanggung jawab (dan admin) yang bisa
 * kelola pengungsi & logistik; relawan lain hanya melihat detail posko.
 */
class PenugasanPoskoRelawanTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private function relawan(string $nama): Relawan
    {
        $u = User::create(['name' => $nama, 'email' => strtolower($nama) . '@t.id',
            'password' => bcrypt('x12345678'), 'status' => 'approved', 'is_verified' => 1]);
        $u->assignRole('relawan');
        return Relawan::create(['user_id' => $u->id, 'avatar' => 'assets/default/avatar.png']);
    }

    private function posko(string $nama, Relawan $pj): Lokasi
    {
        $l = Lokasi::create([
            'desa_id' => Desa::first()->id, 'relawan_id' => $pj->id, 'nama_lokasi' => $nama,
            'alamat_lokasi' => 'Jl. 1', 'luas_lokasi' => 300, 'kapasitas_pengungsi' => 100,
            'gambar_lokasi' => 'x.jpg', 'latitude' => '-6.9', 'longitude' => '106.1',
        ]);
        StatusLokasi::create(['lokasi_id' => $l->id, 'status' => 'approved', 'catatan' => 'Disetujui']);
        return $l;
    }

    private function simpanRelawan(Relawan $r, $lokasiId)
    {
        return $this->actingAs($this->admin)->put(route('admin.relawan.update', $r->id), [
            'name' => $r->user->name, 'email' => $r->user->email, 'lokasi_id' => $lokasiId,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Desa::create(['nama_desa' => 'Panggarangan', 'alamat_desa' => 'Kec. Panggarangan']);
        $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@t.id',
            'password' => bcrypt('x12345678'), 'status' => 'approved', 'is_verified' => 1]);
        $this->admin->assignRole('admin');
    }

    public function test_pindah_posko_bertukar_dengan_penanggung_jawab_lama(): void
    {
        $andi = $this->relawan('Andi');
        $budi = $this->relawan('Budi');
        $satu = $this->posko('Posko Satu', $andi);
        $dua = $this->posko('Posko Dua', $budi);

        $this->actingAs($this->admin)->get(route('admin.relawan.edit', $andi->id))
            ->assertOk()->assertSee('Posko Tugas')->assertDontSee('Belum ditugaskan');

        $this->simpanRelawan($andi, $dua->id)->assertRedirect(route('admin.relawan.index'));

        $this->assertSame($andi->id, $dua->fresh()->relawan_id);
        $this->assertSame($budi->id, $satu->fresh()->relawan_id, 'Budi bertukar ke Posko Satu');
        $this->assertSame(0, Lokasi::whereNull('relawan_id')->count(), 'tidak ada posko kosong');

        // Hak akses mengikuti penugasan baru; yang lain hanya detail.
        $this->actingAs($andi->user)->get(route('pengungsi.index', $dua->id))->assertOk();
        $this->actingAs($andi->user)->get(route('logistik.index', $dua->id))->assertOk();
        $this->actingAs($andi->user)->get(route('pengungsi.index', $satu->id))->assertForbidden();
        $this->actingAs($andi->user)->get(route('logistik.index', $satu->id))->assertForbidden();
        $this->actingAs($andi->user)->get(route('lokasi.show', $satu->id))->assertOk();
    }

    public function test_relawan_baru_menggantikan_dan_yang_lama_jadi_belum_ditugaskan(): void
    {
        $andi = $this->relawan('Andi');
        $citra = $this->relawan('Citra');           // belum ditugaskan
        $satu = $this->posko('Posko Satu', $andi);

        $this->actingAs($this->admin)->get(route('admin.relawan.edit', $citra->id))
            ->assertOk()->assertSee('Belum ditugaskan');
        $this->simpanRelawan($citra, $satu->id)->assertRedirect();

        $this->assertSame($citra->id, $satu->fresh()->relawan_id);
        $this->assertSame(0, Lokasi::where('relawan_id', $andi->id)->count());
        $this->actingAs($andi->user)->get(route('pengungsi.index', $satu->id))->assertForbidden();
    }

    public function test_penanggung_jawab_tidak_bisa_dikosongkan_atau_dihapus(): void
    {
        $andi = $this->relawan('Andi');
        $satu = $this->posko('Posko Satu', $andi);

        $this->simpanRelawan($andi, '')->assertSessionHasErrors('lokasi_id');
        $this->assertSame($andi->id, $satu->fresh()->relawan_id);

        $this->actingAs($this->admin)->delete(route('admin.relawan.destroy', $andi->id));
        $this->assertNotNull(Relawan::find($andi->id), 'relawan yang memegang posko tidak terhapus');
        $this->assertSame($andi->id, $satu->fresh()->relawan_id);
    }

    public function test_edit_posko_wajib_relawan_dan_bertukar(): void
    {
        $andi = $this->relawan('Andi');
        $budi = $this->relawan('Budi');
        $satu = $this->posko('Posko Satu', $andi);
        $dua = $this->posko('Posko Dua', $budi);

        $data = fn ($rid) => [
            'desa_id' => $satu->desa_id, 'relawan_id' => $rid, 'nama_lokasi' => 'Posko Satu',
            'alamat_lokasi' => 'Jl. 1', 'luas_lokasi' => 300, 'kapasitas_pengungsi' => 100,
            'latitude' => '-6.9', 'longitude' => '106.1',
        ];
        $this->actingAs($this->admin)->put(route('admin.lokasi.update', $satu->id), $data(''))
            ->assertSessionHasErrors('relawan_id');

        $this->actingAs($this->admin)->put(route('admin.lokasi.update', $satu->id), $data($budi->id));
        $this->assertSame($budi->id, $satu->fresh()->relawan_id);
        $this->assertSame($andi->id, $dua->fresh()->relawan_id, 'Andi bertukar ke Posko Dua');
    }
}
