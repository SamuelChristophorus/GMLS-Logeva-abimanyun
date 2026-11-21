@extends('layouts.admin')

@section('title', 'Edit Data Relawan')

@section('content')
<div class="relative z-10 container mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10 min-h-screen flex flex-col gap-8">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white/60 backdrop-blur-xl p-6 md:p-8 rounded-3xl border border-white/80 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-amber-50 border border-amber-100/50 text-amber-600 text-xs font-bold tracking-wide uppercase mb-3">
                <i class="fas fa-user-edit opacity-70"></i>
                Pembaruan Profil
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 tracking-tight">
                Edit Relawan: {{ $relawan->user->name }}
            </h1>
            <p class="text-slate-500 mt-1 font-medium text-sm">Modifikasi data personal, akses, atau perbarui foto profil relawan.</p>
        </div>

        <div>
            <a href="{{route('admin.relawan.index')}}" class="inline-flex items-center justify-center gap-2 bg-white hover:bg-slate-50 text-slate-600 font-bold px-5 py-3 rounded-xl shadow-sm border border-slate-200 transition-all hover:-translate-x-1">
                <i class="fas fa-arrow-left text-sm opacity-70"></i> Batal / Kembali
            </a>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden">
        
        <!-- Cover Background -->
        <div class="h-24 bg-gradient-to-r from-blue-500/10 to-indigo-600/10 w-full relative border-b border-slate-100">
             <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMiIgY3k9IjIiIHI9IjIiIGZpbGw9InJnYmEoMCwwLDAsMC4wMykiLz48L3N2Zz4=')]"></div>
        </div>

        <div class="p-6 md:p-8 pt-0 relative">
            
            <form action="{{route('admin.relawan.update', $relawan->id)}}" method="POST" enctype="multipart/form-data" class="space-y-8">
                @csrf
                @method('PUT')

                <!-- Profile Upload Section positioned overlapping front -->
                <div class="flex flex-col sm:flex-row items-start sm:items-end gap-6 -mt-12 mb-8 relative z-10">
                    <div class="w-24 h-24 sm:w-32 sm:h-32 rounded-3xl bg-white p-1.5 shadow-xl border border-slate-100 shrink-0 relative group cursor-pointer" onclick="document.getElementById('avatar').click()">
                        @if($relawan->avatar)
                            <img src="{{asset('storage/'.$relawan->avatar)}}" id="avatarPreview" alt="Current Avatar" class="w-full h-full rounded-2xl object-cover transition-opacity group-hover:opacity-70">
                        @else
                            <div class="w-full h-full rounded-2xl bg-slate-100 flex items-center justify-center transition-opacity group-hover:opacity-70" id="avatarPreviewContainer">
                                <i class="fas fa-user text-3xl text-slate-300"></i>
                            </div>
                        @endif
                        <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                            <i class="fas fa-camera text-2xl text-slate-800 drop-shadow-md"></i>
                        </div>
                    </div>
                    
                    <div class="flex-1 space-y-2">
                        <label for="avatar" class="block text-sm font-bold text-slate-700">Foto Profil (Opsional)</label>
                        <input type="file" id="avatar" name="avatar" accept="image/*" class="block w-full text-sm text-slate-500
                            file:mr-4 file:py-2.5 file:px-4
                            file:rounded-full file:border-0
                            file:text-xs file:font-bold file:uppercase file:tracking-wider
                            file:bg-blue-50 file:text-blue-700
                            hover:file:bg-blue-100 transition-all cursor-pointer @error('avatar') border-rose-500 @enderror" onchange="previewImage(this)">
                        <p class="text-xs text-slate-500">Format PNG, JPG, JPEG (Maks. 2MB). Klik gambar untuk mengubah.</p>
                        @error('avatar')
                            <p class="mt-1.5 text-xs font-bold text-rose-500 flex items-center gap-1">
                                <i class="fas fa-exclamation-circle"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50/50 rounded-2xl border border-slate-100 p-6">
                    
                    <div class="md:col-span-2 mb-2">
                        <h4 class="text-sm font-bold text-slate-800 uppercase tracking-widest flex items-center gap-2">
                            <i class="fas fa-address-card text-blue-500"></i> Informasi Personal
                        </h4>
                    </div>

                    <!-- Name Field -->
                    <div class="space-y-2">
                        <label for="name" class="block text-sm font-bold text-slate-700">Nama Lengkap <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-user-tie text-slate-400"></i>
                            </div>
                            <input type="text" id="name" name="name" 
                                value="{{old('name', $relawan->user->name)}}"
                                class="w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all @error('name') border-rose-500 focus:ring-rose-500/20 focus:border-rose-500 @enderror"
                                placeholder="Masukkan nama lengkap">
                        </div>
                        @error('name')
                            <p class="mt-1.5 text-xs font-bold text-rose-500 flex items-center gap-1">
                                <i class="fas fa-exclamation-circle"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Email Field -->
                    <div class="space-y-2">
                        <label for="email" class="block text-sm font-bold text-slate-700">Alamat Email (Akses Login)</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-slate-400"></i>
                            </div>
                            <!-- Readonly styling -->
                            <input type="email" id="email" name="email" 
                                value="{{old('email', $relawan->user->email)}}" readonly
                                class="w-full pl-11 pr-4 py-3 bg-slate-100 text-slate-500 border border-slate-200 rounded-xl shadow-sm cursor-not-allowed select-none"
                                title="Email tidak dapat diubah">
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-slate-400 text-xs"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Penugasan posko: satu relawan = satu posko pilihan admin -->
                    <div class="md:col-span-2 mt-4 mb-2">
                        <h4 class="text-sm font-bold text-slate-800 uppercase tracking-widest flex items-center gap-2">
                            <i class="fas fa-map-marker-alt text-red-500"></i> Posko Tugas
                        </h4>
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <label for="lokasi_id" class="block text-sm font-bold text-slate-700">Bertugas di posko</label>
                        @php $dipilih = (string) old('lokasi_id', $poskoTugas?->id); @endphp
                        <select id="lokasi_id" name="lokasi_id"
                            class="w-full max-w-xl px-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all @error('lokasi_id') border-rose-500 @enderror">
                            @if (! $poskoTugas)
                                <option value="" @selected($dipilih === '')>— Belum ditugaskan —</option>
                            @endif
                            @foreach ($poskos as $p)
                                @php
                                    $pj = optional(optional($p->relawan)->user)->name;
                                    $status = optional($p->latestStatusView)->status;
                                @endphp
                                <option value="{{ $p->id }}" @selected($dipilih === (string) $p->id)>
                                    {{ $p->nama_lokasi }}{{ $p->desa ? ' · ' . $p->desa->nama_desa : '' }}
                                    @if ($status !== 'approved') ({{ $status === 'rejected' ? 'ditolak' : 'belum disetujui' }}) @endif
                                    @if ($pj && (int) $p->relawan_id !== (int) $relawan->id) — PJ sekarang: {{ $pj }} @endif
                                </option>
                            @endforeach
                        </select>
                        @if ($jumlahPosko > 1)
                            <p class="text-xs font-bold text-rose-600 mt-1.5 flex items-center gap-1.5 border border-rose-200 bg-rose-50 p-2 rounded-lg max-w-xl">
                                <i class="fas fa-exclamation-triangle"></i> Relawan ini saat ini memegang {{ $jumlahPosko }} posko (data lama). Satu relawan hanya untuk satu posko: ganti penanggung jawab posko lainnya lewat Edit Posko.
                            </p>
                        @endif
                        <p class="text-xs font-medium text-slate-500 mt-1.5 flex items-start gap-1.5 border border-slate-200 bg-slate-50 p-2 rounded-lg max-w-xl">
                            <i class="fas fa-info-circle mt-0.5"></i>
                            <span>Satu posko = satu relawan penanggung jawab, satu relawan = satu posko. Relawan menjadi penanggung jawab posko yang dipilih dan bisa mengelola pengungsi &amp; logistiknya (setelah posko disetujui). Penanggung jawab lama posko itu <b>bertukar</b> ke posko asal relawan ini; kalau relawan ini belum punya posko, penanggung jawab lama menjadi belum ditugaskan. Relawan lain hanya bisa melihat detail posko.</span>
                        </p>
                        @error('lokasi_id')
                            <p class="mt-1.5 text-xs font-bold text-rose-500 flex items-center gap-1">
                                <i class="fas fa-exclamation-circle"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="md:col-span-2 mt-4 mb-2">
                        <h4 class="text-sm font-bold text-slate-800 uppercase tracking-widest flex items-center gap-2">
                            <i class="fas fa-shield-alt text-amber-500"></i> Keamanan Akun
                        </h4>
                    </div>

                    <!-- Password Field -->
                    <div class="space-y-2 md:col-span-2">
                        <label for="password" class="block text-sm font-bold text-slate-700">Password Baru</label>
                        <div class="relative max-w-md">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-key text-slate-400"></i>
                            </div>
                            <input type="password" id="password" name="password" 
                                class="w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all @error('password') border-rose-500 focus:ring-rose-500/20 focus:border-rose-500 @enderror"
                                placeholder="••••••••">
                        </div>
                        <p class="text-xs font-medium text-amber-600 mt-1.5 flex items-center gap-1.5 border border-amber-200 bg-amber-50 p-2 rounded-lg max-w-md">
                            <i class="fas fa-info-circle"></i> Biarkan kosong jika tidak ingin mengubah kata sandi lama.
                        </p>
                        @error('password')
                            <p class="mt-1.5 text-xs font-bold text-rose-500 flex items-center gap-1">
                                <i class="fas fa-exclamation-circle"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>

                </div>

                <!-- Submit Button -->
                <div class="flex justify-end pt-4 mt-6 border-t border-slate-100">
                    <button type="submit" class="inline-flex items-center gap-2 bg-gradient-to-tr from-blue-600 to-indigo-500 hover:from-blue-700 hover:to-indigo-600 text-white font-bold px-6 py-3 rounded-xl shadow-lg shadow-blue-500/30 transform transition-all hover:-translate-y-0.5 border border-blue-400/20">
                        <i class="fas fa-save text-sm"></i> Simpan Pembaruan
                    </button>
                </div>
            </form>
        </div>
        
    </div>
</div>

<script>
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('avatarPreview');
                if(preview) {
                    preview.src = e.target.result;
                } else {
                    const container = document.getElementById('avatarPreviewContainer');
                    container.innerHTML = `<img src="${e.target.result}" id="avatarPreview" alt="Avatar" class="w-full h-full rounded-2xl object-cover transition-opacity group-hover:opacity-70">`;
                }
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
@endsection