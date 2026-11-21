{{--
    Halaman 403 (tidak berhak), contoh: relawan membuka pengungsi / logistik posko yang bukan tanggung jawabnya.
    Sengaja berdiri sendiri (tidak memakai layout dashboard) agar tetap tampil walau error terjadi di tengah proses.
--}}
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Akses Ditolak – GMLS Logeva</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-4 text-slate-800 antialiased">
    <main class="w-full max-w-md bg-white rounded-3xl border border-slate-200 shadow-sm p-8 text-center">
        <img src="{{ asset('assets/logo/logo-logeva.png') }}" alt="LOGEVA" class="h-10 mx-auto mb-6">

        <div class="w-16 h-16 mx-auto rounded-2xl bg-red-50 text-red-600 flex items-center justify-center text-2xl mb-4">
            <i class="fas fa-lock"></i>
        </div>

        @php
            $pesan = $exception->getMessage();
            // Pesan bawaan (Laravel / spatie permission) berbahasa Inggris -> pesan umum berbahasa Indonesia
            if ($pesan === '' || $pesan === 'Forbidden' || str_starts_with($pesan, 'User ') || str_starts_with($pesan, 'This action')) {
                $pesan = 'Anda tidak berhak membuka halaman ini.';
            }
            $halamanPosko = request()->is('lokasi/*', 'pengungsi*', 'logistik*', 'stok-masuk*', 'donasi/*/konfirmasi', 'donasi/*/tolak');
        @endphp
        <h1 class="text-xl font-black text-slate-900">Akses ditolak</h1>
        <p class="mt-2 text-sm text-slate-600">{{ $pesan }}</p>
        @if ($halamanPosko)
            <p class="mt-2 text-xs text-slate-400">
                Data pengungsi dan logistik posko hanya dapat dikelola oleh relawan penanggung jawab posko tersebut dan admin.
                Jika seharusnya Anda bertugas di posko ini, hubungi admin GMLS agar Anda ditetapkan sebagai penanggung jawab.
            </p>
        @endif

        <div class="mt-6 flex flex-col sm:flex-row gap-2 justify-center">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('lokasi.index') }}"
               class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                <i class="fas fa-arrow-left text-xs"></i> Kembali
            </a>
            <a href="{{ route('lokasi.index') }}"
               class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-sm font-semibold text-white transition">
                <i class="fas fa-campground text-xs"></i> Manajemen Posko
            </a>
        </div>
    </main>
</body>
</html>
