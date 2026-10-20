@extends('layouts.dashboard')

@section('title', 'Manajemen Posko')

@section('content')
@php
    $pakaiJarak = request()->filled('latitude') && request()->filled('longitude');
    $warnaOps = [
        'green' => 'bg-green-50 text-green-700 border-green-200',
        'amber' => 'bg-amber-50 text-amber-700 border-amber-200',
        'slate' => 'bg-slate-100 text-slate-600 border-slate-200',
    ];
@endphp

<div class="flex flex-col gap-6">

    {{-- ===== Breadcrumb ===== --}}
    <nav aria-label="Breadcrumb">
        <ol class="flex items-center gap-2 text-xs font-medium text-slate-400">
            <li>
                <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 hover:text-slate-600">
                    <i class="fas fa-house text-[10px]"></i> Beranda
                </a>
            </li>
            <li><i class="fas fa-chevron-right text-[9px]"></i></li>
            <li class="text-slate-600 font-semibold">Manajemen Posko</li>
        </ol>
    </nav>

    {{-- ===== Header + ringkasan ===== --}}
    <header class="bg-white rounded-3xl border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] p-6 md:p-8">
        <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-50 border border-blue-100 text-blue-600 text-xs font-bold tracking-wide uppercase mb-3">
                    <i class="fas fa-campground opacity-70"></i> Manajemen Posko
                </div>
                <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 tracking-tight">Daftar Posko Pengungsian</h1>
                <p class="text-slate-500 mt-1 text-sm">Pantau status, kapasitas, pengungsi, dan logistik setiap posko.</p>
            </div>

            @auth
                <a href="{{ route('lokasi.create') }}"
                   class="inline-flex items-center justify-center gap-2 bg-gradient-to-tr from-emerald-600 to-teal-500 hover:from-emerald-700 hover:to-teal-600 text-white font-bold px-5 py-3 rounded-xl shadow-lg shadow-emerald-500/30 transition-all hover:-translate-y-0.5 self-start lg:self-auto">
                    <i class="fas fa-plus-circle text-sm"></i> Tambah Posko
                </a>
            @endauth
        </div>

        <dl class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-6">
            @foreach ([
                ['Total Posko',      $ringkasan['total'],      'fa-campground',  'text-blue-600 bg-blue-50'],
                ['Sedang Beroperasi',$ringkasan['beroperasi'], 'fa-circle-play', 'text-green-600 bg-green-50'],
                ['Total Kapasitas',  number_format($ringkasan['kapasitas'], 0, ',', '.') . ' jiwa', 'fa-users', 'text-indigo-600 bg-indigo-50'],
                ['Pengungsi Terdata',number_format($ringkasan['pengungsi'], 0, ',', '.') . ' jiwa', 'fa-person-shelter', 'text-amber-600 bg-amber-50'],
            ] as [$label, $nilai, $ikon, $warna])
                <div class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-slate-50/60 p-4">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 {{ $warna }}">
                        <i class="fas {{ $ikon }}"></i>
                    </div>
                    <div class="min-w-0">
                        <dt class="text-[11px] font-bold uppercase tracking-wider text-slate-400 truncate">{{ $label }}</dt>
                        <dd class="text-lg font-black text-slate-800 truncate">{{ $nilai }}</dd>
                    </div>
                </div>
            @endforeach
        </dl>
    </header>

    {{-- ===== Toolbar: cari, filter status, urut jarak ===== --}}
    <div class="flex flex-col md:flex-row md:items-center gap-3">
        <div class="relative flex-1">
            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
            <input type="search" id="cariPosko" placeholder="Cari nama posko, alamat, atau desa..."
                   class="w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
        </div>

        <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Filter status operasional">
            @foreach (['semua' => 'Semua', 'beroperasi' => 'Beroperasi', 'siaga' => 'Siaga', 'selesai' => 'Selesai'] as $key => $label)
                <button type="button" data-filter="{{ $key }}"
                        class="filter-ops px-4 py-2.5 rounded-xl text-sm font-semibold border transition-colors {{ $key === 'semua' ? 'bg-slate-800 text-white border-slate-800' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
                    {{ $label }}
                </button>
            @endforeach

            @if ($pakaiJarak)
                <a href="{{ route('lokasi.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100">
                    <i class="fas fa-xmark"></i> Hapus urutan jarak
                </a>
            @else
                <button type="button" id="btnJarak"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold border border-slate-200 bg-white text-slate-600 hover:bg-slate-50">
                    <i class="fas fa-location-crosshairs"></i> Urutkan terdekat
                </button>
            @endif
        </div>
    </div>
    <p id="pesanGps" class="hidden -mt-3 text-xs font-medium text-rose-600"></p>

    @if (request()->desa)
        <p class="-mt-2 text-sm text-slate-500">
            <i class="fas fa-filter text-indigo-400 mr-1"></i> Menampilkan posko di desa <b class="text-slate-700">{{ request()->desa }}</b>
            · <a href="{{ route('lokasi.index') }}" class="text-blue-600 hover:underline">tampilkan semua</a>
        </p>
    @endif

    {{-- ===== Grid posko ===== --}}
    @if ($lokasis->count())
        <div id="gridPosko" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            @foreach ($lokasis as $lokasi)
                @php
                    $ops       = $lokasi->labelOperasional();
                    $approved  = $lokasi->isApproved();
                    $kapasitas = (int) $lokasi->kapasitas_pengungsi;
                    $persen    = $kapasitas > 0 ? min(100, round($lokasi->pengungsi_count / $kapasitas * 100)) : 0;
                    $warnaBar  = $persen >= 90 ? 'bg-red-500' : ($persen >= 70 ? 'bg-amber-500' : 'bg-blue-600');
                @endphp

                <article class="posko-card group bg-white rounded-3xl overflow-hidden border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col relative"
                         data-ops="{{ $lokasi->status_operasional ?? 'siaga' }}"
                         data-cari="{{ Str::lower($lokasi->nama_lokasi . ' ' . $lokasi->alamat_lokasi . ' ' . ($lokasi->desa->nama_desa ?? '')) }}">

                    {{-- Gambar --}}
                    <a href="{{ route('lokasi.show', $lokasi->id) }}" class="relative block h-44 overflow-hidden bg-slate-100">
                        <div class="absolute inset-0 flex items-center justify-center text-slate-300 pointer-events-none">
                            <i class="fas fa-image text-4xl"></i>
                        </div>
                        <img src="{{ asset('storage/' . $lokasi->gambar_lokasi) }}" alt="{{ $lokasi->nama_lokasi }}" loading="lazy"
                             onerror="this.style.display='none'"
                             class="relative w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 via-transparent to-transparent"></div>

                        <span class="absolute top-3 left-3 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border text-[11px] font-bold {{ $warnaOps[$ops['warna']] }}">
                            @if ($lokasi->isBeroperasi())
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                            @endif
                            {{ $ops['label'] }}
                        </span>

                        @if (isset($lokasi->distance) && $pakaiJarak)
                            <span class="absolute top-3 right-3 bg-white/95 text-blue-700 px-2.5 py-1 rounded-full text-[11px] font-bold shadow">
                                <i class="fas fa-location-arrow mr-1 text-blue-500"></i>{{ number_format($lokasi->distance, 1, ',', '.') }} km
                            </span>
                        @endif

                        @unless ($approved)
                            <span class="absolute bottom-3 left-3 bg-amber-400 text-amber-950 px-2.5 py-1 rounded-full text-[11px] font-bold">
                                <i class="fas fa-hourglass-half mr-1"></i> Menunggu verifikasi
                            </span>
                        @endunless

                        @if (($bantuan[$lokasi->id] ?? null) === 'segera')
                            <span class="absolute bottom-3 left-3 bg-red-600 text-white px-2.5 py-1 rounded-full text-[11px] font-bold shadow">
                                <i class="fas fa-triangle-exclamation mr-1"></i> Butuh bantuan segera
                            </span>
                        @elseif (($bantuan[$lokasi->id] ?? null) === 'pantau')
                            <span class="absolute bottom-3 left-3 bg-amber-400 text-amber-950 px-2.5 py-1 rounded-full text-[11px] font-bold shadow">
                                <i class="fas fa-eye mr-1"></i> Perlu dipantau
                            </span>
                        @endif
                    </a>

                    {{-- Isi --}}
                    <div class="p-5 flex flex-col flex-1 gap-4">
                        <div>
                            <a href="{{ route('lokasi.show', $lokasi->id) }}">
                                <h3 class="text-lg font-extrabold text-slate-800 line-clamp-1 group-hover:text-blue-600 transition-colors">{{ $lokasi->nama_lokasi }}</h3>
                            </a>
                            <p class="mt-1 text-sm text-slate-500 flex items-start gap-2">
                                <i class="fas fa-map-marker-alt text-rose-400 mt-1 text-xs"></i>
                                <span class="line-clamp-2">{{ $lokasi->alamat_lokasi }}</span>
                            </p>
                        </div>

                        {{-- Keterisian --}}
                        <div>
                            <div class="flex items-baseline justify-between text-xs mb-1.5">
                                <span class="font-semibold text-slate-500">Keterisian</span>
                                <span class="text-slate-700"><b>{{ $lokasi->pengungsi_count }}</b> / {{ $kapasitas }} jiwa</span>
                            </div>
                            <div class="h-2 rounded-full bg-slate-100 overflow-hidden" role="img" aria-label="{{ $persen }} persen terisi">
                                <div class="h-full rounded-full {{ $warnaBar }}" style="width: {{ $persen }}%"></div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-xs text-slate-400 mt-auto pt-3 border-t border-slate-100">
                            <span class="inline-flex items-center gap-1.5 font-medium text-slate-500">
                                <i class="fas fa-map-signs text-indigo-400"></i> {{ $lokasi->desa->nama_desa ?? '-' }}
                            </span>
                            <span><i class="far fa-clock mr-1"></i>{{ $lokasi->created_at->timezone('Asia/Jakarta')->diffForHumans() }}</span>
                        </div>

                        {{-- Aksi --}}
                        <div class="grid {{ auth()->check() && $approved ? 'grid-cols-3' : 'grid-cols-1' }} gap-2">
                            <a href="{{ route('lokasi.show', $lokasi->id) }}"
                               class="inline-flex items-center justify-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold py-2.5 rounded-xl transition">
                                <i class="fas fa-eye text-xs"></i> Detail
                            </a>
                            @if (auth()->check() && $approved)
                                <a href="{{ route('pengungsi.index', $lokasi->id) }}"
                                   class="inline-flex items-center justify-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold py-2.5 rounded-xl transition">
                                    <i class="fas fa-users text-xs"></i> Pengungsi
                                </a>
                                <a href="{{ route('logistik.index', $lokasi->id) }}"
                                   class="inline-flex items-center justify-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold py-2.5 rounded-xl transition">
                                    <i class="fas fa-boxes-stacked text-xs"></i> Logistik
                                </a>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        {{-- Kosong setelah filter di sisi browser --}}
        <div id="kosongFilter" class="hidden flex-col items-center justify-center py-14 bg-white rounded-3xl border border-dashed border-slate-200 text-center">
            <i class="fas fa-filter-circle-xmark text-4xl text-slate-300 mb-3"></i>
            <p class="font-bold text-slate-700">Tidak ada posko yang cocok</p>
            <p class="text-sm text-slate-500">Coba ubah kata kunci atau filter status.</p>
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-16 px-4 bg-white rounded-3xl border border-dashed border-slate-200 text-center">
            <div class="w-24 h-24 bg-blue-50 text-blue-300 rounded-full flex items-center justify-center mb-5">
                <i class="fas fa-search-location text-4xl"></i>
            </div>
            <h3 class="text-xl font-bold text-slate-800 mb-1">Belum ada posko ditemukan</h3>
            <p class="text-slate-500 max-w-md mb-6 text-sm">Tidak ada posko pengungsian dengan filter saat ini.</p>
            <div class="flex flex-wrap justify-center gap-2">
                <a href="{{ route('lokasi.index') }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2.5 rounded-xl text-sm">
                    <i class="fas fa-sync-alt"></i> Reset filter
                </a>
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 bg-white hover:bg-slate-50 text-slate-600 font-semibold px-5 py-2.5 rounded-xl text-sm border border-slate-200">
                    <i class="fas fa-house"></i> Kembali ke Beranda
                </a>
            </div>
        </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const cards   = Array.from(document.querySelectorAll('.posko-card'));
    const input   = document.getElementById('cariPosko');
    const kosong  = document.getElementById('kosongFilter');
    const tombol  = document.querySelectorAll('.filter-ops');
    let filterOps = 'semua';

    function terapkan() {
        const kata = (input.value || '').trim().toLowerCase();
        let tampil = 0;
        cards.forEach(c => {
            const cocok = (filterOps === 'semua' || c.dataset.ops === filterOps)
                       && (!kata || c.dataset.cari.includes(kata));
            c.classList.toggle('hidden', !cocok);
            c.classList.toggle('flex', cocok);
            if (cocok) tampil++;
        });
        if (kosong) {
            kosong.classList.toggle('hidden', tampil > 0 || cards.length === 0);
            kosong.classList.toggle('flex', tampil === 0 && cards.length > 0);
        }
    }

    input?.addEventListener('input', terapkan);
    tombol.forEach(btn => btn.addEventListener('click', function () {
        filterOps = this.dataset.filter;
        tombol.forEach(b => {
            const aktif = b === this;
            b.classList.toggle('bg-slate-800', aktif);
            b.classList.toggle('text-white', aktif);
            b.classList.toggle('border-slate-800', aktif);
            b.classList.toggle('bg-white', !aktif);
            b.classList.toggle('text-slate-600', !aktif);
            b.classList.toggle('border-slate-200', !aktif);
        });
        terapkan();
    }));

    // Urutkan berdasarkan jarak: hanya saat diminta, tanpa membatasi radius
    const btnJarak = document.getElementById('btnJarak');
    const pesan    = document.getElementById('pesanGps');
    btnJarak?.addEventListener('click', function () {
        const tampilkanPesan = teks => { pesan.textContent = teks; pesan.classList.remove('hidden'); };
        if (!navigator.geolocation) return tampilkanPesan('Browser tidak mendukung GPS.');

        btnJarak.disabled = true;
        btnJarak.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mencari lokasi...';

        navigator.geolocation.getCurrentPosition(
            pos => {
                const url = new URL(window.location.href);
                url.searchParams.set('latitude', pos.coords.latitude);
                url.searchParams.set('longitude', pos.coords.longitude);
                window.location.href = url.toString();
            },
            () => {
                btnJarak.disabled = false;
                btnJarak.innerHTML = '<i class="fas fa-location-crosshairs"></i> Urutkan terdekat';
                tampilkanPesan('Akses lokasi ditolak. Izinkan akses lokasi di browser untuk mengurutkan posko terdekat.');
            },
            { enableHighAccuracy: true, timeout: 8000 }
        );
    });
});
</script>
@endsection
