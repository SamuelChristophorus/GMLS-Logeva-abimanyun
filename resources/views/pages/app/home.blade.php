@extends('layouts.app')

@section('title', 'Home')

@section('content')
<div class="container mx-auto px-4 py-8">

    <!-- HERO SECTION -->
    <div class="relative overflow-hidden bg-gradient-to-br from-gray-500 via-gray-800 to-black rounded-3xl p-8 md:p-12 mb-10 shadow-2xl">

        <!-- Blur Background -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute -top-20 -right-20 w-72 h-72 bg-white rounded-full blur-3xl"></div>
            <div class="absolute -bottom-20 -left-20 w-72 h-72 bg-white rounded-full blur-3xl"></div>
        </div>

        <div class="relative z-10 flex flex-col lg:flex-row items-center gap-10">

            <!-- Logo -->
            <div class="bg-white p-5 rounded-full shadow-2xl">
                <img src="{{ asset('assets/logo/logogmls.png') }}"
                     alt="logo"
                     class="w-28 h-28 md:w-36 md:h-36 object-contain">
            </div>

            <!-- Text -->
            <div class="text-white flex-1">

                <div class="inline-flex items-center px-4 py-2 rounded-full bg-white/20 backdrop-blur-sm text-sm mb-5">
                    GMLS Logeva
                </div>

                @auth
                <h2 class="text-4xl md:text-5xl font-extrabold leading-tight mb-4">
                    Hi, {{ Auth::user()->name }} 👋
                </h2>
                @endauth

                <h1 class="text-3xl md:text-5xl font-black leading-tight mb-5">
                    Pantau & Kelola <br>
                    Tempat Evakuasi Akhir
                </h1>

                <p class="text-gray-200 text-lg max-w-3xl leading-relaxed">
                    Sistem monitoring dan pengelolaan Tempat Evakuasi Akhir (TEA)
                    untuk membantu proses mitigasi bencana secara cepat, tepat,
                    dan terintegrasi.
                </p>

                <!-- Statistik Modern -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mt-8">

                    <!-- Total TEA -->
                    <div class="group relative overflow-hidden bg-white/95 backdrop-blur rounded-3xl p-6 shadow-xl border border-white/50">

                        <div class="absolute top-0 right-0 w-32 h-32 bg-red-100 rounded-full blur-3xl opacity-40"></div>

                        <div class="relative z-10 flex items-center justify-between">

                            <div>
                                <p class="text-sm font-semibold text-gray-500 mb-2">
                                    Total Lokasi TEA
                                </p>

                                <h3 class="text-5xl font-black text-red-600">
                                    {{ \App\Models\Lokasi::count() }}
                                </h3>

                                <p class="text-sm text-gray-400 mt-2">
                                    Titik evakuasi aktif
                                </p>
                            </div>

                            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-red-500 to-red-700 flex items-center justify-center shadow-lg">
                                <i class="fas fa-house-circle-check text-white text-2xl"></i>
                            </div>

                        </div>
                    </div>

                    <!-- Total Desa -->
                    <div class="group relative overflow-hidden bg-white/95 backdrop-blur rounded-3xl p-6 shadow-xl border border-white/50">

                        <div class="absolute bottom-0 left-0 w-32 h-32 bg-blue-100 rounded-full blur-3xl opacity-40"></div>

                        <div class="relative z-10 flex items-center justify-between">

                            <div>
                                <p class="text-sm font-semibold text-gray-500 mb-2">
                                    Total Desa
                                </p>

                                <h3 class="text-5xl font-black text-blue-600">
                                    {{ \App\Models\Desa::count() }}
                                </h3>

                                <p class="text-sm text-gray-400 mt-2">
                                    Wilayah terdata sistem
                                </p>
                            </div>

                            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-700 flex items-center justify-center shadow-lg">
                                <i class="fas fa-map-location-dot text-white text-2xl"></i>
                            </div>

                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <!-- INFORMASI SISTEM -->
    <div class="mt-10 bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden mb-10">

        <!-- Header -->
        <div class="px-8 py-6 border-b border-gray-100 bg-gradient-to-r from-red-50 to-white">

            <div class="flex items-center gap-4">

                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-red-500 to-red-700 flex items-center justify-center shadow-lg">
                    <i class="fas fa-circle-info text-white text-2xl"></i>
                </div>

                <div>
                    <h2 class="text-2xl font-bold text-gray-800">
                        Informasi Sistem TEA
                    </h2>

                    <p class="text-gray-500 mt-1">
                        Fitur utama yang tersedia pada sistem monitoring TEA.
                    </p>
                </div>

            </div>
        </div>

        <!-- Content -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 p-8">

            <!-- Card -->
            <div class="group p-6 rounded-3xl border border-gray-100 hover:border-red-200 hover:shadow-xl transition">

                <div class="w-14 h-14 rounded-2xl bg-red-100 text-red-600 flex items-center justify-center mb-5">
                    <i class="fas fa-map-marked-alt text-2xl"></i>
                </div>

                <h3 class="text-lg font-bold text-gray-800 mb-2">
                    Monitoring Lokasi
                </h3>

                <p class="text-sm text-gray-500 leading-relaxed">
                    Menampilkan persebaran lokasi Tempat Evakuasi Akhir secara realtime melalui peta interaktif.
                </p>

            </div>

            <!-- Card -->
            <div class="group p-6 rounded-3xl border border-gray-100 hover:border-blue-200 hover:shadow-xl transition">

                <div class="w-14 h-14 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center mb-5">
                    <i class="fas fa-users text-2xl"></i>
                </div>

                <h3 class="text-lg font-bold text-gray-800 mb-2">
                    Data Pengungsi
                </h3>

                <p class="text-sm text-gray-500 leading-relaxed">
                    Mengelola data pengungsi dan kebutuhan lapangan dalam proses evakuasi.
                </p>

            </div>

            <!-- Card -->
            <div class="group p-6 rounded-3xl border border-gray-100 hover:border-green-200 hover:shadow-xl transition">

                <div class="w-14 h-14 rounded-2xl bg-green-100 text-green-600 flex items-center justify-center mb-5">
                    <i class="fas fa-boxes-stacked text-2xl"></i>
                </div>

                <h3 class="text-lg font-bold text-gray-800 mb-2">
                    Manajemen Logistik
                </h3>

                <p class="text-sm text-gray-500 leading-relaxed">
                    Memantau kebutuhan logistik seperti makanan, obat-obatan, dan perlengkapan pengungsi.
                </p>

            </div>

            <!-- Card -->
            <div class="group p-6 rounded-3xl border border-gray-100 hover:border-purple-200 hover:shadow-xl transition">

                <div class="w-14 h-14 rounded-2xl bg-purple-100 text-purple-600 flex items-center justify-center mb-5">
                    <i class="fas fa-chart-line text-2xl"></i>
                </div>

                <h3 class="text-lg font-bold text-gray-800 mb-2">
                    Statistik & Laporan
                </h3>

                <p class="text-sm text-gray-500 leading-relaxed">
                    Menyediakan visualisasi data dan laporan kondisi lokasi evakuasi secara cepat dan akurat.
                </p>

            </div>

        </div>

    </div>

    <!-- MAP -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">

        <!-- Header -->
        <div class="p-6 border-b border-gray-100">

            <div>
                <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-map text-red-500 mr-3"></i>
                    Peta Persebaran TEA
                </h2>

                <p class="text-gray-500 mt-1">
                    Menampilkan
                    <span class="font-semibold text-red-600">
                        {{ \App\Models\Lokasi::count() }}
                    </span>
                    Tempat Evakuasi Akhir (TEA) terdaftar
                </p>
            </div>

        </div>

        <!-- MAP -->
        <div class="p-4">
            <div id="map"
                 class="h-[550px] w-full rounded-2xl border border-gray-200 shadow-inner">
            </div>
        </div>

    </div>
