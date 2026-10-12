@extends('layouts.app')

@section('title', $lokasi->nama_lokasi)

@php
    $currentStatus = $lokasi->statusLokasi->last()?->status;
    $statusConfig = [
        'pending'  => ['label' => 'Pending',   'pill' => 'bg-amber-100 text-amber-700 ring-amber-200',       'dot' => 'bg-amber-400',   'icon_color' => 'text-amber-500',   'tl_bg' => 'bg-amber-50',   'tl_ring' => 'ring-amber-200'],
        'approved' => ['label' => 'Disetujui', 'pill' => 'bg-emerald-100 text-emerald-700 ring-emerald-200', 'dot' => 'bg-emerald-400', 'icon_color' => 'text-emerald-500', 'tl_bg' => 'bg-emerald-50', 'tl_ring' => 'ring-emerald-200'],
        'rejected' => ['label' => 'Ditolak',   'pill' => 'bg-red-100 text-red-700 ring-red-200',             'dot' => 'bg-red-400',     'icon_color' => 'text-red-500',     'tl_bg' => 'bg-red-50',     'tl_ring' => 'ring-red-200'],
        'done'     => ['label' => 'Selesai',   'pill' => 'bg-blue-100 text-blue-700 ring-blue-200',          'dot' => 'bg-blue-400',    'icon_color' => 'text-blue-500',    'tl_bg' => 'bg-blue-50',    'tl_ring' => 'ring-blue-200'],
    ];
    $cfg = $statusConfig[$currentStatus] ?? ['label' => ucfirst($currentStatus), 'pill' => 'bg-gray-100 text-gray-700 ring-gray-200', 'dot' => 'bg-gray-400', 'icon_color' => 'text-gray-400', 'tl_bg' => 'bg-gray-50', 'tl_ring' => 'ring-gray-200'];
@endphp

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
@endpush

@section('content')

{{-- ════════════════════════════════ HERO ════════════════════════════════ --}}
<div class="relative bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-sm">
<div class="relative bg-gradient-to-br from-slate-700 via-slate-800 to-slate-900 rounded-3xl overflow-hidden shadow-xl">

