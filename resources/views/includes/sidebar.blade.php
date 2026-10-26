<div class="h-full flex flex-col bg-gray-300 backdrop-blur-xl border-r border-black-400 w-64 shadow-[4px_0_24px_rgba(239,68,68,0.15)] relative z-20">
    <!-- Brand -->
    <a class="flex flex-col items-start gap-2 px-6 py-5 border-b border-slate-100/50" href="{{route('admin.dashboard')}}">
        <img src="{{ asset('assets/logo/logo-logeva.png') }}" alt="Logo Logeva - Gugus Mitigasi Lebak Selatan"
             class="w-full max-w-[200px] h-auto object-contain">
        <p class="text-[10px] font-bold text-slate-900 uppercase tracking-widest leading-none">Admin Panel</p>
    </a>

    <!-- Nav Links -->
    <div class="flex-1 overflow-y-auto py-6 px-4 space-y-1">
        
        <p class="px-2 text-xs font-bold text-slate-900 uppercase tracking-wider mb-2 mt-2">Menu Utama</p>

        <a href="{{route('admin.dashboard')}}" 
           class="flex items-center px-3 py-2.5 rounded-xl transition-all duration-200 group {{ request()->routeIs('admin.dashboard') ? 'bg-red-600 shadow-md shadow-gray-500/20 text-white' : 'text-slate-900 hover:bg-white hover:shadow-sm hover:text-blue-600' }}">
            <i class="fas fa-chart-pie w-7 text-center {{ request()->routeIs('admin.dashboard') ? 'text-white' : 'text-slate-900 group-hover:text-blue-500' }} transition-colors"></i>
            <span class="ml-2 font-semibold text-sm">Dashboard</span>
        </a>

        <p class="px-2 text-xs font-bold text-slate-900 uppercase tracking-wider mb-2 mt-6">Manajemen Data</p>

        <a href="{{route('admin.relawan.index')}}" 
           class="flex items-center px-3 py-2.5 rounded-xl transition-all duration-200 group {{ request()->is('admin/relawan*') ? 'bg-red-600 shadow-md shadow-gray-500/20 text-white' : 'text-slate-900 hover:bg-white hover:shadow-sm hover:text-blue-600' }}">
            <i class="fas fa-users w-7 text-center {{ request()->is('admin/relawan*') ? 'text-white' : 'text-slate-900 group-hover:text-blue-500' }} transition-colors"></i>
            <span class="ml-2 font-semibold text-sm">Data Relawan</span>
        </a>
        
        <a href="{{route('admin.desa.index')}}" 
           class="flex items-center px-3 py-2.5 rounded-xl transition-all duration-200 group {{ request()->is('admin/desa*') ? 'bg-red-600 shadow-md shadow-gray-500/20 text-white' : 'text-slate-900 hover:bg-white hover:shadow-sm hover:text-blue-600' }}">
            <i class="fas fa-map-signs w-7 text-center {{ request()->is('admin/desa*') ? 'text-white' : 'text-slate-900 group-hover:text-blue-500' }} transition-colors"></i>
            <span class="ml-2 font-semibold text-sm">Data Desa</span>
        </a>

        <a href="{{route('admin.lokasi.index')}}" 
           class="flex items-center px-3 py-2.5 rounded-xl transition-all duration-200 group {{ request()->is('admin/lokasi*') ? 'bg-red-600 shadow-md shadow-gray-500/20 text-white' : 'text-slate-900 hover:bg-white hover:shadow-sm hover:text-blue-600' }}">
            <i class="fas fa-campground w-7 text-center {{ request()->is('admin/lokasi*') ? 'text-white' : 'text-slate-900 group-hover:text-blue-500' }} transition-colors"></i>
            <span class="ml-2 font-semibold text-sm">Lokasi Pengungsian</span>
        </a>

        <a href="{{ route('admin.logistik.index') }}" 
            class="flex items-center px-3 py-2.5 rounded-xl transition-all duration-200 group {{ request()->is('admin/logistik*') ? 'bg-red-600 shadow-md shadow-gray-500/20 text-white' : 'text-slate-900 hover:bg-white hover:shadow-sm hover:text-blue-600' }}">
            <i class="fas fa-warehouse w-7 text-center {{ request()->is('admin/logistik*') ? 'text-white' : 'text-slate-900 group-hover:text-blue-500' }} transition-colors"></i>
            <span class="ml-2 font-semibold text-sm">Master Logistik</span>
        </a>

        <a href="{{ route('admin.paket.index') }}"
            class="flex items-center px-3 py-2.5 rounded-xl transition-all duration-200 group {{ request()->is('admin/paket-data*') ? 'bg-red-600 shadow-md shadow-gray-500/20 text-white' : 'text-slate-900 hover:bg-white hover:shadow-sm hover:text-blue-600' }}">
            <i class="fas fa-file-archive w-7 text-center {{ request()->is('admin/paket-data*') ? 'text-white' : 'text-slate-900 group-hover:text-blue-500' }} transition-colors"></i>
            <span class="ml-2 font-semibold text-sm">Import Paket Posko</span>
        </a>
    </div>

    <!-- User Mini Profile at bottom -->
    <div class="px-4 py-4 border-t border-slate-100/50">
        <div class="bg-white/50 rounded-2xl p-3 border border-white/80 shadow-sm flex items-center gap-3 hover:bg-white transition-colors">
            <div class="w-8 h-8 rounded-[10px] bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-xs font-bold shrink-0 shadow-sm">
                {{ strtoupper(substr(Auth::user()->email ?? 'A', 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-bold text-slate-800 truncate">{{ Auth::user()->name ?? 'Administrator' }}</p>
                <p class="text-[10px] text-slate-500 truncate">{{ Auth::user()->email }}</p>
            </div>
        </div>
    </div>
</div>