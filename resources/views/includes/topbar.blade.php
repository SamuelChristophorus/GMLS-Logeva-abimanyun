<nav class="relative z-50 flex items-center justify-between bg-white/70 backdrop-blur-xl border-b border-white/50 shadow-[0_4px_20px_rgba(0,0,0,0.02)] px-6 py-3">
    <!-- Left: Mobile Toggle & Breadcrumb -->
    <div class="flex items-center gap-4">
        <!-- Alpine.js click handler added to toggle sidebarOpen -->
        <button @click="sidebarOpen = !sidebarOpen" class="md:hidden w-10 h-10 rounded-xl bg-white border border-slate-100 shadow-sm text-slate-500 hover:text-blue-600 hover:border-blue-200 hover:bg-blue-50 transition-all flex items-center justify-center focus:outline-none">
            <i class="fa fa-bars"></i>
        </button>
        
        <div class="hidden md:flex items-center text-sm font-medium text-slate-400">
            <i class="fas fa-home mr-2 text-slate-300"></i>
            <span class="text-slate-500">Panel Admin</span>
            <i class="fas fa-chevron-right text-[10px] mx-2 text-slate-300"></i>
            <span class="text-slate-800 font-bold">@yield('title', 'Dashboard')</span>
        </div>
    </div>

    <!-- Right: User Dropdown -->
    <div class="ml-auto flex items-center gap-2 sm:gap-4">

        {{-- Notifikasi (permintaan mulai / tutup operasi dari relawan) --}}
        @include('includes.notifikasi')

        <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>

        <!-- User Dropdown via Alpine.js -->
        <div class="relative" x-data="{ open: false }" @click.away="open = false">
            <button @click="open = !open" class="flex items-center gap-2 focus:outline-none group bg-white hover:bg-slate-50 border border-slate-100 shadow-sm hover:border-slate-200 py-1 pl-1 pr-3 rounded-full transition-all">
                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-white flex items-center justify-center text-sm font-bold shadow-sm group-hover:shadow-md transition-all">
                    {{ strtoupper(substr(Auth::user()->email ?? 'A', 0, 1)) }}
                </div>
                <span class="hidden lg:block text-slate-700 text-sm font-bold max-w-[150px] truncate">{{ Auth::user()->email }}</span>
                <i class="fas fa-chevron-down text-slate-400 text-[10px] transition-transform duration-200 ml-1" :class="{ 'rotate-180': open }"></i>
            </button>

            <!-- Dropdown Menu -->
            <div x-show="open" 
                 x-transition:enter="transition ease-out duration-200 delay-75"
                 x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                 class="absolute right-0 mt-3 w-56 bg-white/95 backdrop-blur-xl rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 py-2 z-50 origin-top-right overflow-hidden" x-cloak>
                
                <div class="px-4 py-3 border-b border-slate-50">
                    <p class="text-xs text-slate-400 font-medium">Sesi Login Akun</p>
                    <p class="text-sm font-bold text-slate-800 truncate mt-0.5">{{ Auth::user()->email }}</p>
                </div>
                
                <div class="py-1">
                    <a href="{{ route('home') }}" class="flex items-center px-4 py-2.5 text-sm text-slate-600 font-medium hover:bg-slate-50 hover:text-blue-600 transition-colors">
                        <i class="fas fa-home fa-fw w-5 text-slate-400 mr-2"></i> Aplikasi Publik
                    </a>
                </div>

                <div class="border-t border-slate-50 my-1"></div>
                
                <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" 
                   class="flex items-center px-4 py-2.5 text-sm font-bold text-rose-600 hover:bg-rose-50 transition-colors">
                    <i class="fas fa-sign-out-alt fa-fw w-5 mr-2 text-rose-400"></i> Keluar
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                    @csrf
                </form>
            </div>
        </div>
    </div>
</nav>