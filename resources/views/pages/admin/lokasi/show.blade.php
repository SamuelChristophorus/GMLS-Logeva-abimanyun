@extends('layouts.admin')

@section('title', 'Detail Lokasi')

@push('head')
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
@endpush

@section('content')
<div class="relative z-10 container mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10 min-h-screen flex flex-col gap-8">

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white/60 backdrop-blur-xl p-6 md:p-8 rounded-3xl border border-white/80 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-50 border border-blue-100/50 text-blue-600 text-xs font-bold tracking-wide uppercase mb-3">
                <i class="fas fa-campground opacity-70"></i>
                Detail Posko
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 tracking-tight">
                {{$lokasi->nama_lokasi}}
            </h1>
            <p class="text-slate-500 mt-1 font-medium text-sm flex items-center gap-2">
                <i class="fas fa-map-pin text-slate-400"></i> Desa {{$lokasi->desa->nama_desa}}
            </p>
        </div>

        <div class="flex items-center gap-3 mt-2 md:mt-0">
            <a href="{{route('admin.lokasi.edit', $lokasi->id)}}" class="inline-flex items-center justify-center gap-2 bg-amber-50 hover:bg-amber-100 text-amber-600 font-bold px-5 py-3 rounded-xl shadow-sm border border-amber-200 transition-all">
                <i class="fas fa-edit text-sm"></i> Edit
            </a>
            <a href="{{route('admin.lokasi.index')}}" class="inline-flex items-center justify-center gap-2 bg-white hover:bg-slate-50 text-slate-600 font-bold px-5 py-3 rounded-xl shadow-sm border border-slate-200 transition-all hover:-translate-x-1">
                <i class="fas fa-arrow-left text-sm opacity-70"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Status Operasional Posko (tombol Mulai/Tutup + dashboard saat beroperasi) -->
    <div>
        @include('includes.operasional-lokasi', ['lokasi' => $lokasi, 'operasional' => $operasional ?? null])
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        
        <!-- Left/Main Column -->
        <div class="{{ $lokasi->isBeroperasi() ? 'xl:col-span-3' : 'xl:col-span-2' }} flex flex-col gap-6">
            
            <!-- Media & Map -->
            <div class="bg-white rounded-3xl border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden">
                <div class="grid grid-cols-1 md:grid-cols-2">
                    <div class="h-[300px] md:h-full min-h-[300px] relative group overflow-hidden">
                        <img src="{{asset('storage/'. $lokasi->gambar_lokasi)}}" 
                        alt="{{$lokasi->nama_lokasi}}" 
                        class="w-full h-full object-contain transition-transform duration-500">
                        <div class="bg-gradient-to-t from-slate-900/60 to-transparent"></div>
                        <div class="absolute bottom-4 left-4 right-4 flex justify-between items-end">
                            <span class="px-3 py-1 rounded-lg bg-white/20 backdrop-blur-md border border-white/30 text-white text-xs font-bold">Foto Lokasi</span>
            </div>
        </div>
                    <div class="h-[300px] md:h-full min-h-[300px] relative border-l border-slate-100">
                        <div id="map" class="w-full h-full z-10"></div>
                        <div class="absolute top-4 right-4 z-20">
                            <span class="px-3 py-1 rounded-lg bg-white/80 backdrop-blur-md border border-slate-200/50 text-slate-700 text-xs font-bold shadow-sm">Peta Koordinat</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Basic Info Card -->
            <div class="bg-white rounded-3xl border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] p-6 md:p-8">
                <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    Informasi Terperinci
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-slate-50/70 p-4 rounded-2xl border border-slate-100">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Alamat Lengkap</p>
                        <p class="text-sm font-bold text-slate-700">{{$lokasi->alamat_lokasi}}</p>
                    </div>
                    <div class="bg-slate-50/70 p-4 rounded-2xl border border-slate-100">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Penanggung Jawab</p>
                        <div class="flex items-center gap-2 mt-1">
                            <img src="{{asset('storage/'. $lokasi->relawan->avatar)}}" alt="Avatar" class="w-6 h-6 rounded-full object-cover border border-slate-200">
                            <div>
                                <p class="text-sm font-bold text-slate-700 leading-tight">{{$lokasi->relawan->user->name}}</p>
                                <p class="text-[10px] text-slate-500 font-medium leading-tight">{{$lokasi->relawan->user->email}}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-emerald-50/70 p-4 rounded-2xl border border-emerald-100">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-bold text-emerald-600/70 uppercase tracking-widest mb-1">Kapasitas Maksimal</p>
                                <p class="text-lg font-black text-emerald-700">{{number_format($lokasi->kapasitas_pengungsi, 0, ',', '.')}} <span class="text-sm font-bold text-emerald-600/70">Jiwa</span></p>
                            </div>
                            <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center"><i class="fas fa-users"></i></div>
                        </div>
                    </div>
                    <div class="bg-indigo-50/70 p-4 rounded-2xl border border-indigo-100">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-bold text-indigo-600/70 uppercase tracking-widest mb-1">Luas Area</p>
                                <p class="text-lg font-black text-indigo-700">{{number_format($lokasi->luas_lokasi, 0, ',', '.')}} <span class="text-sm font-bold text-indigo-600/70">m²</span></p>
                            </div>
                            <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center"><i class="fas fa-vector-square"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Tracking Card -->
            <div class="bg-white rounded-3xl border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] p-6 md:p-8">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        Log Status Lokasi
                    </h3>
                    <a href="{{route('admin.status-lokasi.create', $lokasi->id)}}" class="inline-flex items-center justify-center gap-2 bg-teal-600 hover:bg-teal-700 text-white font-bold px-4 py-2 rounded-xl shadow-sm shadow-teal-500/30 transition-all text-sm">
                        <i class="fas fa-plus"></i> Tambah Status
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100">
                                <th class="pb-3 px-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Tanggal</th>
                                <th class="pb-3 px-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Status</th>
                                <th class="pb-3 px-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Catatan</th>
                                <th class="pb-3 px-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse ($lokasi->statusLokasi()->latest()->get() as $status)
                            <tr class="hover:bg-slate-50/50 transition-colors group">
                                <td class="py-4 px-4 text-sm font-bold text-slate-600 whitespace-nowrap">{{ $status->created_at->format('d M Y, H:i') }}</td>
                                <td class="py-4 px-4">
                                    @php
                                        $badgeBg = match ($status->status) {
                                            'approved' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                            'pending'  => 'bg-amber-100 text-amber-700 border-amber-200',
                                            'rejected' => 'bg-rose-100 text-rose-700 border-rose-200',
                                            default    => 'bg-slate-100 text-slate-600',
                                        };
                                        if(stripos($status->status, 'aman') !== false || stripos($status->status, 'dibuka') !== false) $badgeBg = 'bg-emerald-100 text-emerald-700 border-emerald-200';
                                        elseif(stripos($status->status, 'penuh') !== false || stripos($status->status, 'waspada') !== false) $badgeBg = 'bg-amber-100 text-amber-700 border-amber-200';
                                        elseif(stripos($status->status, 'tutup') !== false || stripos($status->status, 'bahaya') !== false) $badgeBg = 'bg-rose-100 text-rose-700 border-rose-200';
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border {{$badgeBg}}">
                                        {{$status->status}}
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-sm text-slate-600 max-w-xs truncate" title="{{$status->catatan}}">
                                    {{$status->catatan ?? '-'}}
                                </td>
                                <td class="py-4 px-4 text-right">
                                    <div class="flex items-center justify-end gap-2 opacity-50 group-hover:opacity-100 transition-opacity">
                                        <a href="{{route('admin.status-lokasi.edit', $status->id)}}" class="w-7 h-7 rounded-md bg-amber-50 text-amber-600 flex items-center justify-center hover:bg-amber-500 hover:text-white transition-colors" title="Edit">
                                            <i class="fas fa-edit text-[10px]"></i>
                                        </a>
                                        <form action="{{route('admin.status-lokasi.destroy', $status->id)}}" method="POST" class="inline-block m-0" onsubmit="return confirm('Hapus log status ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="w-7 h-7 rounded-md bg-rose-50 text-rose-600 flex items-center justify-center hover:bg-rose-600 hover:text-white transition-colors" title="Hapus">
                                                <i class="fas fa-trash-alt text-[10px]"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-slate-400 text-sm font-medium">Beban log status masih kosong.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>

        <!-- Right Column: Sphere & Nutrition (hanya saat SIAGA / belum beroperasi) -->
        @if (! $lokasi->isBeroperasi() && $lokasi->sphereLokasi)
        <div class="flex flex-col gap-6">
            
            <!-- Standard Sphere Card -->
            <div class="bg-gradient-to-br from-indigo-600 to-blue-700 rounded-3xl shadow-lg p-1 relative overflow-hidden">
                <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMiIgY3k9IjIiIHI9IjIiIGZpbGw9InJnYmEoMjU1LDI1NSwyNTUsMC4wNykiLz48L3N2Zz4=')]"></div>
                
                <div class="bg-white/10 backdrop-blur-md rounded-[22px] border border-white/20 p-6 relative z-10">
                    <h3 class="text-lg font-bold text-white mb-2 flex items-center gap-2">
                        <i class="fas fa-shield-alt text-blue-200"></i> Standar Sphere
                    </h3>
                    <p class="text-blue-100/70 text-xs mb-6 font-medium">Kalkulasi kebutuhan dasar untuk {{$lokasi->kapasitas_pengungsi}} jiwa.</p>

                    <div class="space-y-3">
                        <!-- Water -->
                        <div class="bg-white/5 border border-white/10 rounded-xl p-3 flex justify-between items-center hover:bg-white/10 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-blue-400/20 text-blue-200 flex items-center justify-center"><i class="fas fa-tint text-xs"></i></div>
                                <div>
                                    <p class="text-[10px] text-blue-200 font-bold uppercase tracking-wider">Total Air Harian</p>
                                    <p class="text-white font-black text-sm">{{number_format(($lokasi->sphereLokasi->air_hidup + $lokasi->sphereLokasi->air_kebersihan + $lokasi->sphereLokasi->air_memasak), 0, ',', '.')}} L</p>
                                </div>
                            </div>
                        </div>

                        <!-- Sanitation -->
                        <div class="bg-white/5 border border-white/10 rounded-xl p-3 flex justify-between items-center hover:bg-white/10 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-emerald-400/20 text-emerald-200 flex items-center justify-center"><i class="fas fa-restroom text-xs"></i></div>
                                <div>
                                    <p class="text-[10px] text-emerald-200 font-bold uppercase tracking-wider">Fasilitas Toilet</p>
                                    <p class="text-white font-black text-sm">{{$lokasi->sphereLokasi->toilet_pendek}} Pendek / {{$lokasi->sphereLokasi->toilet_panjang}} Panjang</p>
                                </div>
                            </div>
                        </div>

                        <!-- Nutrition Totals -->
                        <div class="bg-white/5 border border-white/10 rounded-xl p-3 flex justify-between items-center hover:bg-white/10 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-amber-400/20 text-amber-200 flex items-center justify-center"><i class="fas fa-utensils text-xs"></i></div>
                                <div>
                                    <p class="text-[10px] text-amber-200 font-bold uppercase tracking-wider">Total Makro Harian</p>
                                    <p class="text-white font-bold text-[11px]">{{number_format($lokasi->sphereLokasi->kalori, 0, ',', '.')}} kCal | {{$lokasi->sphereLokasi->protein}}g Pro | {{$lokasi->sphereLokasi->lemak}}g Lemak</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Food Conversions -->
            <div class="bg-white rounded-3xl border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] p-6 z-10" x-data="{ tab: 'karbo' }">
                <h3 class="text-md font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-box-open text-amber-500"></i> Konversi Logistik Harian
                </h3>

                <!-- Tabs -->
                <div class="flex p-1 bg-slate-100/80 rounded-xl mb-4">
                    <button @click="tab = 'karbo'" :class="tab === 'karbo' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="flex-1 py-1.5 px-3 rounded-lg text-xs font-bold transition-all">Karbohidrat</button>
                    <button @click="tab = 'protein'" :class="tab === 'protein' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="flex-1 py-1.5 px-3 rounded-lg text-xs font-bold transition-all">Protein</button>
                    <button @click="tab = 'lemak'" :class="tab === 'lemak' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="flex-1 py-1.5 px-3 rounded-lg text-xs font-bold transition-all">Lemak</button>
                </div>

                <!-- Karbohidrat Tab -->
                <div x-show="tab === 'karbo'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-3">
                    <div class="flex justify-between items-center p-3 rounded-xl bg-orange-50/50 border border-orange-100">
                        <span class="text-sm font-bold text-slate-700">Beras</span>
                        <span class="text-sm font-black text-orange-600">{{ round(($lokasi->sphereLokasi->kalori / 180 * 100 ) / 1000, 2) }} <span class="text-[10px] text-orange-500 font-bold uppercase">kg/hari</span></span>
                    </div>
                    <div class="flex justify-between items-center p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-sm font-bold text-slate-700">Roti</span>
                        <span class="text-sm font-black text-slate-800">{{ round(($lokasi->sphereLokasi->kalori / 248 * 100 ) / 1000, 2) }} <span class="text-[10px] text-slate-500 font-bold uppercase">kg/hari</span></span>
                    </div>
                    <div class="flex justify-between items-center p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-sm font-bold text-slate-700">Ubi / Singkong</span>
                        <span class="text-sm font-black text-slate-800">~{{ round(($lokasi->sphereLokasi->kalori / 130 * 100 ) / 1000, 2) }} <span class="text-[10px] text-slate-500 font-bold uppercase">kg/hari</span></span>
                    </div>
                </div>

                <!-- Protein Tab -->
                <div x-show="tab === 'protein'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-3">
                    <div class="flex justify-between items-center p-3 rounded-xl bg-red-50/50 border border-red-100">
                        <span class="text-sm font-bold text-slate-700">Ayam / Sapi</span>
                        <span class="text-sm font-black text-red-600">~{{ round(($lokasi->sphereLokasi->protein / 18 * 100 ) / 1000, 2) }} <span class="text-[10px] text-red-500 font-bold uppercase">kg/hari</span></span>
                    </div>
                    <div class="flex justify-between items-center p-3 rounded-xl bg-blue-50/50 border border-blue-100">
                        <span class="text-sm font-bold text-slate-700">Ikan</span>
                        <span class="text-sm font-black text-blue-600">{{ round(($lokasi->sphereLokasi->protein / 19 * 100 ) / 1000, 2) }} <span class="text-[10px] text-blue-500 font-bold uppercase">kg/hari</span></span>
                    </div>
                    <div class="flex justify-between items-center p-3 rounded-xl bg-amber-50/50 border border-amber-100">
                        <span class="text-sm font-bold text-slate-700">Telur</span>
                        <span class="text-sm font-black text-amber-600">{{ round(($lokasi->sphereLokasi->protein / 12.4 * 100 * $lokasi->kapasitas_pengungsi ) / 1000, 2) }} <span class="text-[10px] text-amber-500 font-bold uppercase">kg/hari</span></span>
                    </div>
                     <div class="flex justify-between items-center p-3 rounded-xl bg-green-50/50 border border-green-100">
                        <span class="text-sm font-bold text-slate-700">Tahu / Tempe</span>
                        <span class="text-sm font-black text-green-600">~{{ round(($lokasi->sphereLokasi->protein / 15 * 100 ) / 1000, 2) }} <span class="text-[10px] text-green-500 font-bold uppercase">kg/hari</span></span>
                    </div>
                </div>

                <!-- Lemak Tab -->
                <div x-show="tab === 'lemak'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-3">
                    <div class="flex justify-between items-center p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-sm font-bold text-slate-700">Susu Kedelai</span>
                        <span class="text-sm font-black text-slate-800">{{ round(($lokasi->sphereLokasi->lemak / 2.5 * 100 ) / 1000, 2) }} <span class="text-[10px] text-slate-500 font-bold uppercase">L/hari</span></span>
                    </div>
                    <div class="flex justify-between items-center p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-sm font-bold text-slate-700">Susu Sapi</span>
                        <span class="text-sm font-black text-slate-800">{{ round(($lokasi->sphereLokasi->lemak / 3.5 * 100 ) / 1000, 2) }} <span class="text-[10px] text-slate-500 font-bold uppercase">L/hari</span></span>
                    </div>
                </div>
            </div>
            
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var map = L.map('map', {
            zoomControl: false,
            scrollWheelZoom: false
        }).setView([{{$lokasi->latitude}}, {{$lokasi->longitude}}], 14);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(map);

        L.control.zoom({ position: 'bottomright' }).addTo(map);

        // Custom marker icon
        var customIcon = L.divIcon({
            className: 'custom-div-icon',
            html: `<div class="w-10 h-10 -ml-5 -mt-10 bg-blue-600 text-white rounded-t-full rounded-br-full transform -rotate-45 flex items-center justify-center shadow-lg border-2 border-white">
                        <i class="fas fa-campground transform rotate-45 text-sm"></i>
                   </div>`,
            iconSize: [40, 40],
            iconAnchor: [20, 40],
            popupAnchor: [0, -35]
        });

        // Add marker
        L.marker([{{$lokasi->latitude}}, {{$lokasi->longitude}}], {icon: customIcon})
        .addTo(map)
        .bindPopup(`
            <div class="font-sans px-1 py-1 text-center">
                <span class="text-xs font-bold uppercase tracking-wider text-blue-600 block mb-1">Posko Pengungsian</span>
                <strong class="text-slate-800 block mb-1">{{$lokasi->nama_lokasi}}</strong>
                <a href="https://www.google.com/maps/search/?api=1&query={{$lokasi->latitude}},{{$lokasi->longitude}}" target="_blank" class="inline-block mt-2 px-3 py-1 bg-blue-50 text-blue-600 rounded-md text-xs font-bold hover:bg-blue-100 transition-colors">
                    Buka di Google Maps
                </a>
            </div>
        `, {
            className: 'custom-popup rounded-[20px]',
            closeButton: false
        }).openPopup();
    });
</script>
<style>
    /* Popup styling adjustments */
    .custom-popup .leaflet-popup-content-wrapper { border-radius: 1rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); }
    .custom-popup .leaflet-popup-tip { box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
</style>
@endpush