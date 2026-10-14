@extends('layouts.dashboard')

@section('title', 'Monitoring Lokasi')

@php
    $semuaLokasi = \App\Models\Lokasi::select(
        'id', 'nama_lokasi', 'alamat_lokasi', 'latitude', 'longitude', 'kapasitas_pengungsi'
    )->get();

    $totalTea       = $semuaLokasi->count();
    $totalDesa      = \App\Models\Desa::count();
    $totalKapasitas = $semuaLokasi->sum('kapasitas_pengungsi');

    $tim = [
        ['anis.jpeg',   'Anis Faisal Reza'],
        ['zedro.jpeg',  'Zedro Deniro Mason'],
        ['prajna.jpeg', 'Prajna Ananda Citra'],
        ['melati.jpeg', 'Melati Octaviana Purba'],
        ['melisa.jpeg', 'Melisa Ashley'],
        ['farrel.jpeg', 'Farrel Safwan'],
    ];
@endphp

@section('content')
<div class="space-y-6">

    {{-- ===== Judul halaman ===== --}}
    <div>
        @auth
            <p class="text-sm text-slate-500">Hi, {{ Auth::user()->name }} 👋</p>
        @endauth
        <h1 class="text-2xl font-black text-slate-900">Pantau &amp; Kelola Tempat Evakuasi Akhir</h1>
        <p class="text-sm text-slate-500 mt-1">
            Sistem monitoring dan pengelolaan TEA untuk membantu mitigasi bencana secara cepat, tepat, dan terintegrasi.
        </p>
    </div>

    {{-- ===== Statistik ===== --}}
    <section id="statistik" class="grid grid-cols-1 sm:grid-cols-3 gap-4 scroll-mt-24">
        <div class="bg-white rounded-2xl border border-slate-200 p-5 flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-slate-500">Total Lokasi TEA</p>
                <p class="text-3xl font-black text-red-600 mt-1">{{ $totalTea }}</p>
                <p class="text-xs text-slate-400 mt-1">Titik evakuasi aktif</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-red-600 text-white flex items-center justify-center text-xl">
                <i class="fas fa-house-circle-check"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-slate-500">Total Desa</p>
                <p class="text-3xl font-black text-blue-600 mt-1">{{ $totalDesa }}</p>
                <p class="text-xs text-slate-400 mt-1">Wilayah terdata sistem</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-600 text-white flex items-center justify-center text-xl">
                <i class="fas fa-map-location-dot"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-slate-500">Total Kapasitas</p>
                <p class="text-3xl font-black text-green-600 mt-1">{{ number_format($totalKapasitas, 0, ',', '.') }}</p>
                <p class="text-xs text-slate-400 mt-1">Orang dapat ditampung</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-green-600 text-white flex items-center justify-center text-xl">
                <i class="fas fa-people-roof"></i>
            </div>
        </div>
    </section>

    {{-- ===== Peta ===== --}}
    <section class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                <i class="fas fa-map text-red-500"></i> Peta Persebaran TEA
            </h2>
            <p class="text-sm text-slate-500">
                Menampilkan <span class="font-semibold text-red-600">{{ $totalTea }}</span>
                Tempat Evakuasi Akhir (TEA) terdaftar
            </p>
        </div>
        <div class="p-4">
            <div id="map" class="h-[460px] md:h-[560px] w-full rounded-xl border border-slate-200"></div>
        </div>
    </section>

    {{-- ===== About Us ===== --}}
    <footer class="bg-slate-900 text-white rounded-2xl overflow-hidden" x-data="{ about: false }">
        <div class="text-center py-4 border-b border-white/10">
            <button @click="about = !about"
                    class="inline-flex items-center gap-2 px-5 py-2 rounded-full bg-white/10 hover:bg-white/20 text-sm font-semibold transition">
                <i class="fas fa-circle-info"></i> About Us
                <i class="fas fa-chevron-down transition-transform" :class="{ 'rotate-180': about }"></i>
            </button>
        </div>

        <div x-show="about" x-cloak x-transition class="max-w-5xl mx-auto p-6 md:p-8">
            <div class="text-center mb-8">
                <h3 class="text-2xl font-bold mb-3">Tentang Sistem</h3>
                <p class="text-slate-300 max-w-3xl mx-auto">
                    GMLS Logeva merupakan platform yang dirancang untuk membantu pengelolaan Tempat Evakuasi Akhir (TEA),
                    pemantauan ketersediaan logistik, serta penyajian informasi mitigasi bencana secara terintegrasi,
                    cepat, dan akurat.
                </p>
            </div>

            <h4 class="text-xl font-bold text-center mb-5">Tim Pengembang Website</h4>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                @foreach ($tim as [$foto, $nama])
                    <div class="bg-white/10 rounded-2xl p-4 text-center">
                        <img src="{{ asset('assets/team/' . $foto) }}" alt="{{ $nama }}"
                             class="w-20 h-20 mx-auto mb-3 rounded-full object-cover border-4 border-white/20">
                        <h5 class="font-semibold text-sm">{{ $nama }}</h5>
                    </div>
                @endforeach
            </div>
        </div>

        <p class="border-t border-white/10 py-4 text-center text-sm text-slate-400">
            © {{ date('Y') }} GMLS. All Rights Reserved.
        </p>
    </footer>

