@php
    $user    = Auth::user();
    // Role memakai Spatie (sama seperti middleware role:admin di routes)
    $isAdmin = $user && method_exists($user, 'hasRole') && $user->hasRole('admin');
@endphp

<header class="sticky top-0 z-[1100] h-16 bg-white border-b border-slate-200 flex items-center gap-3 px-4 md:px-6">

    {{-- Tombol buka sidebar (HP) --}}
    <button @click="sidebarOpen = !sidebarOpen"
            class="md:hidden w-10 h-10 rounded-xl border border-slate-200 text-slate-500 hover:text-red-600 hover:border-red-200 flex items-center justify-center">
        <i class="fas fa-bars"></i>
    </button>

    {{-- Logo Logeva --}}
    <a href="{{ route('home') }}" class="flex items-center gap-3 min-w-0">
        <img src="{{ asset('assets/logo/logo-logeva.png') }}" alt="Logo Logeva - Gugus Mitigasi Lebak Selatan"
             class="h-10 sm:h-11 w-auto object-contain shrink-0">
        <span class="hidden lg:block h-8 w-px bg-slate-200"></span>
        <p class="hidden lg:block text-xs text-slate-500 leading-tight">Dashboard<br>monitoring TEA</p>
    </a>

    {{-- Kanan: cari, notifikasi, profil --}}
    <div class="ml-auto flex items-center gap-2 sm:gap-3">

        {{-- Pencarian lokasi (tekan Enter) --}}
        <label class="hidden md:flex items-center gap-2 px-4 py-2 rounded-full bg-slate-100 border border-slate-200 focus-within:border-red-300">
            <i class="fas fa-magnifying-glass text-slate-400 text-sm"></i>
            <input type="text" placeholder="Cari lokasi / laporan"
                   class="bg-transparent outline-none text-sm w-48"
                   @keydown.enter="window.dispatchEvent(new CustomEvent('tea-search', { detail: $event.target.value }))">
        </label>

        {{-- Notifikasi --}}
        <button class="w-10 h-10 rounded-full border border-slate-200 text-slate-500 hover:text-red-600 hover:border-red-200 flex items-center justify-center"
                title="Notifikasi">
            <i class="far fa-bell"></i>
        </button>

        @auth
            {{-- Dropdown profil --}}
            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <button @click="open = !open"
                        class="flex items-center gap-2 border border-slate-200 hover:bg-slate-50 rounded-full py-1 pl-1 pr-3 transition">
                    <div class="w-8 h-8 rounded-full bg-red-600 text-white flex items-center justify-center text-xs font-bold">
                        {{ strtoupper(substr($user->name ?? $user->email ?? 'U', 0, 2)) }}
                    </div>
                    <div class="hidden lg:block text-left leading-tight">
                        <p class="text-sm font-bold text-slate-800 max-w-[140px] truncate">{{ $user->name ?? $user->email }}</p>
                        <p class="text-[11px] text-slate-500">{{ $isAdmin ? 'Admin' : 'Relawan' }}</p>
                    </div>
                    <i class="fas fa-chevron-down text-[10px] text-slate-400 transition-transform" :class="{ 'rotate-180': open }"></i>
                </button>

                <div x-show="open" x-cloak x-transition
                     class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-slate-100 py-2 z-50">
                    <div class="px-4 py-2 border-b border-slate-100">
                        <p class="text-xs text-slate-400">Masuk sebagai</p>
                        <p class="text-sm font-bold text-slate-800 truncate">{{ $user->email }}</p>
                    </div>

                    @if ($isAdmin)
                        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm font-bold text-blue-600 hover:bg-blue-50">
                            <i class="fas fa-gauge w-5"></i> Panel Admin
                        </a>
                    @endif

                    <a href="{{ route('profile') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50">
                        <i class="far fa-user w-5 text-slate-400"></i> Profil
                    </a>


                    <form action="{{ route('logout') }}" method="POST" class="border-t border-slate-100 mt-1 pt-1">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-2 px-4 py-2.5 text-sm font-bold text-rose-600 hover:bg-rose-50">
                            <i class="fas fa-right-from-bracket w-5"></i> Keluar
                        </button>
                    </form>
                </div>
            </div>
        @else
            <a href="{{ route('login') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-red-600 hover:bg-red-700 text-white text-sm font-semibold">
                <i class="fas fa-right-to-bracket"></i> Masuk
            </a>
        @endauth
    </div>
</header>