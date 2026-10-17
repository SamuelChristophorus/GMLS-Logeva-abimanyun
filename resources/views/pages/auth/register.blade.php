@extends('layouts.auth')

@section('title', 'Buat Akun Baru')

@section('content')
<div class="w-full max-w-md mx-auto relative z-10">
    <!-- Dekorasi Background -->
    <div class="absolute -top-16 -right-16 w-48 h-48 bg-blue-300 rounded-full mix-blend-multiply filter blur-2xl opacity-50 animate-blob animation-delay-4000"></div>
    <div class="absolute -bottom-16 -left-16 w-48 h-48 bg-indigo-300 rounded-full mix-blend-multiply filter blur-2xl opacity-50 animate-blob"></div>

    <div class="bg-white/90 backdrop-blur-lg rounded-3xl shadow-2xl overflow-hidden p-8 sm:p-10 border border-white/50 relative">
        <div class="text-center mb-8">
            <a href="{{ route('home') }}" class="inline-block mb-5">
                <img src="{{ asset('assets/logo/logo-logeva.png') }}"
                     alt="Logo Logeva - Gugus Mitigasi Lebak Selatan"
                     class="mx-auto w-full max-w-[260px] h-auto object-contain transform hover:scale-105 transition-transform duration-300">
            </a>
            <h1 class="text-2xl font-extrabold text-gray-800 tracking-tight">Daftar Akun Baru</h1>
            <p class="text-gray-500 mt-1.5 text-sm">Bergabunglah untuk mulai menggunakan Logeva</p>
        </div>

        <form action="{{ route('register.store') }}" method="POST" class="space-y-5" enctype="multipart/form-data">
            @csrf
            
            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-semibold text-gray-700 mb-1.5 ml-1">Alamat Email</label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-500 transition-colors">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <input type="email" id="email" name="email" 
                           class="block w-full pl-11 pr-4 py-3 bg-gray-50 border @error('email') border-red-500 @else border-gray-200 @enderror rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 focus:bg-white transition-all duration-300"
                           value="{{ old('email') }}" placeholder="pengguna@contoh.com">
                </div>
                @error('email')
                    <p class="mt-1.5 text-sm text-red-500 flex items-center ml-1"><i class="fas fa-exclamation-circle mr-1"></i> {{ $message }}</p>
                @enderror
            </div>

            <!-- Nama Lengkap -->
            <div>
                <label for="name" class="block text-sm font-semibold text-gray-700 mb-1.5 ml-1">Nama Lengkap</label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-500 transition-colors">
                        <i class="fas fa-user"></i>
                    </div>
                    <input type="text" id="name" name="name" 
                           class="block w-full pl-11 pr-4 py-3 bg-gray-50 border @error('name') border-red-500 @else border-gray-200 @enderror rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 focus:bg-white transition-all duration-300"
                           value="{{ old('name') }}" placeholder="Nama sesuai identitas">
                </div>
                @error('name')
                    <p class="mt-1.5 text-sm text-red-500 flex items-center ml-1"><i class="fas fa-exclamation-circle mr-1"></i> {{ $message }}</p>
                @enderror
            </div>

            <!-- Foto Profil -->
            <div>
                <label for="avatar" class="block text-sm font-semibold text-gray-700 mb-1.5 ml-1">Foto Profil (Opsional)</label>
                <div class="relative group">
                    <input type="file" id="avatar" name="avatar" 
                           class="block w-full bg-gray-50 border @error('avatar') border-red-500 @else border-gray-200 @enderror rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500/50 transition-all duration-300 text-sm text-gray-500
                                  file:mr-4 file:py-3 file:px-4 file:rounded-l-xl file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                </div>
                @error('avatar')
                    <p class="mt-1.5 text-sm text-red-500 flex items-center ml-1"><i class="fas fa-exclamation-circle mr-1"></i> {{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-semibold text-gray-700 mb-1.5 ml-1">Password</label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-500 transition-colors">
                        <i class="fas fa-lock"></i>
                    </div>
                    <input type="password" id="password" name="password" 
                           class="block w-full pl-11 pr-4 py-3 bg-gray-50 border @error('password') border-red-500 @else border-gray-200 @enderror rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 focus:bg-white transition-all duration-300"
                           placeholder="Minimal 8 karakter">
                </div>
                @error('password')
                    <p class="mt-1.5 text-sm text-red-500 flex items-center ml-1"><i class="fas fa-exclamation-circle mr-1"></i> {{ $message }}</p>
                @enderror
            </div>

            <div class="pt-3">
                <button type="submit" 
                        class="w-full flex justify-center py-3.5 px-4 rounded-xl shadow-md text-sm font-bold text-white bg-gradient-to-r from-red-600 to-red-600 hover:from-gray-700 hover:to-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transform hover:-translate-y-0.5 transition-all duration-300">
                    <i class="fas fa-user-plus mr-2 mt-0.5"></i> Buat Akun
                </button>
            </div>
        </form>

        <div class="mt-6 pt-5 border-t border-gray-100 text-center">
            <p class="text-sm text-gray-500">
                Sudah punya akun?
                <a href="{{ route('login') }}" class="font-bold text-blue-600 hover:text-indigo-600 hover:underline transition-colors ml-1">Masuk di sini</a>
            </p>
        </div>
    </div>
</div>
@endsection