</div>
@endsection

@section('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const lokasiData = @json($semuaLokasi);
    const detailBase = @json(url('lokasi'));   // otomatis ikut /logeva di hosting

    const map = L.map('map').setView([-6.93, 106.12], 11);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 18
    }).addTo(map);

    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    const teaIcon = L.divIcon({
        className: '',
        html: `
            <div class="relative flex items-center justify-center">
                <div class="absolute w-10 h-10 bg-red-500 rounded-full opacity-20 animate-pulse"></div>
                <div class="w-5 h-5 bg-red-600 border-2 border-white rounded-full shadow-lg z-10"></div>
            </div>`,
        iconSize: [20, 20],
        iconAnchor: [10, 10]
    });

    const markers = [];

    lokasiData.forEach(lokasi => {
        const lat = parseFloat(lokasi.latitude);
        const lng = parseFloat(lokasi.longitude);
        if (isNaN(lat) || isNaN(lng)) return;

        const marker = L.marker([lat, lng], { icon: teaIcon }).addTo(map);

        marker.bindPopup(`
            <div class="w-56">
                <div class="inline-flex px-2 py-1 rounded-full bg-red-100 text-red-700 text-[11px] font-bold mb-2">
                    Tempat Evakuasi Akhir
                </div>
                <h3 class="font-bold text-gray-800 text-base">${esc(lokasi.nama_lokasi)}</h3>
                <p class="text-sm text-gray-600 mt-1">${esc(lokasi.alamat_lokasi)}</p>
                <div class="mt-3 text-sm text-gray-700">Kapasitas: ${esc(lokasi.kapasitas_pengungsi)} orang</div>
                <a href="${detailBase}/${lokasi.id}" class="inline-block mt-3 text-red-600 font-semibold text-sm">
                    Lihat Detail →
                </a>
            </div>
        `);

        markers.push({ nama: String(lokasi.nama_lokasi ?? '').toLowerCase(), marker });
    });

    if (markers.length) {
        map.fitBounds(L.featureGroup(markers.map(m => m.marker)).getBounds(), { padding: [50, 50] });
    }

    setTimeout(() => map.invalidateSize(), 300);

    // Pencarian dari kotak "Cari lokasi" di topbar (tekan Enter)
    window.addEventListener('tea-search', (e) => {
        const q = String(e.detail || '').trim().toLowerCase();
        if (!q) return;
        const hit = markers.find(m => m.nama.includes(q));
        if (hit) {
            map.flyTo(hit.marker.getLatLng(), 16);
            hit.marker.openPopup();
        } else {
            alert('Lokasi "' + e.detail + '" tidak ditemukan');
        }
    });
});
</script>
@endsection