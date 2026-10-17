@extends('layouts.admin')

@section('title', 'Tambah Data Lokasi')

@section('content')
<div class="relative z-10 container mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10 min-h-screen flex flex-col gap-8">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white/60 backdrop-blur-xl p-6 md:p-8 rounded-3xl border border-white/80 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-50 border border-emerald-100/50 text-emerald-600 text-xs font-bold tracking-wide uppercase mb-3">
                <i class="fas fa-plus opacity-70"></i>
                Pendaftaran Fasilitas
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 tracking-tight">
                Tambah Lokasi Posko Baru
            </h1>
            <p class="text-slate-500 mt-1 font-medium text-sm">Integrasikan lokasi posko / titik pengungsian baru ke dalam sistem.</p>
        </div>

        <div>
            <a href="{{route('admin.lokasi.index')}}" class="inline-flex items-center justify-center gap-2 bg-white hover:bg-slate-50 text-slate-600 font-bold px-5 py-3 rounded-xl shadow-sm border border-slate-200 transition-all hover:-translate-x-1">
                <i class="fas fa-arrow-left text-sm opacity-70"></i> Batal / Kembali
            </a>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden">
        
        <div class="p-6 md:p-8">
            <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i class="fas fa-campground"></i>
                </div>
                Registrasi Titik Pengungsian
            </h3>

            <form action="{{route('admin.lokasi.store')}}" method="POST" enctype="multipart/form-data" class="space-y-8">
                @csrf

                <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
                    <!-- Left Column: Basic Info -->
                    <div class="space-y-6 bg-slate-50/50 rounded-2xl border border-slate-100 p-6">
                        <h4 class="text-sm font-bold text-slate-800 uppercase tracking-widest flex items-center gap-2 mb-4 border-b border-slate-200 pb-2">
                            <i class="fas fa-info-circle text-emerald-500"></i> Integrasi Wilayah & Tim
                        </h4>

                        <!-- Nama Desa Field -->
                        <div class="space-y-2">
                            <label for="desa_id" class="block text-sm font-bold text-slate-700">Wilayah Desa Integrasi <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                                    <i class="fas fa-map-signs text-slate-400"></i>
                                </div>
                                <select name="desa_id" id="desa_id"
                                    class="w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all appearance-none @error('desa_id') border-rose-500 focus:ring-rose-500/20 focus:border-rose-500 @enderror">
                                    <option value="" disabled selected>-- Pilih Desa --</option>
                                    @foreach ($desas as $desa)
                                        <option value="{{$desa->id}}" @if (old('desa_id') == $desa->id) selected @endif>
                                            {{$desa->nama_desa}}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none z-10">
                                    <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                                </div>
                            </div>
                            @error('desa_id')
                                <p class="mt-1.5 text-xs font-bold text-rose-500 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Nama Relawan Field -->
                        <div class="space-y-2">
                            <label for="relawan_id" class="block text-sm font-bold text-slate-700">Penanggung Jawab (Relawan) <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                                    <i class="fas fa-user-shield text-slate-400"></i>
                                </div>
                                <select name="relawan_id" id="relawan_id"
                                    class="w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all appearance-none @error('relawan_id') border-rose-500 focus:ring-rose-500/20 focus:border-rose-500 @enderror">
                                    <option value="" disabled selected>-- Hubungkan Relawan Aktif --</option>
                                    @foreach ($relawans as $relawan)
                                        <option value="{{$relawan->id}}" @if (old('relawan_id') == $relawan->id) selected @endif>
                                            {{$relawan->user->name}} ({{$relawan->user->email}})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none z-10">
                                    <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                                </div>
                            </div>
                            @error('relawan_id')
                                <p class="mt-1.5 text-xs font-bold text-rose-500 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Nama Lokasi Field -->
                        <div class="space-y-2">
                            <label for="nama_lokasi" class="block text-sm font-bold text-slate-700">Nama Posko / Lokasi <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                                    <i class="fas fa-tag text-slate-400"></i>
                                </div>
                                <input type="text" id="nama_lokasi" name="nama_lokasi" value="{{old('nama_lokasi')}}"
                                    class="w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all @error('nama_lokasi') border-rose-500 focus:ring-rose-500/20 focus:border-rose-500 @enderror" placeholder="Contoh: Posko Utama Bale Desa">
                            </div>
                            @error('nama_lokasi')
                                <p class="mt-1.5 text-xs font-bold text-rose-500 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Alamat Lokasi Field -->
                        <div class="space-y-2 border-t border-slate-200 pt-4 mt-6">
                            <label for="alamat_lokasi" class="block text-sm font-bold text-slate-700">Detail Jalan / Alamat Cepat <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <div class="absolute top-3.5 left-0 pl-4 flex items-start pointer-events-none z-10">
                                    <i class="fas fa-map-marker-alt text-slate-400 mt-1"></i>
                                </div>
                                <textarea id="alamat_lokasi" name="alamat_lokasi" rows="3"
                                    class="w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all @error('alamat_lokasi') border-rose-500 focus:ring-rose-500/20 focus:border-rose-500 @enderror" placeholder="Masukkan detail rute ke posko...">{{old('alamat_lokasi')}}</textarea>
                            </div>
                            @error('alamat_lokasi')
                                <p class="mt-1.5 text-xs font-bold text-rose-500 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Right Column: Coordinates & Image -->
                    <div class="space-y-6">
                        <div class="bg-indigo-50/50 rounded-2xl border border-indigo-100 p-6">
                            <h4 class="text-sm font-bold text-slate-800 uppercase tracking-widest flex items-center gap-2 mb-4 border-b border-indigo-200/60 pb-2">
                                <i class="fas fa-map-marked-alt text-indigo-500"></i> Setup Sensor Koordinat
                            </h4>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <label for="latitude" class="block text-sm font-bold text-slate-700">Latitude <span class="text-rose-500">*</span></label>
                                    <input type="text" id="latitude" name="latitude" value="{{old('latitude')}}" placeholder="-6.12345"
                                        class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-mono text-sm @error('latitude') border-rose-500 focus:ring-rose-500/20 focus:border-rose-500 @enderror">
                                    @error('latitude')
                                        <p class="mt-1.5 text-xs font-bold text-rose-500 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="space-y-2">
                                    <label for="longitude" class="block text-sm font-bold text-slate-700">Longitude <span class="text-rose-500">*</span></label>
                                    <input type="text" id="longitude" name="longitude" value="{{old('longitude')}}" placeholder="106.12345"
                                        class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-mono text-sm @error('longitude') border-rose-500 focus:ring-rose-500/20 focus:border-rose-500 @enderror">
                                    @error('longitude')
                                        <p class="mt-1.5 text-xs font-bold text-rose-500 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="bg-amber-50/50 rounded-2xl border border-amber-100 p-6">
                            <h4 class="text-sm font-bold text-slate-800 uppercase tracking-widest flex items-center gap-2 mb-4 border-b border-amber-200/60 pb-2">
                                <i class="fas fa-image text-amber-500"></i> Laporan Visualisasi
                            </h4>

                            <div class="space-y-4">
                                <div class="w-full h-40 hidden rounded-xl overflow-hidden border-2 border-slate-200 shadow-sm relative group cursor-pointer" onclick="document.getElementById('gambar_lokasi').click()" id="previewContainerLokasi">
                                    <img src="" id="lokasiPreview" alt="Foto Lokasi" class="w-full h-full object-cover transition-opacity group-hover:opacity-70">
                                    <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity bg-slate-900/20">
                                        <div class="bg-white/90 backdrop-blur text-slate-800 text-xs font-bold px-3 py-1.5 rounded-lg shadow-lg flex items-center gap-2">
                                            <i class="fas fa-camera"></i> Ubah Foto
                                        </div>
                                    </div>
                                </div>
                                
                                <div>
                                    <label for="gambar_lokasi" class="block text-sm font-bold text-slate-700 mb-2">Unggah Foto Lapangan <span class="text-rose-500">*</span></label>
                                    <input type="file" id="gambar_lokasi" name="gambar_lokasi" accept="image/*"
                                        class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:uppercase file:tracking-wider file:bg-amber-100 file:text-amber-700 hover:file:bg-amber-200 transition-all cursor-pointer @error('gambar_lokasi') border-rose-500 @enderror" onchange="previewLokasi(this)">
                                    @error('gambar_lokasi')
                                        <p class="mt-1.5 text-xs font-bold text-rose-500 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Capacity Section -->
                <div class="bg-emerald-50/40 border border-emerald-100/60 rounded-3xl p-6 md:p-8 mt-8 shadow-sm relative overflow-hidden">
                    <div class="absolute right-0 top-0 w-64 h-64 bg-emerald-500/5 rounded-full blur-3xl -mr-20 -mt-20 pointer-events-none"></div>

                    <div class="flex items-center justify-between mb-6 relative z-10">
                        <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center">
                                <i class="fas fa-expand-arrows-alt"></i>
                            </div>
                            Kalkulasi Ruang Hidup Posko
                        </h3>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 border border-emerald-200">
                            Auto Generate Sphere Data
                        </span>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 relative z-10">
                        <div class="space-y-4 bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
                            <label for="luas_lokasi" class="block text-sm font-bold text-slate-700">Estimasi Luas Area Terpakai <span class="text-rose-500">*</span></label>
                            <div class="flex items-center gap-4">
                                <div class="relative flex-1">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                                        <i class="fas fa-vector-square text-emerald-500/70"></i>
                                    </div>
                                    <input type="number" step="0.01" id="luas_lokasi" name="luas_lokasi" value="{{old('luas_lokasi')}}" placeholder="0"
                                        class="w-full pl-11 pr-12 py-4 bg-slate-50 border border-slate-200 rounded-xl shadow-inner focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition-all font-black text-lg text-slate-700 @error('luas_lokasi') border-rose-500 @enderror"
                                        oninput="hitungSemua()">
                                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none z-10">
                                        <span class="text-slate-400 font-bold">m²</span>
                                    </div>
                                </div>
                                <div class="w-12 flex justify-center text-slate-300">
                                    <i class="fas fa-arrow-right text-xl"></i>
                                </div>
                            </div>
                            @error('luas_lokasi')
                                <p class="mt-1.5 text-xs font-bold text-rose-500 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-4 bg-emerald-600 p-5 rounded-2xl border border-emerald-500 shadow-md text-white">
                            <label for="kapasitas_pengungsi" class="block text-sm font-bold text-emerald-50">Daya Tampung Maksimal</label>
                            <div class="relative flex-1">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                                    <i class="fas fa-users text-emerald-200"></i>
                                </div>
                                <input type="number" id="kapasitas_pengungsi" name="kapasitas_pengungsi" value="{{old('kapasitas_pengungsi')}}" placeholder="0"
                                    class="w-full pl-11 pr-16 py-4 bg-emerald-700 border border-emerald-500 rounded-xl shadow-inner focus:outline-none focus:ring-2 focus:ring-white/20 transition-all font-black text-xl text-white readonly-input cursor-not-allowed @error('kapasitas_pengungsi') border-rose-400 @enderror"
                                    readonly>
                                <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none z-10">
                                    <span class="text-emerald-200 font-bold">Jiwa</span>
                                </div>
                            </div>
                            <p class="text-[11px] text-emerald-100 font-medium">Berdasarkan rasio kepadatan standar sphere (3.5 m² / orang).</p>
                        </div>
                    </div>
                </div>

                <!-- Sphere Calculations Display -->
                <div class="space-y-6 mt-8" id="spherePreviewContainer">
                    <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2 border-b border-slate-200 pb-3">
                        <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        Pratinjau Standar Sphere
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Air Card -->
                        <div class="bg-blue-50/50 rounded-2xl border border-blue-100 p-5">
                            <h4 class="text-xs font-bold text-blue-800 uppercase tracking-widest flex items-center gap-2 mb-4">
                                <i class="fas fa-tint text-blue-500"></i> Kebutuhan Air (Ltr/Hari)
                            </h4>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 mb-1">Hidup</label>
                                    <input type="number" id="air_hidup_preview" class="w-full px-3 py-2 bg-white/50 border border-slate-200 rounded-lg text-sm text-slate-500 font-bold focus:outline-none pointer-events-none" readonly>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 mb-1">Kebersihan</label>
                                    <input type="number" id="air_kebersihan_preview" class="w-full px-3 py-2 bg-white/50 border border-slate-200 rounded-lg text-sm text-slate-500 font-bold focus:outline-none pointer-events-none" readonly>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 mb-1">Memasak</label>
                                    <input type="number" id="air_memasak_preview" class="w-full px-3 py-2 bg-white/50 border border-slate-200 rounded-lg text-sm text-slate-500 font-bold focus:outline-none pointer-events-none" readonly>
                                </div>
                            </div>
                        </div>

                        <!-- Sanitasi Card -->
                        <div class="bg-emerald-50/50 rounded-2xl border border-emerald-100 p-5">
                            <h4 class="text-xs font-bold text-emerald-800 uppercase tracking-widest flex items-center gap-2 mb-4">
                                <i class="fas fa-restroom text-emerald-500"></i> Fasilitas Sanitasi
                            </h4>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 mb-1">Toilet Jangka Pendek</label>
                                    <input type="number" id="toilet_pendek_preview" class="w-full px-3 py-2 bg-white/50 border border-slate-200 rounded-lg text-sm text-slate-500 font-bold focus:outline-none pointer-events-none" readonly>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 mb-1">Toilet Jangka Panjang</label>
                                    <input type="number" id="toilet_panjang_preview" class="w-full px-3 py-2 bg-white/50 border border-slate-200 rounded-lg text-sm text-slate-500 font-bold focus:outline-none pointer-events-none" readonly>
                                </div>
                            </div>
                        </div>

                        <!-- Nutrisi Card -->
                        <div class="bg-amber-50/50 rounded-2xl border border-amber-100 p-5">
                            <h4 class="text-xs font-bold text-amber-800 uppercase tracking-widest flex items-center gap-2 mb-4">
                                <i class="fas fa-utensils text-amber-500"></i> Kebutuhan Nutrisi
                            </h4>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 mb-1">Kalori (kCal)</label>
                                    <input type="number" id="kalori_preview" class="w-full px-3 py-2 bg-white/50 border border-slate-200 rounded-lg text-sm text-slate-500 font-bold focus:outline-none pointer-events-none" readonly>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 mb-1">Protein (g)</label>
                                    <input type="number" id="protein_preview" class="w-full px-3 py-2 bg-white/50 border border-slate-200 rounded-lg text-sm text-slate-500 font-bold focus:outline-none pointer-events-none" readonly>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 mb-1">Lemak (g)</label>
                                    <input type="number" id="lemak_preview" class="w-full px-3 py-2 bg-white/50 border border-slate-200 rounded-lg text-sm text-slate-500 font-bold focus:outline-none pointer-events-none" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hidden Sphere Standard Inputs (Generated by JS during submission) -->
                <!-- We hide these in the UI since we mimic the old layout via form requests -->
                <div class="hidden">
                    <input type="hidden" id="air_hidup" name="air_hidup" value="{{old('air_hidup')}}">
                    <input type="hidden" id="air_kebersihan" name="air_kebersihan" value="{{old('air_kebersihan')}}">
                    <input type="hidden" id="air_memasak" name="air_memasak" value="{{old('air_memasak')}}">
                    <input type="hidden" id="toilet_pendek" name="toilet_pendek" value="{{old('toilet_pendek')}}">
                    <input type="hidden" id="toilet_panjang" name="toilet_panjang" value="{{old('toilet_panjang')}}">
                    <input type="hidden" id="kalori" name="kalori" value="{{old('kalori')}}">
                    <input type="hidden" id="protein" name="protein" value="{{old('protein')}}">
                    <input type="hidden" id="lemak" name="lemak" value="{{old('lemak')}}">
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end pt-4 mt-8 border-t border-slate-100">
                    <button type="submit" class="inline-flex items-center gap-2 bg-gradient-to-tr from-emerald-600 to-teal-500 hover:from-emerald-700 hover:to-teal-600 text-white font-bold px-8 py-4 rounded-xl shadow-lg shadow-emerald-500/30 transform transition-all hover:-translate-y-1 border border-emerald-400/20 text-lg">
                        <i class="fas fa-paper-plane"></i> Daftarkan Posko Pengungsian
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function previewLokasi(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('lokasiPreview');
                const container = document.getElementById('previewContainerLokasi');
                if(preview && container) {
                    preview.src = e.target.result;
                    container.classList.remove('hidden');
                }
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function hitungSemua() {
        const luasRaw = document.getElementById('luas_lokasi').value;
        const luas = parseFloat(luasRaw) || 0;
        
        // Show or hide preview cards based on input
        const sphereContainer = document.getElementById('spherePreviewContainer');
        if(luas > 0) {
            // we no longer hide it, but we only calculate if luas > 0
            const kapasitas = Math.round(luas / 3.5);
            document.getElementById('kapasitas_pengungsi').value = kapasitas;
            hitungKebutuhanSphere(kapasitas);
        } else {
            document.getElementById('kapasitas_pengungsi').value = '';
            clearSphereData();
            return;
        }

    }

    function hitungKebutuhanSphere(kapasitas) {
        // Values for form submission (Hidden names)
        document.getElementById('air_hidup').value = (2.5 * kapasitas).toFixed(2);
        document.getElementById('air_kebersihan').value = (2 * kapasitas).toFixed(2);
        document.getElementById('air_memasak').value = (3 * kapasitas).toFixed(2);
        document.getElementById('toilet_pendek').value = Math.round(kapasitas / 50);
        document.getElementById('toilet_panjang').value = Math.round(kapasitas / 20);
        document.getElementById('kalori').value = (2100 * kapasitas).toFixed(0);
        document.getElementById('protein').value = (53 * kapasitas).toFixed(0);
        document.getElementById('lemak').value = (40 * kapasitas).toFixed(0);

        // Values for UI Preview
        document.getElementById('air_hidup_preview').value = (2.5 * kapasitas).toFixed(2);
        document.getElementById('air_kebersihan_preview').value = (2 * kapasitas).toFixed(2);
        document.getElementById('air_memasak_preview').value = (3 * kapasitas).toFixed(2);
        document.getElementById('toilet_pendek_preview').value = Math.round(kapasitas / 50);
        document.getElementById('toilet_panjang_preview').value = Math.round(kapasitas / 20);
        document.getElementById('kalori_preview').value = (2100 * kapasitas).toFixed(0);
        document.getElementById('protein_preview').value = (53 * kapasitas).toFixed(0);
        document.getElementById('lemak_preview').value = (40 * kapasitas).toFixed(0);
    }

    function clearSphereData() {
        // Clear all previews
        const previewIds = ['air_hidup_preview', 'air_kebersihan_preview', 'air_memasak_preview', 
                           'toilet_pendek_preview', 'toilet_panjang_preview', 'kalori_preview', 
                           'protein_preview', 'lemak_preview'];
        previewIds.forEach(id => document.getElementById(id).value = '');

        // Clear hidden form fields
        const hiddenIds = ['air_hidup', 'air_kebersihan', 'air_memasak', 
                           'toilet_pendek', 'toilet_panjang', 'kalori', 
                           'protein', 'lemak'];
        hiddenIds.forEach(id => document.getElementById(id).value = '');
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        // If there is an old value for luas_lokasi after a failed validation
        if(document.getElementById('luas_lokasi').value) {
           hitungSemua(); 
        }
    });
</script>
<style>
    /* Turn off browser spinner arrows for number input capacity */
    input[type=number].readonly-input::-webkit-inner-spin-button, 
    input[type=number].readonly-input::-webkit-outer-spin-button { 
        -webkit-appearance: none; 
        margin: 0; 
    }
</style>
@endsection