<!-- Footer -->
<footer class="mt-10 px-4">
    <div class="rounded-[32px] overflow-hidden bg-[linear-gradient(135deg,#6b7280_0%,#334155_50%,#020617_100%)] text-white shadow-xl">

        <!-- About Us Button -->
        <div class="text-center py-6 border-b border-white/10">
            <button
                onclick="toggleAboutUs()"
                class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-white/10 hover:bg-white/20 backdrop-blur-sm font-semibold transition">

                <i class="fas fa-circle-info"></i>
                About Us

                <i id="arrowIcon"
                   class="fas fa-chevron-down transition-transform duration-300"></i>
            </button>
        </div>

        <!-- About Content -->
        <div id="aboutUsContent" class="hidden">

            <div class="max-w-5xl mx-auto p-8">

                <!-- Tentang Sistem -->
                <div class="text-center mb-10">
                    <h3 class="text-3xl font-bold mb-4">
                        Tentang Sistem
                    </h3>

                    <p class="text-slate-300 leading-relaxed max-w-3xl mx-auto">
                        GMLS Logeva merupakan platform
                        yang dirancang untuk membantu pengelolaan Tempat Evakuasi Akhir (TEA),
                        pemantauan ketersediaan logistik, serta penyajian informasi mitigasi
                        bencana secara terintegrasi, cepat, dan akurat.
                    </p>
                </div>

                <!-- Tim Pengembang -->
                <div class="mb-10">
                    <h4 class="text-2xl font-bold text-center mb-6">
                        Tim Pengembang Website
                    </h4>

                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">

    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-5 text-center">
        <img src="{{ asset('assets/team/anis.jpeg') }}"
             alt="Anis"
             class="w-24 h-24 mx-auto mb-4 rounded-full object-cover border-4 border-white/20 shadow-lg">
        <h5 class="font-semibold text-white text-sm">
            Anis Faisal Reza
        </h5>
    </div>

    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-5 text-center">
        <img src="{{ asset('assets/team/zedro.jpeg') }}"
             alt="Zedro"
             class="w-24 h-24 mx-auto mb-4 rounded-full object-cover border-4 border-white/20 shadow-lg">
        <h5 class="font-semibold text-white text-sm">
            Zedro Deniro Mason
        </h5>
    </div>

    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-5 text-center">
        <img src="{{ asset('assets/team/prajna.jpeg') }}"
             alt="Prajna"
             class="w-24 h-24 mx-auto mb-4 rounded-full object-cover border-4 border-white/20 shadow-lg">
        <h5 class="font-semibold text-white text-sm">
            Prajna Ananda Citra
        </h5>
    </div>

            <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-5 text-center">
        <img src="{{ asset('assets/team/melati.jpeg') }}"
             alt="melati"
             class="w-24 h-24 mx-auto mb-4 rounded-full object-cover border-4 border-white/20 shadow-lg">
        <h5 class="font-semibold text-white text-sm">
                            <h5 class="font-semibold">Melati Octaviana Purba</h5>
                        </div>

                        <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-5 text-center">
        <img src="{{ asset('assets/team/melisa.jpeg') }}"
             alt="melisa"
             class="w-24 h-24 mx-auto mb-4 rounded-full object-cover border-4 border-white/20 shadow-lg">
        <h5 class="font-semibold text-white text-sm">
                            <h5 class="font-semibold">Melisa Ashley</h5>
                        </div>

                        <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-5 text-center">
        <img src="{{ asset('assets/team/farrel.jpeg') }}"
             alt="farrel"
             class="w-24 h-24 mx-auto mb-4 rounded-full object-cover border-4 border-white/20 shadow-lg">
        <h5 class="font-semibold text-white text-sm">
                            <h5 class="font-semibold">Farrel Safwan</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Copyright -->
        <div class="border-t border-white/10 py-5 text-center">
            <p class="text-sm text-slate-300">
                © 2025 GMLS. All Rights Reserved.
            </p>
        </div>

    </div>
