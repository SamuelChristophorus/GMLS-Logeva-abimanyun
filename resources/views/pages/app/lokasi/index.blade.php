@extends('layouts.app')

@section('title', 'Daftar Lokasi')

@section('content')
<div class="container mx-auto px-4 py-8">

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight mb-2">
                Daftar Lokasi Pengungsian
            </h1>

            <p class="text-gray-500 text-sm">
                Temukan lokasi pengungsian terdekat dan teraman di sekitar anda.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3 mt-4 md:mt-0">

            <span class="bg-blue-50 text-blue-700 px-4 py-2 rounded-xl text-sm font-semibold border border-blue-100 shadow-sm flex items-center">
                <i class="fas fa-map-marked-alt mr-2"></i>
                {{ $lokasis->count() }} Lokasi Tersedia
            </span>

            @if(request()->desa)
            <div class="bg-indigo-50 text-indigo-700 px-4 py-2 rounded-xl text-sm font-semibold border border-indigo-100 shadow-sm flex items-center">
                <i class="fas fa-filter mr-2"></i>
                {{ request()->desa }}
            </div>
            @endif
            
        </div>
    </div>

    <!-- Locations Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

        @foreach ($lokasis as $lokasi)

        <div class="group bg-white rounded-3xl overflow-hidden shadow-md hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-50 flex flex-col h-full relative">

            <!-- Image Area -->
            <div class="relative h-56 overflow-hidden">

               <img src="{{ asset('storage/'. $lokasi->gambar_lokasi) }}"
                     alt="{{ $lokasi->nama_lokasi }}"
                     class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">

                <!-- Gradient -->
                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>

                <!-- Distance Badge -->
                <div class="absolute top-4 left-4 bg-white/95 backdrop-blur-sm text-blue-700 px-3 py-1.5 rounded-lg text-xs font-bold shadow-lg flex items-center border border-white/50">

                    <i class="fas fa-location-arrow mr-1.5 text-blue-500"></i>

                    {{ number_format($lokasi->distance ?? 0, 1) }} km

                </div>

            </div>

            <!-- Content -->
            <div class="p-6 flex flex-col flex-grow relative bg-white">

                <!-- Title -->
                <h3 class="text-xl font-extrabold text-gray-800 mb-2 line-clamp-1 group-hover:text-blue-600 transition-colors">
                    {{ $lokasi->nama_lokasi }}
                </h3>

                <!-- Address -->
                <div class="flex items-start text-gray-500 mb-4 h-10">

                    <i class="fas fa-map-marker-alt text-red-500 mr-2.5 mt-1 text-sm"></i>

                    <p class="text-sm line-clamp-2 leading-relaxed">
                        {{ $lokasi->alamat_lokasi }}
                    </p>

                </div>

                <!-- Spacer -->
                <div class="flex-grow"></div>

                <!-- BUTTON DETAIL -->
                <div class="mt-4 flex gap-2">

                    <!-- DETAIL -->
                    <a href="{{ route('lokasi.show', $lokasi->id) }}"
                       class="flex-1 text-center bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold py-2 rounded-xl transition">

                        Detail

                    </a>                 

                </div>

                <!-- Footer -->
                <div class="flex justify-between items-center pt-4 border-t border-gray-100 mt-4">

                    <!-- Desa -->
                    <div class="flex items-center text-xs font-medium text-gray-500 bg-gray-50 px-2.5 py-1.5 rounded-lg shadow-inner">

                        <i class="fas fa-map mr-1.5 text-indigo-400"></i>

                        {{ $lokasi->desa->nama_desa ?? 'Desa Terdata' }}

                    </div>

                    <!-- Date -->
                    <div class="flex items-center text-xs text-gray-400">

                        <i class="far fa-clock mr-1.5"></i>

                        {{ \Carbon\Carbon::parse($lokasi->created_at)->timezone('Asia/Jakarta')->diffForHumans() }}

                    </div>

                </div>

            </div>
        </div>

        @endforeach

    </div>

    <!-- Empty State -->
    @if($lokasis->count() == 0)

    <div class="flex flex-col items-center justify-center py-20 px-4 bg-white rounded-3xl border border-gray-100 shadow-sm mt-8">

        <div class="w-32 h-32 bg-blue-50 text-blue-300 rounded-full flex items-center justify-center mb-6 shadow-inner">

            <i class="fas fa-search-location text-5xl"></i>

        </div>

        <h3 class="text-2xl font-bold text-gray-800 mb-2 text-center">
            Belum Ada Lokasi Ditemukan
        </h3>

        <p class="text-gray-500 text-center max-w-md mb-8">
            Kami tidak dapat menemukan lokasi pengungsian dengan filter saat ini.
            Silakan coba mengubah wilayah atau kriteria pencarian Anda.
        </p>

        <button onclick="window.location.href='{{ route('lokasi.index') }}'"
                class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-xl shadow-md transition-colors flex items-center">

            <i class="fas fa-sync-alt mr-2"></i>

            Reset Filter Pencarian

        </button>

    </div>

    @endif

</div>

<script>

window.onload = function () {

    // cek apakah browser support GPS
    if (navigator.geolocation) {

        navigator.geolocation.getCurrentPosition(

            function(position) {

                let latitude = position.coords.latitude;
                let longitude = position.coords.longitude;

                console.log("Latitude:", latitude);
                console.log("Longitude:", longitude);

                let url = new URL(window.location.href);

                // supaya tidak reload terus
                if (!url.searchParams.has('latitude')) {

                    url.searchParams.set('latitude', latitude);
                    url.searchParams.set('longitude', longitude);
                    url.searchParams.set('radius', 10);

                    window.location.href = url.toString();
                }

            },

            function(error) {

                console.log(error);

                alert("Izinkan akses lokasi agar jarak dapat dihitung");

            }

        );

    } else {

        alert("Browser tidak mendukung GPS");

    }

}

</script>
@endsection