@extends('layouts.auth')

@section('title', 'Masuk ke Sistem')

@section('content')
<div class="w-full max-w-md mx-auto relative z-10">
    <!-- Dekorasi Background -->
    <div class="absolute -top-16 -left-16 w-48 h-48 bg-blue-300 rounded-full mix-blend-multiply filter blur-2xl opacity-50 animate-blob"></div>
    <div class="absolute -bottom-16 -right-16 w-48 h-48 bg-indigo-300 rounded-full mix-blend-multiply filter blur-2xl opacity-50 animate-blob animation-delay-2000"></div>
    
    <div class="bg-white/90 backdrop-blur-lg rounded-3xl shadow-2xl overflow-hidden p-8 sm:p-10 border border-white/50 relative">
        <!-- Logo/Header Section -->
        <div class="text-center mb-8">
            <a href="{{ route('home') }}" class="inline-block mb-6">
                <img src="{{ asset('assets/logo/logo-logeva.png') }}"
                     alt="Logo Logeva - Gugus Mitigasi Lebak Selatan"
                     class="mx-auto w-full max-w-[280px] h-auto object-contain transform hover:scale-105 transition-transform duration-300">
            </a>
            <h2 class="text-3xl font-extrabold text-gray-800 tracking-tight">Selamat Datang</h2>
            <p class="text-gray-500 mt-2 text-sm">Masuk ke Logeva untuk mengelola data</p>
        </div>

        @if (session()->has('success'))
            <div class="bg-green-50 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-start" role="alert">
                <i class="fas fa-check-circle mt-0.5 mr-2 text-green-500"></i>
                <span class="block text-sm sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        <!-- Login Form -->
        <form action="{{route('login.store') }}" method="POST" class="space-y-5">
            @csrf
            
            <!-- Email Field -->
            <div>
                <label for="email" class="block text-sm font-semibold text-gray-700 mb-1.5 ml-1">Alamat Email</label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-500 transition-colors">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                           class="block w-full pl-11 pr-4 py-3 bg-gray-50 border @error('email') border-red-500 @else border-gray-200 @enderror rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 focus:bg-white transition-all duration-300" 
                           placeholder="email@contoh.com">
                </div>
                @error('email')
                    <p class="mt-1.5 text-sm text-red-500 flex items-center ml-1"><i class="fas fa-exclamation-circle mr-1"></i> {{ $message }}</p>
                @enderror
            </div>

            <!-- Password Field -->
            <div>
                <div class="flex justify-between items-center mb-1.5 ml-1">
                    <label for="password" class="block text-sm font-semibold text-gray-700">Password</label>
                </div>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-500 transition-colors">
                        <i class="fas fa-lock"></i>
                    </div>
                    <input type="password" id="password" name="password" 
                           class="block w-full pl-11 pr-4 py-3 bg-gray-50 border @error('password') border-red-500 @else border-gray-200 @enderror rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 focus:bg-white transition-all duration-300" 
                           placeholder="••••••••">
                </div>
                @error('password')
                    <p class="mt-1.5 text-sm text-red-500 flex items-center ml-1"><i class="fas fa-exclamation-circle mr-1"></i> {{ $message }}</p>
                @enderror
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button type="submit" id="btn-login" 
                        class="w-full flex justify-center py-3.5 px-4 rounded-xl shadow-md text-sm font-bold text-white bg-gradient-to-r from-red-600 to-red-600 hover:from-gray-700 hover:to-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transform hover:-translate-y-0.5 transition-all duration-300">
                    <i class="fas fa-sign-in-alt mr-2 mt-0.5"></i> Masuk Sekarang
                </button>
            </div>
        </form>

        <!-- Sign Up Link -->
        <div class="mt-8 pt-6 border-t border-gray-100 text-center">
            <p class="text-sm text-gray-500">
                Belum memiliki akun?
                <a href="{{ route('register') }}" class="font-bold text-blue-600 hover:text-indigo-600 hover:underline transition-colors ml-1">Daftar Akun Baru</a>
            </p>
        </div>
    </div>
</div>
@endsection