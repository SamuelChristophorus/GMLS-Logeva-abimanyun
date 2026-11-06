{{-- Tab menu "Pusat Logistik": dipakai halaman Gudang Pusat & Stok Semua Posko --}}
<nav class="inline-flex flex-wrap rounded-2xl bg-white border border-slate-200 p-1 text-sm font-bold shadow-sm self-start" aria-label="Pusat Logistik">
    @foreach ([
        ['admin.gudang.index',      'fa-warehouse',  'Gudang Pusat'],
        ['admin.gudang.stok-posko', 'fa-th',         'Stok Semua Posko'],
    ] as [$rute, $ikon, $label])
        <a href="{{ route($rute) }}" @if (request()->routeIs($rute)) aria-current="page" @endif
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl transition {{ request()->routeIs($rute) ? 'bg-red-600 text-white shadow' : 'text-slate-600 hover:bg-slate-50' }}">
            <i class="fas {{ $ikon }}"></i> {{ $label }}
        </a>
    @endforeach
</nav>
