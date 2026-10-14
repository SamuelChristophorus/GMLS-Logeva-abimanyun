@php
    $user     = Auth::user();
    $isAdmin  = $user && (($user->role ?? null) === 'admin');
    $totalTea = \App\Models\Lokasi::count();

    // Class menu: merah kalau aktif, abu-abu kalau tidak
    $menu = function ($active) {
        return 'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition '
            . ($active ? 'bg-red-600 text-white shadow-md shadow-red-900/30'
                       : 'text-slate-300 hover:bg-white/10 hover:text-white');
    };
@endphp

<div class="h-full flex flex-col bg-slate-900 text-slate-300 border-r border-slate-800">

    <div class="flex-1 overflow-y-auto px-4 py-6 space-y-1">

        {{-- ===== Navigasi utama ===== --}}
        <p class="px-2 text-[11px] font-bold uppercase tracking-wider text-slate-500">Navigasi</p>
        <p class="px-2 pb-3 text-xs text-slate-500">Menu utama dashboard</p>

        <a href="{{ route('home') }}" class="{{ $menu(request()->routeIs('home')) }}">
            <i class="fas fa-map-location-dot w-5 text-center"></i> Monitoring Lokasi
        </a>

        <a href="{{ route('lokasi.index') }}" class="{{ $menu(request()->routeIs('lokasi.index', 'lokasi.show', 'pengungsi.*')) }}">
            <i class="fas fa-users w-5 text-center"></i> Data Pengungsi
        </a>

        <a href="{{ route('lokasi.index') }}" class="{{ $menu(request()->routeIs('logistik.*')) }}">
            <i class="fas fa-boxes-stacked w-5 text-center"></i> Manajemen Logistik
        </a>

        <a href="{{ route('home') }}#statistik" class="{{ $menu(false) }}">
            <i class="fas fa-chart-column w-5 text-center"></i> Statistik Laporan
        </a>

        {{-- ===== Akun ===== --}}
        <p class="px-2 pt-6 pb-2 text-[11px] font-bold uppercase tracking-wider text-slate-500">Akun</p>

        @auth
            <a href="{{ route('lokasi.mylokasi') }}" class="{{ $menu(request()->routeIs('lokasi.mylokasi')) }}">
                <i class="fas fa-clipboard-list w-5 text-center"></i> Laporanmu
            </a>

            <a href="{{ route('lokasi.create') }}" class="{{ $menu(request()->routeIs('lokasi.create')) }}">
                <i class="fas fa-location-dot w-5 text-center"></i> Tambah Lokasi
            </a>

            @if ($isAdmin)
                <a href="{{ route('admin.dashboard') }}" class="{{ $menu(false) }}">
                    <i class="fas fa-gauge w-5 text-center"></i> Panel Admin
                </a>
            @endif
        @else
            <a href="{{ route('login') }}" class="{{ $menu(request()->routeIs('login')) }}">
                <i class="fas fa-right-to-bracket w-5 text-center"></i> Masuk
            </a>

            <a href="{{ route('register') }}" class="{{ $menu(request()->routeIs('register')) }}">
                <i class="fas fa-user-plus w-5 text-center"></i> Daftar
            </a>
        @endauth
    </div>

    {{-- ===== Kotak status (bawah) ===== --}}
    <div class="m-4 rounded-2xl bg-white/5 border border-white/10 p-4 text-sm">
        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">Status</p>
        <p class="flex items-center gap-2 text-white">
            <span class="w-2 h-2 rounded-full bg-green-500"></span> Sistem aktif
        </p>
        <p class="mt-1 text-slate-400">{{ $totalTea }} TEA terhubung</p>
    </div>
</div>