```
<div class="grid lg:grid-cols-5">

    {{-- INFORMASI --}}
    <div class="lg:col-span-3 p-8 lg:p-10 flex flex-col justify-between">

        <div>

            <a href="{{ route('home') }}"
               class="inline-flex items-center gap-2 text-slate-300 hover:text-white transition mb-6">

                <i class="fas fa-arrow-left"></i>
                <span>Kembali ke Beranda</span>

            </a>

            <div class="flex flex-wrap items-center gap-3 mb-4">

                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-medium {{ $cfg['pill'] }}">

                    <span class="w-2 h-2 rounded-full {{ $cfg['dot'] }}"></span>

                    {{ $cfg['label'] }}

                </span>

            </div>

            <h1 class="text-4xl font-bold text-white leading-tight">
                {{ $lokasi->nama_lokasi }}
            </h1>

            <p class="mt-4 text-slate-300 text-base leading-relaxed max-w-2xl">

                <i class="fas fa-location-dot text-red-400 mr-2"></i>

                {{ $lokasi->desa->nama_desa }},
                {{ $lokasi->alamat_lokasi }}

            </p>
<div class="mt-4 flex items-center gap-3">

    <img src="{{ asset('storage/' . $lokasi->relawan->avatar) }}"
     class="w-10 h-10 rounded-full border-2 border-white object-cover">

    <div>
        <p class="text-sm text-slate-400">
            Relawan Posko
        </p>

        <p class="font-semibold text-white">
            {{ $lokasi->relawan->user->name }}
        </p>
    </div>

</div>

    
        </div>

        <div class="grid grid-cols-2 gap-4 mt-8">

            <div class="bg-slate-800/80 border border-slate-700 rounded-2xl p-4">

                <p class="text-sm text-slate-400">
                    Dibuat
                </p>

                <p class="font-semibold text-white mt-1">
                    {{ \Carbon\Carbon::parse($lokasi->created_at)->translatedFormat('d M Y') }}
                </p>

            </div>

            <div class="bg-slate-800/80 border border-slate-700 rounded-2xl p-4">

                <p class="text-sm text-slate-400">
                    Diperbarui
                </p>

                <p class="font-semibold text-white mt-1">
                    {{ \Carbon\Carbon::parse($lokasi->updated_at)->translatedFormat('d M Y') }}
                </p>

                
            </div>

        </div>

        <div class="flex flex-wrap gap-3 mt-8">

            <a href="{{ route('pengungsi.index', $lokasi->id) }}"
               class="inline-flex items-center gap-3 px-6 py-3 bg-emerald-700 hover:bg-green-800 text-white rounded-xl font-semibold transition">

                <i class="fas fa-users"></i>
                Kelola Pengungsi

            </a>

            <a href="{{ route('logistik.index', $lokasi->id) }}"
               class="inline-flex items-center gap-3 px-6 py-3 bg-slate-600 hover:bg-slate-500 text-white rounded-xl font-semibold transition">

                <i class="fas fa-boxes-stacked"></i>
                Monitoring Logistik

            </a>

        </div>

    </div>

    

    {{-- FOTO --}}
    <div class="lg:col-span-2 flex items-center justify-center p-6">

        <img src="{{ asset('storage/'. $lokasi->gambar_lokasi) }}"
             alt="{{ $lokasi->nama_lokasi }}"
             class="w-full max-w-md h-[220px] lg:h-[260px] object-cover rounded-2xl shadow-lg border border-slate-600">


    </div>

</div>


</div>





{{-- ════════════════════════════════ STATS BAR ════════════════════════════════ --}}
<div class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
    <div class="max-w-7xl mx-auto grid grid-cols-2 sm:grid-cols-4 divide-x divide-y sm:divide-y-0 divide-slate-100">
        @php
            $stats = [
                ['Luas Lokasi',     number_format($lokasi->luas_lokasi),          'm²'],
                ['Kapasitas',       number_format($lokasi->kapasitas_pengungsi),  'orang'],
                ['Total Air Harian',number_format($lokasi->sphereLokasi->air_hidup + $lokasi->sphereLokasi->air_kebersihan + $lokasi->sphereLokasi->air_memasak), 'L/hari'],
                ['Kebutuhan Kalori',number_format($lokasi->sphereLokasi->kalori), 'kCal/hari'],
            ];
        @endphp
        @foreach($stats as [$label, $value, $unit])
        <div class="px-5 py-4">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">{{ $label }}</p>
            <p class="mt-0.5 text-xl font-bold text-slate-800">
                {{ $value }}<span class="text-xs font-normal text-slate-400 ml-1">{{ $unit }}</span>
            </p>
        </div>
        @endforeach
    </div>
</div>

{{-- ════════════════════════════════ BODY ════════════════════════════════ --}}
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">

    {{-- ── Row 1: Map + Sphere ── --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">

        {{-- MAP --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-blue-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-1.447-.894L15 9m0 8V9m0 0L9 7"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-slate-800">Lokasi di Peta</h2>
                    <p class="text-xs text-slate-400 mt-0.5">{{ $lokasi->alamat_lokasi }}</p>
                </div>
            </div>
            <div class="p-4">
                <div id="map" class="w-full h-72 rounded-xl overflow-hidden border border-slate-100"></div>
            </div>
        </div>

        {{-- SPHERE --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-violet-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-slate-800">Kebutuhan Sphere Minimum</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Standar kemanusiaan internasional</p>
                </div>
            </div>

            <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-5">
                {{-- Air --}}
                <div class="space-y-2.5">
                    <p class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-blue-600 pb-2 border-b-2 border-blue-100">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                        Air
                    </p>
                    @foreach([
                        ['Kebutuhan Hidup', $lokasi->sphereLokasi->air_hidup,      'L/org/hari', 'Std: 2.5 L'],
                        ['Kebersihan',      $lokasi->sphereLokasi->air_kebersihan, 'L/org/hari', 'Std: 2 L'],
                        ['Memasak',         $lokasi->sphereLokasi->air_memasak,    'L/org/hari', 'Std: 3 L'],
                    ] as [$name, $val, $unit, $std])
                    <div class="bg-blue-50 rounded-xl px-4 py-3">
                        <p class="text-xs font-semibold text-blue-700">{{ $name }}</p>
                        <p class="text-xl font-extrabold text-blue-800 leading-tight mt-0.5">
                            {{ $val }}<span class="text-xs font-medium text-blue-500 ml-1">{{ $unit }}</span>
                        </p>
                        <p class="text-xs text-blue-400 mt-0.5">{{ $std }}</p>
                    </div>
                    @endforeach
                </div>

                {{-- Sanitasi --}}
                <div class="space-y-2.5">
                    <p class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-emerald-600 pb-2 border-b-2 border-emerald-100">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
                        Sanitasi
                    </p>
                    @foreach([
                        ['Toilet Jangka Pendek', $lokasi->sphereLokasi->toilet_pendek,  'unit', 'Std: 1 per 50 orang'],
                        ['Toilet Jangka Panjang',$lokasi->sphereLokasi->toilet_panjang, 'unit', 'Std: 1 per 20 orang'],
                    ] as [$name, $val, $unit, $std])
                    <div class="bg-emerald-50 rounded-xl px-4 py-3">
                        <p class="text-xs font-semibold text-emerald-700">{{ $name }}</p>
                        <p class="text-xl font-extrabold text-emerald-800 leading-tight mt-0.5">
                            {{ $val }}<span class="text-xs font-medium text-emerald-500 ml-1">{{ $unit }}</span>
                        </p>
                        <p class="text-xs text-emerald-400 mt-0.5">{{ $std }}</p>
                    </div>
                    @endforeach
                </div>

                {{-- Nutrisi --}}
                <div class="space-y-2.5">
                    <p class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-orange-600 pb-2 border-b-2 border-orange-100">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>
                        Nutrisi
                    </p>
                    @foreach([
                        ['Kalori',  number_format($lokasi->sphereLokasi->kalori), 'kCal/hari', 'Std: 2100 kCal/orang'],
                        ['Protein', $lokasi->sphereLokasi->protein,               'g/hari',    'Std: 53 g/orang'],
                        ['Lemak',   $lokasi->sphereLokasi->lemak,                 'g/hari',    'Std: 40 g/orang'],
                    ] as [$name, $val, $unit, $std])
                    <div class="bg-orange-50 rounded-xl px-4 py-3">
                        <p class="text-xs font-semibold text-orange-700">{{ $name }}</p>
                        <p class="text-xl font-extrabold text-orange-800 leading-tight mt-0.5">
                            {{ $val }}<span class="text-xs font-medium text-orange-500 ml-1">{{ $unit }}</span>
                        </p>
                        <p class="text-xs text-orange-400 mt-0.5">{{ $std }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ── Row 2: Nutrition Conversion ── --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-100">
            <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                </svg>
            </div>
            <div>
                <h2 class="text-sm font-bold text-slate-800">Konversi Kebutuhan Nutrisi</h2>
                <p class="text-xs text-slate-400 mt-0.5">Estimasi bahan makanan per hari untuk {{ number_format($lokasi->kapasitas_pengungsi) }} orang</p>
            </div>
        </div>

        <div class="p-6">
            {{-- Tab Nav --}}
            <div class="flex gap-1 p-1 bg-slate-100 rounded-xl w-fit mb-6">
                <button id="tab-carbohydrate" onclick="showTab('carbohydrate', this)"
                        class="tab-btn px-5 py-2 rounded-lg text-sm font-semibold transition-all duration-200 bg-white text-blue-600 shadow-sm">
                    🌾 Karbohidrat
                </button>
                <button id="tab-protein" onclick="showTab('protein', this)"
                        class="tab-btn px-5 py-2 rounded-lg text-sm font-semibold transition-all duration-200 text-slate-500 hover:text-slate-700">
                    🥩 Protein
                </button>
                <button id="tab-fat" onclick="showTab('fat', this)"
                        class="tab-btn px-5 py-2 rounded-lg text-sm font-semibold transition-all duration-200 text-slate-500 hover:text-slate-700">
                    🥛 Lemak
                </button>
            </div>

            {{-- Carbohydrate Tab --}}
            <div id="carbohydrate-content" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                @php
                    $carbFoods = [
                        ['Beras',    180, '🍚', 'kCal/100g'],
                        ['Roti',     248, '🍞', 'kCal/100g'],
                        ['Kentang',   62, '🥔', 'kCal/100g'],
                        ['Ubi',      119, '🍠', 'kCal/100g'],
                        ['Singkong', 154, '🌿', 'kCal/100g'],
                    ];
                @endphp
                @foreach($carbFoods as [$name, $kcal, $emoji, $perUnit])
                <div class="border border-slate-200 hover:border-blue-300 hover:shadow-md rounded-xl p-4 transition-all duration-200 cursor-default">
                    <div class="text-2xl mb-2">{{ $emoji }}</div>
                    <p class="font-bold text-slate-800 text-sm">{{ $name }}</p>
                    <p class="text-xs text-slate-400 mb-3">{{ $kcal }} {{ $perUnit }}</p>
                    <p class="text-2xl font-extrabold text-blue-600 leading-none">
                        {{ round(($lokasi->sphereLokasi->kalori / $kcal * 100) / 1000, 2) }}
                    </p>
                    <p class="text-xs text-slate-500 mt-0.5">kg / hari</p>
                    <p class="text-xs text-slate-400 mt-2 pt-2 border-t border-dashed border-slate-200">
                        {{ number_format($lokasi->kapasitas_pengungsi) }} orang
                    </p>
                </div>
                @endforeach
            </div>

            {{-- Protein Tab --}}
            <div id="protein-content" class="hidden grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                @php
                    $proteinFoods = [
                        ['Ayam',        18.2, '🍗', 'g protein/100g'],
                        ['Daging Sapi', 17.5, '🥩', 'g protein/100g'],
                        ['Ikan Bawal',  19.0, '🐟', 'g protein/100g'],
                        ['Tahu',        10.9, '🫘', 'g protein/100g'],
                        ['Tempe',       20.8, '🟫', 'g protein/100g'],
                        ['Telur Ayam',  12.4, '🥚', 'g protein/100g'],
                    ];
                @endphp
                @foreach($proteinFoods as [$name, $prot, $emoji, $perUnit])
                <div class="border border-slate-200 hover:border-emerald-300 hover:shadow-md rounded-xl p-4 transition-all duration-200 cursor-default">
                    <div class="text-2xl mb-2">{{ $emoji }}</div>
                    <p class="font-bold text-slate-800 text-sm">{{ $name }}</p>
                    <p class="text-xs text-slate-400 mb-3">{{ $prot }} {{ $perUnit }}</p>
                    <p class="text-2xl font-extrabold text-emerald-600 leading-none">
                        {{ round(($lokasi->sphereLokasi->protein / $prot * 100) / 1000, 2) }}
                    </p>
                    <p class="text-xs text-slate-500 mt-0.5">kg / hari</p>
                    <p class="text-xs text-slate-400 mt-2 pt-2 border-t border-dashed border-slate-200">
                        {{ number_format($lokasi->kapasitas_pengungsi) }} orang
                    </p>
                </div>
                @endforeach
            </div>

            {{-- Fat Tab --}}
            <div id="fat-content" class="hidden grid grid-cols-2 sm:grid-cols-4 gap-4">
                @php
                    $fatFoods = [
                        ['Susu Kedelai', 2.5, '🫙', 'g lemak/100ml', 'liter'],
                        ['Susu Sapi',    3.5, '🥛', 'g lemak/100ml', 'liter'],
                    ];
                @endphp
                @foreach($fatFoods as [$name, $fat, $emoji, $perUnit, $resultUnit])
                <div class="border border-slate-200 hover:border-amber-300 hover:shadow-md rounded-xl p-4 transition-all duration-200 cursor-default">
                    <div class="text-2xl mb-2">{{ $emoji }}</div>
                    <p class="font-bold text-slate-800 text-sm">{{ $name }}</p>
                    <p class="text-xs text-slate-400 mb-3">{{ $fat }} {{ $perUnit }}</p>
                    <p class="text-2xl font-extrabold text-amber-600 leading-none">
                        {{ round(($lokasi->sphereLokasi->lemak / $fat * 100) / 1000, 2) }}
                    </p>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $resultUnit }} / hari</p>
                    <p class="text-xs text-slate-400 mt-2 pt-2 border-t border-dashed border-slate-200">
                        {{ number_format($lokasi->kapasitas_pengungsi) }} orang
                    </p>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── Row 3: Status History ── --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-100">
            <div class="w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-sm font-bold text-slate-800">Riwayat Status</h2>
                <p class="text-xs text-slate-400 mt-0.5">{{ $lokasi->statusLokasi->count() }} entri tercatat</p>
            </div>
        </div>

        <div class="p-6">
            <div class="max-h-96 overflow-y-auto pr-1 space-y-0 scrollbar-thin">
                @foreach($lokasi->statusLokasi->sortByDesc('created_at') as $st)
                @php
                    $stCfg = $statusConfig[$st->status] ?? ['label' => ucfirst($st->status), 'icon_color' => 'text-gray-400', 'tl_bg' => 'bg-gray-50', 'tl_ring' => 'ring-gray-200'];
                @endphp
                <div class="flex gap-4 pb-6 relative">
                    @if(!$loop->last)
                    <div class="absolute left-5 top-10 bottom-0 w-px bg-slate-100"></div>
                    @endif

                    <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 ring-2 z-10 {{ $stCfg['tl_bg'] }} {{ $stCfg['tl_ring'] }}">
                        @if($st->status === 'approved')
                        <svg class="w-4 h-4 {{ $stCfg['icon_color'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                        @elseif($st->status === 'rejected')
                        <svg class="w-4 h-4 {{ $stCfg['icon_color'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        @elseif($st->status === 'done')
                        <svg class="w-4 h-4 {{ $stCfg['icon_color'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        @else
                        <svg class="w-4 h-4 {{ $stCfg['icon_color'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        @endif
                    </div>

                    <div class="flex-1 pt-1.5">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="text-sm font-bold text-slate-800">{{ $stCfg['label'] }}</span>
                            <time class="text-xs text-slate-400">
                                {{ \Carbon\Carbon::parse($st->created_at)->timezone('Asia/Jakarta')->format('d M Y · H:i') }}
                            </time>
                        </div>
                        @if($st->catatan)
                        <p class="mt-1.5 text-sm text-slate-600 leading-relaxed">{{ $st->catatan }}</p>
                        @else
                        <p class="mt-1.5 text-sm text-slate-400 italic">Tidak ada catatan.</p>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

</div>{{-- /page body --}}

@endsection

@section('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>

    // ── Foto ──
    function previewImage(event)
{
    const input = event.target;
    const preview = document.getElementById('preview-foto');

    if(input.files && input.files[0])
    {
        preview.src = URL.createObjectURL(input.files[0]);

        preview.classList.remove('hidden');
    }
}
    // ── Map ──
    var map = L.map('map', { zoomControl: true })
               .setView([{{ $lokasi->latitude }}, {{ $lokasi->longitude }}], 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap',
        maxZoom: 18,
    }).addTo(map);

    var pinIcon = L.divIcon({
        className: '',
        html: '<div style="width:28px;height:28px;border-radius:50% 50% 50% 0;background:#2563EB;border:3px solid #fff;box-shadow:0 4px 12px rgba(37,99,235,.5);transform:rotate(-45deg);"></div>',
        iconSize: [28, 28],
        iconAnchor: [14, 28],
        popupAnchor: [0, -32],
    });

    L.marker([{{ $lokasi->latitude }}, {{ $lokasi->longitude }}], { icon: pinIcon })
     .addTo(map)
     .bindPopup(
        '<div style="min-width:180px;padding:2px 0;">' +
            '<div style="font-weight:700;font-size:.88rem;color:#0F172A;margin-bottom:3px;">{{ $lokasi->nama_lokasi }}</div>' +
            '<div style="font-size:.75rem;color:#64748B;margin-bottom:8px;">{{ $lokasi->alamat_lokasi }}</div>' +
            '<a href="https://www.google.com/maps?q={{ $lokasi->latitude }},{{ $lokasi->longitude }}" target="_blank" ' +
               'style="font-size:.75rem;color:#2563EB;font-weight:600;text-decoration:none;">↗ Buka di Google Maps</a>' +
        '</div>',
        { maxWidth: 230 }
     )
     .openPopup();

    // ── Tabs ──
    var tabColors = {
        carbohydrate: 'text-blue-600',
        protein:      'text-emerald-600',
        fat:          'text-amber-600',
    };

    function showTab(name, btn) {
        ['carbohydrate', 'protein', 'fat'].forEach(function(t) {
            document.getElementById(t + '-content').classList.add('hidden');
            var b = document.getElementById('tab-' + t);
            b.classList.remove('bg-white', 'shadow-sm', 'text-blue-600', 'text-emerald-600', 'text-amber-600');
            b.classList.add('text-slate-500');
        });

        document.getElementById(name + '-content').classList.remove('hidden');
        btn.classList.remove('text-slate-500');
        btn.classList.add('bg-white', 'shadow-sm', tabColors[name]);
    }
</script>
@endsection