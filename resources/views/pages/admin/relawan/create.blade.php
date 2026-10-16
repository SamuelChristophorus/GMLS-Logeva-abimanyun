@extends('layouts.admin')

@section('title', 'Tambah Data Relawan')

@section('content')
<div class="relative z-10 container mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10 min-h-screen flex flex-col gap-8">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white/60 backdrop-blur-xl p-6 md:p-8 rounded-3xl border border-white/80 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-50 border border-emerald-100/50 text-emerald-600 text-xs font-bold tracking-wide uppercase mb-3">
                <i class="fas fa-user-plus opacity-70"></i>
                Registrasi Personil
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 tracking-tight">
                Tambah Relawan Baru
            </h1>
            <p class="text-slate-500 mt-1 font-medium text-sm">Tambahkan akun relawan lapangan baru beserta hak aksesnya.</p>
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
        <div class="h-24 bg-gradient-to-r from-emerald-500/10 to-teal-600/10 w-full relative border-b border-slate-100">
             <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMiIgY3k9IjIiIHI9IjIiIGZpbGw9InJnYmEoMCwwLDAsMC4wMykiLz48L3N2Zz4=')]"></div>
        </div>

        <div class="p-6 md:p-8 pt-0 relative">
            
            <form action="{{route('admin.relawan.store')}}" method="POST" enctype="multipart/form-data" class="space-y-8">
                @csrf

                <!-- Profile Upload Section positioned overlapping front -->
                <div class="flex flex-col sm:flex-row items-start sm:items-end gap-6 -mt-12 mb-8 relative z-10">
                    <div class="w-24 h-24 sm:w-32 sm:h-32 rounded-3xl bg-white p-1.5 shadow-xl border border-slate-100 shrink-0 relative group cursor-pointer" onclick="document.getElementById('avatar').click()">
                        <div class="w-full h-full rounded-2xl bg-slate-100 flex items-center justify-center transition-opacity group-hover:opacity-70 overflow-hidden" id="avatarPreviewContainer">
                            <i class="fas fa-user text-3xl text-slate-300" id="avatarIcon"></i>
                            <img src="" id="avatarPreview" alt="Avatar Preview" class="w-full h-full object-cover hidden">
                        </div>
                        <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                            <i class="fas fa-camera text-2xl text-slate-800 drop-shadow-md"></i>
                        </div>
                    </div>
                    
                    <div class="flex-1 space-y-2">
                        <label for="avatar" class="block text-sm font-bold text-slate-700">Foto Profil Resmi <span class="text-xs font-semibold text-slate-400">(opsional)</span></label>
                        <input type="file" id="avatar" name="avatar" accept="image/*" class="block w-full text-sm text-slate-500
                            file:mr-4 file:py-2.5 file:px-4
                            file:rounded-full file:border-0
                            file:text-xs file:font-bold file:uppercase file:tracking-wider
                            file:bg-emerald-50 file:text-emerald-700
                            hover:file:bg-emerald-100 transition-all cursor-pointer @error('avatar') border-rose-500 @enderror" onchange="previewImage(this)">
                        <p class="text-xs text-slate-500">Format PNG, JPG, JPEG (Maks. 2MB). Jika dikosongkan, memakai foto default dan dapat diganti nanti lewat Edit.</p>
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
                            <i class="fas fa-address-card text-emerald-500"></i> Informasi Personal
                        </h4>
                    </div>

                    <!-- Name Field -->
                    <div class="space-y-2">
                        <label for="name" class="block text-sm font-bold text-slate-700">Nama Lengkap Sesuai KTP <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-user-tie text-slate-400"></i>
                            </div>
                            <input type="text" id="name" name="name" 
                                value="{{old('name')}}"
                                class="w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all @error('name') border-rose-500 focus:ring-rose-500/20 focus:border-rose-500 @enderror"
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
                        <label for="email" class="block text-sm font-bold text-slate-700">Alamat Email Aktif <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-slate-400"></i>
                            </div>
                            <input type="email" id="email" name="email" 
                                value="{{old('email')}}"
                                class="w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all @error('email') border-rose-500 focus:ring-rose-500/20 focus:border-rose-500 @enderror"
                                placeholder="contoh@relawan.com">
                        </div>
                        @error('email')
                            <p class="mt-1.5 text-xs font-bold text-rose-500 flex items-center gap-1">
                                <i class="fas fa-exclamation-circle"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="md:col-span-2 mt-4 mb-2">
                        <h4 class="text-sm font-bold text-slate-800 uppercase tracking-widest flex items-center gap-2">
                            <i class="fas fa-key text-amber-500"></i> Akses Keamanan
                        </h4>
                    </div>

                    <!-- Password Field -->
                    <div class="space-y-2 md:col-span-2">
                        <label for="password" class="block text-sm font-bold text-slate-700">Password Sistem <span class="text-rose-500">*</span></label>
                        <div class="relative max-w-md">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-slate-400"></i>
                            </div>
                            <input type="password" id="password" name="password" 
                                class="w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all @error('password') border-rose-500 focus:ring-rose-500/20 focus:border-rose-500 @enderror"
                                placeholder="Minimal 8 karakter">
                        </div>
                        <p class="text-xs font-medium text-slate-500 mt-1.5 flex items-center gap-1.5">
                            Sandi ini akan digunakan relawan untuk masuk ke portal aplikasi lapangan.
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
                    <button type="submit" class="inline-flex items-center gap-2 bg-gradient-to-tr from-emerald-600 to-teal-500 hover:from-emerald-700 hover:to-teal-600 text-white font-bold px-6 py-3 rounded-xl shadow-lg shadow-emerald-500/30 transform transition-all hover:-translate-y-0.5 border border-emerald-400/20">
                        <i class="fas fa-user-plus text-sm"></i> Tambahkan Relawan
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
                const icon = document.getElementById('avatarIcon');
                
                if(preview) {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                }
                if(icon) {
                    icon.classList.add('hidden');
                }
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
@endsection