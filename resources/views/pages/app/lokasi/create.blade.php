@extends('layouts.dashboard')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
@endpush

@section('title', 'Lapor Posko Baru')

@section('content')

    <!-- Header Section -->
    <div class="max-w-4xl mx-auto mb-8 lg:mb-12">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-sm font-bold text-slate-500 hover:text-blue-600 transition-colors mb-6 group bg-white/50 backdrop-blur-sm px-4 py-2 rounded-full border border-slate-100 w-fit">
            <div class="w-6 h-6 rounded-full bg-white shadow-sm flex items-center justify-center text-slate-400 group-hover:text-blue-500 transition-colors">
                <i class="fas fa-arrow-left text-[10px]"></i>
            </div>
            Kembali ke Daftar
        </a>

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <h1 class="text-3xl md:text-4xl font-extrabold text-slate-800 tracking-tight flex items-center gap-3">
                    Lapor Posko Baru
                    <span class="relative flex h-3 w-3 mt-1">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                    </span>
                </h1>
                <p class="text-slate-500 mt-2 font-medium text-sm md:text-base max-w-xl">Lengkapi data profil pengungsian di lapangan untuk memfasilitasi kebutuhan dasar pengungsi.</p>
            </div>
        </div>
    </div>

    <!-- Form Section -->
    <div class="max-w-4xl mx-auto relative">
        <form action="{{route('lokasi.store')}}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            {{-- Penanggung jawab posko: relawan = akun sendiri, admin = pilih relawan --}}
            @php
                $relawan = auth()->user()?->relawan;
                $isAdmin = auth()->user()?->hasRole('admin');
            @endphp

            @if ($isAdmin)
                <div class="bg-white/60 backdrop-blur-xl border border-white/80 rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] p-6 md:p-8">
                    <label for="relawan_id" class="block text-sm font-bold text-slate-700 mb-1">
                        Relawan Penanggung Jawab <span class="text-rose-500">*</span>
                    </label>
                    <p class="text-xs text-slate-500 mb-3">Anda masuk sebagai admin, pilih relawan yang akan mengelola posko ini.</p>
                    <div class="relative">
                        <i class="fas fa-user-shield absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                        <select name="relawan_id" id="relawan_id"
                                class="w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 @error('relawan_id') border-rose-500 @enderror">
                            <option value="" disabled {{ old('relawan_id') ? '' : 'selected' }}>-- Pilih Relawan --</option>
                            @foreach ($relawans as $r)
                                <option value="{{ $r->id }}" @selected(old('relawan_id') == $r->id)>{{ $r->user->name }} ({{ $r->user->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    @error('relawan_id')
                        <p class="mt-1.5 text-xs font-bold text-rose-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                    @enderror
                </div>
            @elseif ($relawan)
                <input type="hidden" name="relawan_id" value="{{ $relawan->id }}">
            @else
                <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-600 text-sm font-semibold">
                    <i class="fas fa-circle-exclamation mr-1"></i>
                    Akun Anda belum terdaftar sebagai relawan, sehingga belum dapat melaporkan posko. Hubungi admin untuk didaftarkan.
                </div>
            @endif

            <!-- 1. Informasi Dasar -->
            <div class="bg-white/60 backdrop-blur-xl border border-white/80 rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden">
                <div class="p-6 md:p-8">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-800">Informasi Dasar</h3>
                            <p class="text-xs text-slate-500 font-medium">Identitas lokasi pengungsian</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Nama Lokasi -->
                        <div class="relative group">
                            <label for="nama_lokasi" class="block text-sm font-bold text-slate-700 mb-2">Nama Posko/Lokasi <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="fas fa-campground text-slate-400 group-focus-within:text-blue-500 transition-colors"></i>
                                </div>
                                <input type="text" id="nama_lokasi" name="nama_lokasi" value="{{old('nama_lokasi')}}" required
                                    class="w-full pl-11 pr-4 py-3 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-medium text-slate-700 @error('nama_lokasi') border-rose-500 focus:ring-rose-500/10 focus:border-rose-500 @enderror"
                                    placeholder="Contoh: Posko Utama Balai Desa">
                            </div>
                            @error('nama_lokasi')
                                <p class="mt-1.5 text-xs font-bold text-rose-500 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Desa -->
                        <div class="relative group">
                            <label for="desa_id" class="block text-sm font-bold text-slate-700 mb-2">Wilayah Desa <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="fas fa-map text-slate-400 group-focus-within:text-emerald-500 transition-colors"></i>
                                </div>
                                <select name="desa_id" id="desa_id" required
                                    class="w-full pl-11 pr-4 py-3 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all font-medium text-slate-700 appearance-none @error('desa_id') border-rose-500 focus:ring-rose-500/10 focus:border-rose-500 @enderror">
                                    <option value="" disabled selected>Pilih Desa/Kelurahan...</option>
                                    @foreach ($desas as $desa)
                                        <option value="{{$desa->id}}" @if (old('desa_id') == $desa->id) selected @endif>
                                            {{$desa->nama_desa}}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-500">
                                    <i class="fas fa-chevron-down text-xs"></i>
                                </div>
                            </div>
                            @error('desa_id')
                                <p class="mt-1.5 text-xs font-bold text-rose-500 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Alamat -->
                        <div class="md:col-span-2 relative group">
                            <label for="alamat_lokasi" class="block text-sm font-bold text-slate-700 mb-2">Alamat Lengkap <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <div class="absolute top-3.5 left-4 pointer-events-none">
                                    <i class="fas fa-map-marker-alt text-slate-400 group-focus-within:text-blue-500 transition-colors"></i>
                                </div>
                                <textarea id="alamat_lokasi" name="alamat_lokasi" rows="2" required
                                    class="w-full pl-11 pr-4 py-3 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-medium text-slate-700 resize-none @error('alamat_lokasi') border-rose-500 focus:ring-rose-500/10 focus:border-rose-500 @enderror"
                                    placeholder="Jl. Raya Desa No. 123, RT 01 / RW 02">{{old('alamat_lokasi')}}</textarea>
                            </div>
                            @error('alamat_lokasi')
                                <p class="mt-1.5 text-xs font-bold text-rose-500 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                            @enderror
                        </div>
                        
                        <!-- Koordinat MAP INTERACTIVE -->
                        <div class="md:col-span-2 pt-6 border-t border-slate-100 mt-2">
                            
                            <!-- WRAPPER UTAMA -->
                            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 items-stretch">

                                <!-- PANEL INFO -->
                                <div class="lg:col-span-2 bg-white/60 backdrop-blur-xl border border-white/80 rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] p-6 flex flex-col justify-between min-h-[380px]">

                                    <div>
                                        <div class="flex items-center gap-3 mb-5">
                                            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                                <i class="fas fa-map-marked-alt"></i>
                                            </div>

                                            <div>
                                                <h3 class="text-lg font-bold text-slate-800">
                                                    Lokasi GPS
                                                </h3>
                                                <p class="text-xs text-slate-500">
                                                    Pilih titik lokasi posko
                                                </p>
                                            </div>
                                        </div>

                                        <div class="space-y-4">

                                            <!-- Latitude -->
                                            <div>
                                                <label for="latitude" class="block text-sm font-bold text-slate-700 mb-2">
                                                    Latitude
                                                </label>

                                                <input
                                                    type="text"
                                                    id="latitude"
                                                    name="latitude"
                                                    value="{{old('latitude')}}"
                                                    required
                                                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all font-mono text-sm"
                                                    placeholder="-6.123456"
                                                >
                                            </div>

                                            <!-- Longitude -->
                                            <div>
                                                <label for="longitude" class="block text-sm font-bold text-slate-700 mb-2">
                                                    Longitude
                                                </label>

                                                <input
                                                    type="text"
                                                    id="longitude"
                                                    name="longitude"
                                                    value="{{old('longitude')}}"
                                                    required
                                                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all font-mono text-sm"
                                                    placeholder="106.123456"
                                                >
                                            </div>

                                            <!-- INFO -->
                                            <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-4">
                                                <div class="flex items-start gap-3">
                                                    <div class="text-indigo-500 mt-0.5">
                                                        <i class="fas fa-info-circle"></i>
                                                    </div>

                                                    <div>
                                                        <h4 class="text-sm font-bold text-indigo-700 mb-1">
                                                            Petunjuk
                                                        </h4>

                                                        <p class="text-xs text-indigo-600 leading-relaxed">
                                                            Klik map atau drag marker untuk menentukan lokasi posko secara akurat.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>

                                </div>

                                <!-- MAP -->
                                <div class="lg:col-span-3">
                                    <label class="block text-sm font-bold text-slate-700 mb-2">
                                        Pilih Lokasi di Peta
                                        <span class="text-rose-500">*</span>
                                    </label>

                                    <div class="rounded-3xl overflow-hidden border border-slate-200 shadow-[0_8px_30px_rgb(0,0,0,0.08)] bg-white">
                                        <div id="map" class="w-full h-[420px]"></div>
                                    </div>

                                    <p class="mt-3 text-xs font-medium text-slate-500">
                                        Klik map atau geser marker untuk memperbarui koordinat otomatis.
                                    </p>
                                </div>

                            </div>

                        </div>

                    </div>{{-- /grid --}}
                </div>{{-- /p-6 --}}
            </div>{{-- /1. Informasi Dasar --}}

            <!-- 2. Dimensi & Kapasitas & Media -->
            <div class="flex flex-col gap-6">
                
                <!-- Kapasitas Wrapper (Left, 3 cols) -->
                <div class="lg:col-span-3 bg-white/60 backdrop-blur-xl border border-white/80 rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden">
                    <div class="p-6 md:p-8">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                                <i class="fas fa-ruler-combined"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-800">Dimensi & Kapasitas</h3>
                                <p class="text-xs text-slate-500 font-medium">Pengukuran luas pengungsian</p>
                            </div>
                        </div>

                        <div class="flex flex-col gap-6">
                            <!-- Luas -->
                            <div class="relative group">
                                <label for="luas_lokasi" class="block text-sm font-bold text-slate-700 mb-2">Estimasi Luas Area <span class="text-rose-500">*</span></label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <i class="fas fa-expand-arrows-alt text-slate-400 group-focus-within:text-purple-500 transition-colors"></i>
                                    </div>
                                    <input type="number" step="0.01" id="luas_lokasi" name="luas_lokasi" value="{{old('luas_lokasi')}}" required
                                        class="w-full pl-11 pr-16 py-3 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all font-bold text-lg text-slate-700 @error('luas_lokasi') border-rose-500 @enderror"
                                        placeholder="0" oninput="hitungSemua()">
                                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-400 font-bold">
                                        m&sup2;
                                    </div>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-2"><i class="fas fa-info-circle mr-1"></i> Masukkan luas total area yang bisa dihuni pengungsi.</p>
                                    <div class="mt-4 bg-amber-50 border border-amber-200 rounded-2xl p-4">
                                        <div class="flex items-start gap-3">

                                            <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-exclamation-triangle text-sm"></i>
                                            </div>

                                            <div>
                                                <h4 class="text-sm font-bold text-amber-700 mb-1">
                                                    Pastikan Data Luas Area Akurat
                                                </h4>

                                                <p class="text-xs text-amber-600 leading-relaxed">
                                                    Luas area akan digunakan untuk menghitung estimasi kapasitas pengungsi,
                                                    kebutuhan air bersih, sanitasi, serta standar kebutuhan dasar lainnya
                                                    secara otomatis berdasarkan Standar Sphere.
                                                </p>
                                            </div>

                                        </div>
                                    </div>
                                @error('luas_lokasi')
                                    <p class="mt-1.5 text-xs font-bold text-rose-500 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Kapasitas -->
                            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 shadow-md relative overflow-hidden group border border-slate-700">
                                <div class="absolute top-0 right-0 -mt-4 -mr-4 text-slate-700/50 group-hover:text-slate-700 group-hover:scale-110 transition-all duration-500">
                                    <i class="fas fa-users text-8xl"></i>
                                </div>
                                <div class="relative z-10 grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
                                    <div>
                                        <label for="kapasitas_pengungsi" class="block text-sm font-bold text-slate-300 mb-1">Maks. Kapasitas Daya Tampung</label>
                                        <p class="text-[11px] text-slate-400 bg-slate-800/50 inline-block px-2 py-0.5 rounded border border-slate-700">Standar aman: 3.5 m&sup2; per jiwa</p>
                                    </div>
                                    <div class="relative">
                                        <input type="number" id="kapasitas_pengungsi" name="kapasitas_pengungsi" value="{{old('kapasitas_pengungsi')}}"
                                            class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 text-white rounded-xl focus:outline-none font-black text-2xl tracking-wider @error('kapasitas_pengungsi') border-rose-500 @enderror"
                                            readonly placeholder="0">
                                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold">JIWA</span>
                                    </div>
                                </div>
                                @error('kapasitas_pengungsi')
                                    <p class="mt-2 text-xs font-bold text-rose-400 relative z-10">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gambar Lokasi (Right, 2 cols) -->
                <div class="lg:col-span-2 bg-white/60 backdrop-blur-xl border border-white/80 rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden flex flex-col">
                    <div class="p-6 md:p-8 flex-1 flex flex-col">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                                <i class="fas fa-camera"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-800">Foto Posko</h3>
                            </div>
                        </div>

                        <div class="flex-1 flex flex-col justify-center">
                            <label for="gambar_lokasi" class="group relative flex flex-col items-center justify-center w-full h-48 sm:h-full min-h-[16rem] border-2 border-slate-200 border-dashed rounded-2xl cursor-pointer bg-slate-50/50 hover:bg-slate-50 hover:border-amber-400 transition-all overflow-hidden @error('gambar_lokasi') border-rose-500 @enderror">
                                
                                <img id="previewImage" src="#" alt="Preview" class="absolute inset-0 w-full h-full object-cover hidden z-10">
                                <div id="previewOverlay" class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm hidden z-20 flex flex-col items-center justify-center text-white opacity-0 group-hover:opacity-100 transition-opacity">
                                    <i class="fas fa-exchange-alt text-2xl mb-2"></i>
                                    <span class="font-bold text-sm">Ganti Foto</span>
                                </div>

                                <div id="uploadPlaceholder" class="flex flex-col items-center justify-center pt-5 pb-6 px-4 text-center z-0">
                                    <div class="w-16 h-16 rounded-full bg-white shadow-sm flex items-center justify-center text-slate-400 mb-3 group-hover:scale-110 group-hover:text-amber-500 transition-transform">
                                        <i class="fas fa-cloud-upload-alt text-2xl"></i>
                                    </div>
                                    <p class="mb-2 text-sm font-bold text-slate-600">Klik untuk unggah foto</p>
                                    <p class="text-xs font-medium text-slate-400">JPG, PNG atau JPEG (Maks. 2MB)</p>
                                </div>
                                
                                <input id="gambar_lokasi" name="gambar_lokasi" type="file" class="hidden" accept="image/*" onchange="previewFile()" required />
                            </label>
                            @error('gambar_lokasi')
                                <p class="mt-2 text-xs font-bold text-rose-500 text-center"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

            </div>

             <!-- 3. Sphere Calculations Display -->
             <div class="bg-white/60 backdrop-blur-xl border border-white/80 rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden" id="spherePreviewContainer">
                <div class="p-6 md:p-8">
                    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
                        <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-800">Target Standar Sphere <span class="bg-teal-100 text-teal-700 text-[10px] px-2 py-0.5 rounded ml-2 uppercase tracking-wide">Otomatis</span></h3>
                            <p class="text-xs text-slate-500 font-medium">Kalkulasi minimum kebutuhan dasar berdasarkan kapasitas pengunjung</p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-6">
                        <!-- Air -->
                        <div class="bg-slate-50/50 rounded-2xl border border-slate-100 p-5 hover:shadow-md transition-shadow">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center"><i class="fas fa-tint text-sm"></i></div>
                                <h4 class="font-bold text-slate-700">Kebutuhan Air</h4>
                            </div>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center bg-white p-3 rounded-xl border border-slate-100">
                                    <div>
                                        <span class="block text-xs font-bold text-slate-500">Minum & Hidup</span>
                                        <input type="hidden" id="air_hidup" name="air_hidup" value="{{old('air_hidup')}}">
                                    </div>
                                    <div class="text-right">
                                        <span class="text-lg font-black text-blue-600" id="text_air_hidup">0</span>
                                        <span class="text-[10px] font-bold text-slate-400">L/HR</span>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center bg-white p-3 rounded-xl border border-slate-100">
                                    <div>
                                        <span class="block text-xs font-bold text-slate-500">Kebersihan</span>
                                        <input type="hidden" id="air_kebersihan" name="air_kebersihan" value="{{old('air_kebersihan')}}">
                                    </div>
                                    <div class="text-right">
                                        <span class="text-lg font-black text-blue-600" id="text_air_kebersihan">0</span>
                                        <span class="text-[10px] font-bold text-slate-400">L/HR</span>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center bg-white p-3 rounded-xl border border-slate-100">
                                    <div>
                                        <span class="block text-xs font-bold text-slate-500">Memasak Dasar</span>
                                        <input type="hidden" id="air_memasak" name="air_memasak" value="{{old('air_memasak')}}">
                                    </div>
                                    <div class="text-right">
                                        <span class="text-lg font-black text-blue-600" id="text_air_memasak">0</span>
                                        <span class="text-[10px] font-bold text-slate-400">L/HR</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sanitasi -->
                        <div class="bg-slate-50/50 rounded-2xl border border-slate-100 p-5 hover:shadow-md transition-shadow">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center"><i class="fas fa-restroom text-sm"></i></div>
                                <h4 class="font-bold text-slate-700">Fasilitas Sanitasi</h4>
                            </div>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center bg-white p-3 rounded-xl border border-slate-100">
                                    <div>
                                        <span class="block text-xs font-bold text-slate-500">Jangka Pendek</span>
                                        <input type="hidden" id="toilet_pendek" name="toilet_pendek" value="{{old('toilet_pendek')}}">
                                    </div>
                                    <div class="text-right">
                                        <span class="text-lg font-black text-emerald-600" id="text_toilet_pendek">0</span>
                                        <span class="text-[10px] font-bold text-slate-400">UNIT TOILET</span>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center bg-white p-3 rounded-xl border border-slate-100">
                                    <div>
                                        <span class="block text-xs font-bold text-slate-500">Jangka Panjang</span>
                                        <input type="hidden" id="toilet_panjang" name="toilet_panjang" value="{{old('toilet_panjang')}}">
                                    </div>
                                    <div class="text-right">
                                        <span class="text-lg font-black text-emerald-600" id="text_toilet_panjang">0</span>
                                        <span class="text-[10px] font-bold text-slate-400">UNIT TOILET</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                         <!-- Nutrisi -->
                         <div class="bg-slate-50/50 rounded-2xl border border-slate-100 p-5 hover:shadow-md transition-shadow">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-8 h-8 rounded-lg bg-orange-100 text-orange-600 flex items-center justify-center"><i class="fas fa-apple-alt text-sm"></i></div>
                                <h4 class="font-bold text-slate-700">Kecukupan Nutrisi</h4>
                            </div>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center bg-white p-3 rounded-xl border border-slate-100">
                                    <div>
                                        <span class="block text-xs font-bold text-slate-500">Kalori Harian</span>
                                        <input type="hidden" id="kalori" name="kalori" value="{{old('kalori')}}">
                                    </div>
                                    <div class="text-right">
                                        <span class="text-lg font-black text-orange-600" id="text_kalori">0</span>
                                        <span class="text-[10px] font-bold text-slate-400">KCAL</span>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center bg-white p-3 rounded-xl border border-slate-100">
                                    <div>
                                        <span class="block text-xs font-bold text-slate-500">Asupan Protein</span>
                                        <input type="hidden" id="protein" name="protein" value="{{old('protein')}}">
                                    </div>
                                    <div class="text-right">
                                        <span class="text-lg font-black text-orange-600" id="text_protein">0</span>
                                        <span class="text-[10px] font-bold text-slate-400">GRAM</span>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center bg-white p-3 rounded-xl border border-slate-100">
                                    <div>
                                        <span class="block text-xs font-bold text-slate-500">Asupan Lemak</span>
                                        <input type="hidden" id="lemak" name="lemak" value="{{old('lemak')}}">
                                    </div>
                                    <div class="text-right">
                                        <span class="text-lg font-black text-orange-600" id="text_lemak">0</span>
                                        <span class="text-[10px] font-bold text-slate-400">GRAM</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button API -->
            <div class="pt-5 flex justify-start">
                <button type="submit" class="w-full sm:w-auto px-8 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold rounded-2xl shadow-lg shadow-blue-500/30 transform transition-all hover:-translate-y-1 flex items-center justify-center gap-3">
                    <i class="fas fa-paper-plane"></i>
                    Kirim Data Laporan Lokasi
                </button>
            </div>
            
        </form>
    </div>

<script>
    function previewFile() {
        const preview = document.getElementById('previewImage');
        const overlay = document.getElementById('previewOverlay');
        const placeholder = document.getElementById('uploadPlaceholder');
        const file = document.querySelector('input[type=file]').files[0];
        const reader = new FileReader();

        reader.onloadend = function () {
            preview.src = reader.result;
            preview.classList.remove('hidden');
            overlay.classList.remove('hidden');
            placeholder.classList.add('hidden');
        }

        if (file) {
            reader.readAsDataURL(file);
        } else {
            preview.src = "";
            preview.classList.add('hidden');
            overlay.classList.add('hidden');
            placeholder.classList.remove('hidden');
        }
    }

    function hitungSemua() {
        const luasRaw = document.getElementById('luas_lokasi').value;
        const luas = parseFloat(luasRaw) || 0;
        
        const kapasitas = Math.round(luas / 3.5);
        document.getElementById('kapasitas_pengungsi').value = kapasitas;
        hitungKebutuhanSphere(kapasitas);
    }

    function hitungKebutuhanSphere(kapasitas) {
        if(kapasitas <= 0) {
            clearSphereData();
            return;
        }

        // Air Kebutuhan Hidup (2.5 liter/orang/hari)
        const airHidup = 2.5 * kapasitas;
        document.getElementById('air_hidup').value = airHidup.toFixed(2);
        document.getElementById('text_air_hidup').innerText = Math.round(airHidup).toLocaleString('id-ID'); // simplified display

        // Air Kebersihan (2 liter/orang/hari)
        const airKebersihan = 2 * kapasitas;
        document.getElementById('air_kebersihan').value = airKebersihan.toFixed(2);
        document.getElementById('text_air_kebersihan').innerText = Math.round(airKebersihan).toLocaleString('id-ID');

        // Air untuk Memasak (3 liter/orang/hari)
        const airMemasak = 3 * kapasitas;
        document.getElementById('air_memasak').value = airMemasak.toFixed(2);
        document.getElementById('text_air_memasak').innerText = Math.round(airMemasak).toLocaleString('id-ID');

        // Toilet Jangka Pendek (1:50)
        const toiletPendek = Math.round(kapasitas / 50);
        document.getElementById('toilet_pendek').value = toiletPendek;
        document.getElementById('text_toilet_pendek').innerText = toiletPendek.toLocaleString('id-ID');

        // Toilet Jangka Panjang (1:20)
        const toiletPanjang = Math.round(kapasitas / 20);
        document.getElementById('toilet_panjang').value = toiletPanjang;
        document.getElementById('text_toilet_panjang').innerText = toiletPanjang.toLocaleString('id-ID');

        // Kalori per Hari (2100 kCal/orang/hari)
        const kalori = 2100 * kapasitas;
        document.getElementById('kalori').value = kalori.toFixed(0);
        document.getElementById('text_kalori').innerText = kalori.toLocaleString('id-ID');

        // Protein per Hari (53 g/orang/hari)
        const protein = 53 * kapasitas;
        document.getElementById('protein').value = protein.toFixed(0);
        document.getElementById('text_protein').innerText = protein.toLocaleString('id-ID');

        // Lemak per Hari (40 g/orang/hari)
        const lemak = 40 * kapasitas;
        document.getElementById('lemak').value = lemak.toFixed(0);
        document.getElementById('text_lemak').innerText = lemak.toLocaleString('id-ID');
    }

    function clearSphereData() {
        const fields = ['air_hidup', 'air_kebersihan', 'air_memasak', 'toilet_pendek', 'toilet_panjang', 'kalori', 'protein', 'lemak'];
        fields.forEach(field => {
            document.getElementById(field).value = '';
            document.getElementById('text_' + field).innerText = '0';
        });
    }
    
    // Panggil fungsi saat halaman dimuat (untuk old input)
    document.addEventListener('DOMContentLoaded', function() {
        if(document.getElementById('luas_lokasi').value) {
            hitungSemua();
        }
    });
</script>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
    const defaultLat = -6.2;
    const defaultLng = 106.816666;

    const map = L.map('map').setView([defaultLat, defaultLng], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    const marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);

    // set awal
    document.getElementById('latitude').value = defaultLat;
    document.getElementById('longitude').value = defaultLng;

    // klik map
    map.on('click', function (e) {
        marker.setLatLng(e.latlng);
        document.getElementById('latitude').value = e.latlng.lat;
        document.getElementById('longitude').value = e.latlng.lng;
    });

    // drag marker
    marker.on('dragend', function () {
        const pos = marker.getLatLng();
        document.getElementById('latitude').value = pos.lat;
        document.getElementById('longitude').value = pos.lng;
    });
</script>
@endsection