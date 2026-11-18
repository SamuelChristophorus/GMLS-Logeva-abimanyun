<?php

namespace Tests\Feature;

use App\Models\Relawan;
use App\Models\User;
use App\Support\LamaWaktu;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Data Relawan menampilkan sudah berapa lama akun relawan dibuat. */
class LamaAkunRelawanTest extends TestCase
{
    use RefreshDatabase;

    public function test_teks_lama_waktu(): void
    {
        $now = Carbon::parse('2026-11-10 09:00', 'Asia/Jakarta');
        $cek = fn (string $t) => LamaWaktu::sejak(Carbon::parse($t, 'Asia/Jakarta'), $now);

        $this->assertSame('hari ini', $cek('2026-11-10 07:00'));
        $this->assertSame('kemarin', $cek('2026-11-09 23:00'));
        $this->assertSame('12 hari lalu', $cek('2026-10-29 10:00'));
        $this->assertSame('3 bulan lalu', $cek('2026-08-01 10:00'));
        $this->assertSame('1 tahun lalu', $cek('2025-11-01 10:00'));
        $this->assertSame('2 tahun 3 bulan lalu', $cek('2024-08-05 10:00'));
        $this->assertSame('-', LamaWaktu::sejak(null));
    }

    public function test_daftar_dan_detail_relawan_menampilkan_lama_akun(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::create(['name' => 'Admin', 'email' => 'a@t.id', 'password' => bcrypt('x12345678'),
            'status' => 'approved', 'is_verified' => 1]);
        $admin->assignRole('admin');

        $this->travelTo(Carbon::parse('2026-10-01 10:00', 'Asia/Jakarta'));
        $u = User::create(['name' => 'Rina Marlina', 'email' => 'rina@t.id', 'password' => bcrypt('x12345678'),
            'status' => 'approved', 'is_verified' => 1]);
        $u->assignRole('relawan');
        $relawan = Relawan::create(['user_id' => $u->id, 'avatar' => 'assets/default/avatar.png']);

        $this->travelTo(Carbon::parse('2026-11-10 09:00', 'Asia/Jakarta'));
        $this->actingAs($admin)->get(route('admin.relawan.index'))
            ->assertOk()->assertSee('Akun dibuat 1 bulan lalu');
        $this->actingAs($admin)->get(route('admin.relawan.show', $relawan->id))
            ->assertOk()->assertSee('Akun Dibuat')->assertSee('01-10-2026 10:00')->assertSee('1 bulan lalu');
    }
}
