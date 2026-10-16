<?php

namespace App\Http\Controllers\Admin\Import;

use App\Exports\RelawanExport;
use App\Exports\Template\ImportTemplateExport;
use App\Imports\Support\NilaiImport as N;
use App\Models\Relawan;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Import & Export Data Relawan.
 * Dicocokkan berdasarkan EMAIL.
 *  - Relawan baru  : dibuat akun user + data relawan, password default, status langsung APPROVED
 *                    (karena yang meng-import adalah admin), foto memakai gambar default.
 *  - Relawan lama  : hanya nama yang diperbarui (password & status tidak disentuh).
 */
class RelawanImportController extends BaseImportController
{
    /** Password awal relawan hasil import. Bisa diubah lewat .env: IMPORT_RELAWAN_PASSWORD=... */
    public static function passwordDefault(): string
    {
        return env('IMPORT_RELAWAN_PASSWORD', 'relawan123');
    }

    public const AVATAR_DEFAULT = 'assets/default/avatar.png';
    public const ROLE_RELAWAN   = 'relawan';

    protected function nama(): string    { return 'relawan'; }
    protected function judul(): string   { return 'Data Relawan'; }
    protected function satuan(): string  { return 'relawan'; }

    protected function kolomFile(): array
    {
        return [
            'name'  => ['nama', 'nama lengkap', 'nama relawan', 'nama lengkap sesuai ktp', 'name'],
            'email' => ['email', 'alamat email', 'e mail', 'alamat email aktif'],
        ];
    }

    protected function kolomWajib(): array
    {
        return ['name', 'email'];
    }

    protected function kolomTampil(): array
    {
        return ['name' => 'Nama Lengkap', 'email' => 'Email'];
    }

    protected function peringatan(): array
    {
        $pesan = [
            'Relawan baru akan langsung berstatus APPROVED dengan password awal "' . self::passwordDefault() . '". Minta relawan segera mengganti password setelah login.',
        ];

        if (class_exists(\Spatie\Permission\Models\Role::class)
            && ! \Spatie\Permission\Models\Role::where('name', self::ROLE_RELAWAN)->exists()) {
            $pesan[] = 'Role "' . self::ROLE_RELAWAN . '" belum ada di sistem, sehingga relawan baru tidak akan mendapat role. Hubungi pengembang.';
        }

        return $pesan;
    }

    public function template()
    {
        return Excel::download(new ImportTemplateExport(
            'Data Relawan',
            [
                ['Nama Lengkap', 'wajib', 'Nama lengkap sesuai KTP.', 32],
                ['Email',        'wajib', 'Email aktif, dipakai untuk login. Dipakai juga untuk mencocokkan data yang sudah ada.', 36],
            ],
            [
                ['Ahmad Fauzi',    'ahmad.fauzi@contoh.com'],
                ['Siti Nurhaliza', 'siti.nurhaliza@contoh.com'],
            ],
            [
                'Relawan baru otomatis berstatus disetujui (approved) dengan password awal: ' . self::passwordDefault() . ' . Relawan dapat mengganti password setelah login.',
                'Foto profil memakai gambar default dan dapat diganti lewat tombol Edit.',
                'Jika email sudah terdaftar sebagai relawan, hanya nama yang diperbarui.',
            ]
        ), 'template_import_relawan.xlsx');
    }

    public function export()
    {
        return Excel::download(new RelawanExport, 'data_relawan.xlsx');
    }

    protected function periksa(array $rows): array
    {
        $query   = in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive(User::class))
            ? User::withTrashed()
            : User::query();
        $users   = $query->get()->keyBy(fn ($u) => N::kunci($u->email));
        $relawan = Relawan::pluck('user_id')->flip();
        $dipakai = [];
        $hasil   = [];

        foreach ($rows as $r) {
            $nama  = N::teks($r['name'] ?? null);
            $email = N::kunci($r['email'] ?? null);
            $error = [];

            if (! $nama) {
                $error[] = 'Nama kosong';
            } elseif (mb_strlen($nama) > 255) {
                $error[] = 'Nama terlalu panjang';
            }

            if ($email === '') {
                $error[] = 'Email kosong';
            } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error[] = 'Format email tidak valid';
            } elseif (isset($dipakai[$email])) {
                $error[] = "Email sama dengan baris {$dipakai[$email]}";
            } else {
                $dipakai[$email] = $r['_row'];
            }

            $user = $email !== '' ? $users->get($email) : null;

            if ($user && ! $error) {
                if (method_exists($user, 'trashed') && $user->trashed()) {
                    $error[] = 'Email ini milik akun yang sudah dihapus';
                } elseif (! $relawan->has($user->id)) {
                    $error[] = 'Email sudah dipakai akun lain yang bukan relawan';
                }
            }

            $ubah   = ($user && ! $error && $user->name !== $nama) ? ['name' => $user->name] : [];
            $status = $error ? 'error' : (! $user ? 'tambah' : ($ubah ? 'perbarui' : 'sama'));

            $hasil[] = [
                'row'    => $r['_row'],
                'status' => $status,
                'error'  => $error,
                'data'   => ['name' => $nama, 'email' => $email],
                'lama'   => $ubah,
                'simpan' => $error ? null : ['user_id' => $user?->id, 'name' => $nama, 'email' => $email],
            ];
        }

        return $hasil;
    }

    protected function simpan(array $data): array
    {
        $tambah = $perbarui = 0;
        $password = Hash::make(self::passwordDefault());
        $adaRole  = class_exists(\Spatie\Permission\Models\Role::class)
            && \Spatie\Permission\Models\Role::where('name', self::ROLE_RELAWAN)->exists();

        foreach ($data as $row) {
            if ($row['user_id'] && ($user = User::find($row['user_id']))) {
                $user->forceFill(['name' => $row['name']])->save();
                $perbarui++;
                continue;
            }

            $user = new User;
            $user->forceFill([
                'name'        => $row['name'],
                'email'       => $row['email'],
                'password'    => $password,
                'status'      => 'approved',
                'is_verified' => true,
                'approved_at' => now(),
            ])->save();

            (new Relawan)->forceFill([
                'user_id' => $user->id,
                'avatar'  => self::AVATAR_DEFAULT,
            ])->save();

            if ($adaRole && method_exists($user, 'assignRole')) {
                $user->assignRole(self::ROLE_RELAWAN);
            }

            $tambah++;
        }

        return compact('tambah', 'perbarui');
    }
}
