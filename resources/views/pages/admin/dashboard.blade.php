@extends('layouts.admin')

@section('title', 'Dashboard Admin')

@section('content')
<!-- Decorative Ambient Background -->
<div class="fixed inset-0 z-0 bg-slate-50 pointer-events-none overflow-hidden">
    <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-gradient-to-bl from-blue-200/50 via-indigo-100/20 to-transparent rounded-full blur-3xl opacity-70 transform translate-x-1/3 -translate-y-1/3"></div>
    <div class="absolute bottom-0 left-0 w-[600px] h-[600px] bg-gradient-to-tr from-emerald-100/40 via-teal-50/20 to-transparent rounded-full blur-3xl opacity-60 transform -translate-x-1/4 translate-y-1/4"></div>
</div>

<div class="relative z-10 container mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10 min-h-screen flex flex-col gap-8">
    
    <!-- 1. HEADER SECTION -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 bg-white/60 backdrop-blur-xl p-6 md:p-8 rounded-3xl border border-white/80 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-50 border border-blue-100/50 text-blue-600 text-xs font-bold tracking-wide uppercase mb-3">
                <i class="fas fa-chart-pie opacity-70"></i>
                Ringkasan Sistem
            </div>
            <h1 class="text-3xl md:text-4xl font-extrabold text-slate-800 tracking-tight">
                Dashboard Overview
            </h1>
            <p class="text-slate-500 mt-2 font-medium">Selamat datang kembali, pantau aktivitas dan data terkini dari aplikasi Anda.</p>
        </div>
        <div class="flex items-center gap-2 mt-2 md:mt-0 bg-white px-4 py-2.5 rounded-xl border border-slate-100 shadow-sm">
            <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-500">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-none">Hari Ini</p>
                <p class="text-sm font-bold text-slate-700 mt-0.5 leading-none">{{ now()->translatedFormat('d F Y') }}</p>
            </div>
        </div>
    </div>

    <!-- 2. STATS CARDS GRID -->
    <!-- The grid adjusts dynamically based on screen size -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        
        <!-- Stats Card 1: Lokasi Pengungsian -->
        <div class="group relative bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgb(0,0,0,0.03)] hover:shadow-[0_8px_30px_rgb(59,130,246,0.12)] transition-all duration-300 overflow-hidden flex flex-col justify-between">
            <!-- Background accent -->
            <div class="absolute -right-6 -top-6 w-32 h-32 bg-gradient-to-br from-blue-50 to-blue-100/50 rounded-full blur-2xl group-hover:bg-blue-100 transition-colors duration-500"></div>
            
            <div class="relative z-10 flex justify-between items-start mb-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-red-500 to-indigo-600 flex items-center justify-center text-white shadow-lg shadow-blue-500/30 transform group-hover:scale-110 group-hover:rotate-3 transition-transform duration-300">
                    <i class="fas fa-campground text-xl"></i>
                </div>
                <div class="bg-blue-50 text-blue-600 px-2.5 py-1 rounded-lg text-xs font-bold border border-blue-100 flex items-center gap-1.5">
                    <i class="fas fa-arrow-up text-[10px]"></i> Total
                </div>
            </div>
            <div class="relative z-10 mt-auto">
                <p class="text-sm font-semibold text-slate-400 mb-1">Lokasi Pengungsian</p>
                <div class="flex items-baseline gap-2">
                    <h3 class="text-4xl font-black text-slate-800 tracking-tight">{{ \App\Models\Lokasi::count() }}</h3>
                    <span class="text-sm font-medium text-slate-500">Titik</span>
                </div>
            </div>
            
            <!-- Hover Action Area -->
            <div class="absolute inset-x-0 bottom-0 h-0 bg-gradient-to-t from-blue-50/80 to-transparent group-hover:h-16 transition-all duration-300 flex items-end justify-center pb-4 opacity-0 group-hover:opacity-100">
                <a href="{{ route('admin.lokasi.index') }}" class="text-sm font-bold text-blue-600 flex items-center gap-1 hover:text-blue-700">
                    Kelola Lokasi <i class="fas fa-arrow-right text-xs transition-transform group-hover:translate-x-1"></i>
                </a>
            </div>
        </div>

        <!-- Stats Card 2: Relawan -->
        <div class="group relative bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgb(0,0,0,0.03)] hover:shadow-[0_8px_30px_rgb(16,185,129,0.12)] transition-all duration-300 overflow-hidden flex flex-col justify-between">
            <div class="absolute -right-6 -top-6 w-32 h-32 bg-gradient-to-br from-emerald-50 to-yellow-100/50 rounded-full blur-2xl group-hover:bg-emerald-100 transition-colors duration-500"></div>
            
            <div class="relative z-10 flex justify-between items-start mb-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-400 to-green-600 flex items-center justify-center text-white shadow-lg shadow-green-500/30 transform group-hover:scale-110 group-hover:-rotate-3 transition-transform duration-300">
                    <i class="fas fa-users text-xl"></i>
                </div>
                <div class="bg-green-50 text-green-600 px-2.5 py-1 rounded-lg text-xs font-bold border border-green-100 flex items-center gap-1.5">
                    <i class="fas fa-check-circle text-[10px]"></i> Aktif
                </div>
            </div>
            <div class="relative z-10 mt-auto">
                <p class="text-sm font-semibold text-slate-400 mb-1">Total Relawan</p>
                <div class="flex items-baseline gap-2">
                    <h3 class="text-4xl font-black text-slate-800 tracking-tight">{{ \App\Models\Relawan::count() }}</h3>
                    <span class="text-sm font-medium text-slate-500">Orang</span>
                </div>
            </div>
            
            <div class="absolute inset-x-0 bottom-0 h-0 bg-gradient-to-t from-emerald-50/80 to-transparent group-hover:h-16 transition-all duration-300 flex items-end justify-center pb-4 opacity-0 group-hover:opacity-100">
                <a href="{{ route('admin.relawan.index') }}" class="text-sm font-bold text-emerald-600 flex items-center gap-1 hover:text-emerald-700">
                    Kelola Relawan <i class="fas fa-arrow-right text-xs transition-transform group-hover:translate-x-1"></i>
                </a>
            </div>
        </div>

        <!-- Stats Card 3: Desa -->
        <div class="group relative bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgb(0,0,0,0.03)] hover:shadow-[0_8px_30px_rgb(139,92,246,0.12)] transition-all duration-300 overflow-hidden flex flex-col justify-between sm:col-span-2 lg:col-span-1">
            <div class="absolute -right-6 -top-6 w-32 h-32 bg-gradient-to-br from-purple-50 to-fuchsia-100/50 rounded-full blur-2xl group-hover:bg-purple-100 transition-colors duration-500"></div>
            
            <div class="relative z-10 flex justify-between items-start mb-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-purple-500 to-fuchsia-600 flex items-center justify-center text-white shadow-lg shadow-purple-500/30 transform group-hover:scale-110 group-hover:rotate-3 transition-transform duration-300">
                    <i class="fas fa-map-signs text-xl"></i>
                </div>
                <!-- Mini sparkline fake -->
                <div class="flex items-end gap-1 h-6 opacity-60">
                    <div class="w-1.5 bg-purple-300 h-2 rounded-t-sm"></div>
                    <div class="w-1.5 bg-purple-400 h-4 rounded-t-sm"></div>
                    <div class="w-1.5 bg-purple-500 h-3 rounded-t-sm"></div>
                    <div class="w-1.5 bg-purple-600 h-6 rounded-t-sm"></div>
                </div>
            </div>
            <div class="relative z-10 mt-auto">
                <p class="text-sm font-semibold text-slate-400 mb-1">Area Desa</p>
                <div class="flex items-baseline gap-2">
                    <h3 class="text-4xl font-black text-slate-800 tracking-tight">{{ \App\Models\Desa::count() }}</h3>
                    <span class="text-sm font-medium text-slate-500">Wilayah</span>
                </div>
            </div>
            
            <div class="absolute inset-x-0 bottom-0 h-0 bg-gradient-to-t from-purple-50/80 to-transparent group-hover:h-16 transition-all duration-300 flex items-end justify-center pb-4 opacity-0 group-hover:opacity-100">
                <a href="{{ route('admin.desa.index') }}" class="text-sm font-bold text-purple-600 flex items-center gap-1 hover:text-purple-700">
                    Kelola Wilayah <i class="fas fa-arrow-right text-xs transition-transform group-hover:translate-x-1"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- 3. MAP SECTION -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden flex flex-col mb-12">
        
        <!-- Map Header & Filters -->
        <div class="p-6 md:p-8 border-b border-slate-100 bg-gradient-to-r from-gray-50/50 to-white flex flex-col xl:flex-row justify-between items-start xl:items-center gap-6">
            <div class="flex gap-4 items-center">
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center shrink-0">
                    <i class="fas fa-map-marked-alt text-xl text-indigo-600"></i>
                </div>
                <div>
                    <h2 class="text-xl md:text-2xl font-bold text-slate-800">Pemetaan Titik Pengungsian</h2>
                    <p class="text-sm text-slate-500 mt-1 font-medium">Visualisasi {{ \App\Models\Lokasi::count() }} lokasi berdasarkan status persetujuan saat ini.</p>
                </div>
            </div>
            
            <!-- Beautiful Status Filters -->
            <div class="flex flex-wrap items-center gap-3">
                <!-- Approved -->
                @php $approvedCount = \App\Models\Lokasi::whereHas('latestStatusView', function($q) { $q->where('status', 'approved'); })->count(); @endphp
                <div class="flex items-center bg-white border border-slate-200 rounded-xl px-4 py-2 hover:border-emerald-300 hover:shadow-sm transition-all shadow-[0_2px_10px_rgb(0,0,0,0.02)]">
                    <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 mr-2.5 animate-pulse"></div>
                    <span class="text-sm font-bold text-slate-700 mr-2 text-emerald-700">Disetujui</span>
                    <span class="bg-slate-100 text-slate-600 px-2.5 py-0.5 rounded-lg text-xs font-bold border border-slate-200">{{ $approvedCount }}</span>
                </div>
                
                <!-- Pending -->
                @php $pendingCount = \App\Models\Lokasi::whereHas('latestStatusView', function($q) { $q->where('status', 'pending'); })->count(); @endphp
                <div class="flex items-center bg-white border border-slate-200 rounded-xl px-4 py-2 hover:border-amber-300 hover:shadow-sm transition-all shadow-[0_2px_10px_rgb(0,0,0,0.02)]">
                    <div class="w-2.5 h-2.5 rounded-full bg-amber-400 mr-2.5"></div>
                    <span class="text-sm font-bold text-slate-700 mr-2 text-amber-600">Pending</span>
                    <span class="bg-slate-100 text-slate-600 px-2.5 py-0.5 rounded-lg text-xs font-bold border border-slate-200">{{ $pendingCount }}</span>
                </div>

                <!-- Rejected -->
                @php $rejectedCount = \App\Models\Lokasi::whereHas('latestStatusView', function($q) { $q->where('status', 'rejected'); })->count(); @endphp
                <div class="flex items-center bg-white border border-slate-200 rounded-xl px-4 py-2 hover:border-rose-300 hover:shadow-sm transition-all shadow-[0_2px_10px_rgb(0,0,0,0.02)]">
                    <div class="w-2.5 h-2.5 rounded-full bg-rose-500 mr-2.5"></div>
                    <span class="text-sm font-bold text-slate-700 mr-2 text-rose-600">Ditolak</span>
                    <span class="bg-slate-100 text-slate-600 px-2.5 py-0.5 rounded-lg text-xs font-bold border border-slate-200">{{ $rejectedCount }}</span>
                </div>
            </div>
        </div>

        <!-- Map Container -->
        <div class="p-4 md:p-6 bg-slate-50 relative">
            
            <!-- Locator Button overlay -->
            <button id="btn-my-location" class="absolute top-8 right-8 z-[1000] bg-white text-slate-700 hover:text-blue-600 px-4 py-2.5 rounded-xl shadow-lg border border-slate-200/80 font-bold text-sm flex items-center gap-2 hover:border-blue-300 hover:shadow-blue-500/20 transition-all transform hover:-translate-y-0.5">
                <i class="fas fa-crosshairs"></i> Cari Lokasi Saya
            </button>

            <!-- Map Target -->
            <div id="map" class="h-[500px] lg:h-[600px] w-full rounded-2xl border-4 border-white shadow-sm z-0"></div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- Load Leaflet CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    /* Styling for custom markers and popups */
    .leaflet-popup-content-wrapper { 
        padding: 0 !important; 
        border-radius: 16px !important; 
        overflow: hidden; 
        border: 1px solid rgba(0,0,0,0.05); 
        box-shadow: 0 10px 40px -10px rgba(0,0,0,0.15), 0 0 10px rgba(0,0,0,0.05) !important; 
    }
    .leaflet-popup-content { margin: 0 !important; }
    .leaflet-popup-tip-container { display: none; } /* Hide the ugly default tip */
    .leaflet-container a.leaflet-popup-close-button { 
        padding: 8px 10px 0 0; 
        color: #94a3b8; 
        z-index: 10;
        text-decoration: none;
    }
    .leaflet-container a.leaflet-popup-close-button:hover { 
        color: #1e293b; 
        background: transparent;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // --- 1. Init Map w/ Modern Base Tile ---
    // Start at a general Indonesia view
    const map = L.map('map', {zoomControl: false}).setView([-2.5489, 118.0149], 5);
    
    // Move zoom control to bottom right
    L.control.zoom({ position: 'bottomright' }).addTo(map);
    
    // Use CartoDB Positron for a cleaner, modern look suitable for dashboards
    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
        subdomains: 'abcd',
        maxZoom: 20
    }).addTo(map);

    // --- 2. Custom Icon Factory ---
    function createModernIcon(status) {
        let colors = {
            approved: { bg: 'bg-emerald-500', grad: 'from-emerald-400 to-green-600', shadow: 'shadow-green-500/40', ring: 'ring-emerald-200' },
            rejected: { bg: 'bg-rose-500', grad: 'from-rose-400 to-red-600', shadow: 'shadow-red-500/40', ring: 'ring-rose-200' },
            pending:  { bg: 'bg-amber-400', grad: 'from-amber-300 to-yellow-500', shadow: 'shadow-yellow-500/40', ring: 'ring-amber-200' },
            default:  { bg: 'bg-blue-500', grad: 'from-blue-400 to-indigo-600', shadow: 'shadow-blue-500/40', ring: 'ring-blue-200' }
        };
        let theme = colors[status] || colors.default;
        
        return L.divIcon({
            className: 'custom-div-icon',
            html: `
                <div class='relative group cursor-pointer'>
                    <div class='absolute -inset-3 ${theme.bg} rounded-full blur-md opacity-30 group-hover:opacity-60 transition-opacity ${status === "approved" ? "animate-pulse" : ""}'></div>
                    <div class='relative w-10 h-10 bg-gradient-to-tr ${theme.grad} rounded-full flex items-center justify-center shadow-lg ${theme.shadow} border-2 border-white transform transition-transform group-hover:scale-110'>
                        <span class="w-3 h-3 bg-white rounded-full opacity-90 shadow-inner"></span>
                    </div>
                </div>
            `,
            iconSize: [40, 40],
            iconAnchor: [20, 20],
            popupAnchor: [0, -20]
        });
    }

    // --- 3. Parse Data & Plot Markers ---
    const rawData = {!! json_encode(\App\Models\Lokasi::with(['statusLokasi', 'desa'])->select('id', 'nama_lokasi', 'alamat_lokasi', 'latitude', 'longitude', 'kapasitas_pengungsi', 'luas_lokasi', 'desa_id')->get()->map(function($item) {
        $statusRecord = $item->statusLokasi->last();
        $status = $statusRecord ? $statusRecord->status : 'pending';
        return [
            'id' => $item->id,
            'nama_lokasi' => $item->nama_lokasi,
            'alamat_lokasi' => $item->alamat_lokasi,
            'latitude' => $item->latitude,
            'longitude' => $item->longitude,
            'kapasitas' => $item->kapasitas_pengungsi,
            'luas' => $item->luas_lokasi,
            'desa' => $item->desa ? $item->desa->nama_desa : '-',
            'status' => $status
        ];
    })) !!};
    
    const bounds = L.latLngBounds();
    let hasMarkers = false;

    rawData.forEach(lokasi => {
        if (!lokasi.latitude || !lokasi.longitude) return;
        
        hasMarkers = true;
        let lat = parseFloat(lokasi.latitude);
        let lng = parseFloat(lokasi.longitude);
        bounds.extend([lat, lng]);

        let badgeStyle = '';
        let statusText = '';
        if(lokasi.status === 'approved') { badgeStyle = 'bg-emerald-100 text-emerald-700 border-emerald-200'; statusText = 'Disetujui'; }
        else if(lokasi.status === 'pending') { badgeStyle = 'bg-amber-100 text-amber-700 border-amber-200'; statusText = 'Pending'; }
        else if(lokasi.status === 'rejected') { badgeStyle = 'bg-rose-100 text-rose-700 border-rose-200'; statusText = 'Ditolak'; }
        else { badgeStyle = 'bg-slate-100 text-slate-700 border-slate-200'; statusText = 'Selesai'; }

        const popupHTML = `
            <div class="bg-white min-w-[280px]">
                <!-- Header -->
                <div class="p-4 bg-slate-50 border-b border-slate-100 relative">
                    <span class="absolute top-4 right-8 inline-block px-2 py-0.5 rounded-md text-[10px] font-bold border uppercase tracking-wider ${badgeStyle}">${statusText}</span>
                    <h3 class="font-black text-slate-800 text-base pr-20 leading-tight mb-1">${lokasi.nama_lokasi}</h3>
                    <p class="text-[11px] font-semibold text-slate-400 tracking-wide uppercase"><i class="fas fa-map-pin mr-1 text-slate-300"></i> ${lokasi.desa}</p>
                </div>
                <!-- Body -->
                <div class="p-4 space-y-3">
                    <p class="text-xs text-slate-600 leading-relaxed line-clamp-2" title="${lokasi.alamat_lokasi}">${lokasi.alamat_lokasi}</p>
                    
                    <div class="flex gap-2">
                        <div class="flex-1 bg-slate-50 rounded-lg p-2 border border-slate-100">
                            <p class="text-[10px] text-slate-400 font-semibold uppercase mb-0.5">Kapasitas</p>
                            <p class="text-sm font-bold text-slate-800">${lokasi.kapasitas} <span class="text-[10px] font-normal text-slate-500">org</span></p>
                        </div>
                        <div class="flex-1 bg-slate-50 rounded-lg p-2 border border-slate-100">
                            <p class="text-[10px] text-slate-400 font-semibold uppercase mb-0.5">Luas</p>
                            <p class="text-sm font-bold text-slate-800">${lokasi.luas} <span class="text-[10px] font-normal text-slate-500">m²</span></p>
                        </div>
                    </div>
                    
                    <a href="{{ url('admin/lokasi') }}/${lokasi.id}" class="mt-2 block w-full text-center bg-black-600 hover:bg-white-700 text-black text-xs font-bold py-2.5 px-4 rounded-xl transition-colors">
                        Kelola Data <i class="fas fa-arrow-right ml-1 opacity-70"></i>
                    </a>
                </div>
            </div>
        `;

        const marker = L.marker([lat, lng], {
            icon: createModernIcon(lokasi.status),
            riseOnHover: true
        }).addTo(map);
        
        marker.bindPopup(popupHTML);
    });

    // Auto-fit bounds if we have markers
    if (hasMarkers) {
        map.fitBounds(bounds, { padding: [50, 50], maxZoom: 15 });
    }

    // --- 4. User Location Functionality ---
    document.getElementById('btn-my-location').addEventListener('click', function() {
        const btn = this;
        const orgHtml = btn.innerHTML;
        
        btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Mencari...`;
        btn.disabled = true;

        if (!navigator.geolocation) {
            alert('Browser tidak mendukung geolokasi');
            btn.innerHTML = orgHtml;
            btn.disabled = false;
            return;
        }

        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                if (window.userLocMarker) { map.removeLayer(window.userLocMarker); }
                if (window.userLocCircle) { map.removeLayer(window.userLocCircle); }
                
                // Pulsing dot for user
                window.userLocMarker = L.marker([lat, lng], {
                    icon: L.divIcon({
                        className: '',
                        html: `
                            <div class="relative flex items-center justify-center w-8 h-8">
                                <div class="absolute inset-0 bg-blue-500 rounded-full opacity-40 animate-ping"></div>
                                <div class="relative w-4 h-4 bg-blue-600 border-2 border-white rounded-full shadow-md z-10"></div>
                            </div>
                        `,
                        iconSize: [32, 32],
                        iconAnchor: [16, 16]
                    }),
                    zIndexOffset: 1000
                }).addTo(map).bindPopup('<div class="px-2 py-1 font-bold text-sm">Posisi Anda</div>').openPopup();
                
                window.userLocCircle = L.circle([lat, lng], {
                    color: '#3b82f6',
                    weight: 1,
                    fillColor: '#3b82f6',
                    fillOpacity: 0.1,
                    radius: position.coords.accuracy
                }).addTo(map);

                map.flyTo([lat, lng], 14, { duration: 1.5 });
                
                btn.innerHTML = orgHtml;
                btn.disabled = false;
            },
            function(error) {
                alert('Gagal mendapatkan lokasi: ' + error.message);
                btn.innerHTML = orgHtml;
                btn.disabled = false;
            },
            { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
        );
    });
});
</script>
@endsection