</footer>

<script>
function toggleAboutUs() {
    const content = document.getElementById('aboutUsContent');
    const arrow = document.getElementById('arrowIcon');

    content.classList.toggle('hidden');
    arrow.classList.toggle('rotate-180');
}
</script>
</div>
@endsection

@section('scripts')

<!-- LEAFLET -->
<link rel="stylesheet"
      href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // INIT MAP
    const map = L.map('map').setView([-2.5489, 118.0149], 5);

    // TILE
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 18
    }).addTo(map);

    // DATA LOKASI
    const lokasiData = {!! json_encode(
        \App\Models\Lokasi::select(
            'id',
            'nama_lokasi',
            'alamat_lokasi',
            'latitude',
            'longitude',
            'kapasitas_pengungsi'
        )->get()
    ) !!};

    const markers = [];

    // ICON TEA
    const teaIcon = L.divIcon({
        className: '',
        html: `
            <div class="relative flex items-center justify-center">

                <div class="absolute w-10 h-10 bg-red-500 rounded-full opacity-20 animate-pulse"></div>

                <div class="w-5 h-5 bg-red-600 border-2 border-white rounded-full shadow-lg z-10"></div>

            </div>
        `,
        iconSize: [20, 20],
        iconAnchor: [10, 10]
    });

    // LOOP MARKER
    lokasiData.forEach(lokasi => {

        if (lokasi.latitude && lokasi.longitude) {

            const marker = L.marker(
                [
                    parseFloat(lokasi.latitude),
                    parseFloat(lokasi.longitude)
                ],
                {
                    icon: teaIcon
                }
            ).addTo(map);

            // POPUP
            marker.bindPopup(`
                <div class="w-56">

                    <div class="inline-flex items-center px-2 py-1 rounded-full bg-red-100 text-red-700 text-[11px] font-bold mb-2">
                        Tempat Evakuasi Akhir
                    </div>

                    <h3 class="font-bold text-gray-800 text-base">
                        ${lokasi.nama_lokasi}
                    </h3>

                    <p class="text-sm text-gray-600 mt-1">
                        ${lokasi.alamat_lokasi}
                    </p>

                    <div class="mt-3 text-sm text-gray-700">
                        Kapasitas:
                        ${lokasi.kapasitas_pengungsi} orang
                    </div>

                    <a href="/logeva/lokasi/${lokasi.id}"
                       class="inline-block mt-3 text-red-600 font-semibold text-sm">

                       Lihat Detail →

                    </a>

                </div>
            `);

            markers.push([
                parseFloat(lokasi.latitude),
                parseFloat(lokasi.longitude)
            ]);
        }
    });

    // FIT BOUNDS
    if (markers.length > 0) {

        map.fitBounds(markers, {
            padding: [50, 50]
        });

    }

});
</script>

@endsection