@extends('layouts.dashboard')

@section('title', 'Profil Saya')

@section('content')
<div class="relative z-10 container mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12 min-h-[calc(100vh-80px)] flex flex-col items-center">

    <!-- Header Section -->
    <div class="w-full max-w-2xl text-center mb-8">
        <h1 class="text-3xl md:text-4xl font-extrabold text-slate-800 tracking-tight">Profil Akun</h1>
        <p class="text-slate-500 mt-2 font-medium">Informasi dan statistik relasi akun Anda di sistem GMLS.</p>
    </div>

    <!-- Profile Card (Glassmorphism) -->
    <div class="w-full max-w-2xl bg-white/60 backdrop-blur-xl border border-white/80 rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden relative">
        
        <!-- Cover Art -->
        <div class="h-32 md:h-40 w-full bg-gradient-to-r from-emerald-500 to-teal-400 relative overflow-hidden">
            <!-- Decorative overlay -->
            <div class="absolute inset-0 bg-white/10 mix-blend-overlay"></div>
            <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMiIgY3k9IjIiIHI9IjIiIGZpbGw9InJnYmEoMjU1LDI1NSwyNTUsMC4wNykiLz48L3N2Zz4=')] opacity-50"></div>
            <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-white/20 rounded-full blur-2xl"></div>
            <div class="absolute -top-10 -left-10 w-32 h-32 bg-teal-300/30 rounded-full blur-2xl"></div>
        </div>

        <!-- Content Area -->
        <div class="px-6 md:px-10 pb-10 relative">
            
            <!-- Avatar Overlay -->
            <div class="flex flex-col md:flex-row items-center md:items-end gap-6 -mt-16 md:-mt-20 mb-8">
                <div class="relative group">
                    <div class="w-32 h-32 md:w-40 md:h-40 rounded-full p-1.5 bg-white shadow-xl flex-shrink-0 relative z-10">
                        @if(Auth::user()->relawan && Auth::user()->relawan->avatar)
                            <img src="{{asset('storage/'. Auth::user()->relawan->avatar)}}" alt="Avatar" class="w-full h-full rounded-full object-cover">
                        @else
                            <div class="w-full h-full rounded-full bg-slate-100 flex items-center justify-center">
                                <i class="fas fa-user text-4xl text-slate-300"></i>
                            </div>
                        @endif
                    </div>
                    <!-- Role Badge overlaying the avatar -->
                    <div class="absolute bottom-2 right-2 md:bottom-4 md:right-4 z-20">
                        <div class="bg-gradient-to-r from-emerald-500 to-teal-500 border-2 border-white text-white w-8 h-8 rounded-full flex items-center justify-center shadow-lg" title="Relawan Terverifikasi">
                            <i class="fas fa-check-circle text-sm"></i>
                        </div>
                    </div>
                </div>

                <div class="text-center md:text-left flex-1 md:pb-2">
                    <h2 class="text-2xl md:text-3xl font-extrabold text-slate-800 tracking-tight">{{ Auth::user()->name }}</h2>
                    <p class="text-emerald-600 font-bold mt-1 text-sm md:text-base flex items-center justify-center md:justify-start gap-1.5">
                        <i class="fas fa-id-badge"></i> {{ Auth::user()->email }}
                    </p>
                </div>
            </div>

            <hr class="border-slate-100 mb-8">

            <!-- Stats & Info -->
            <div class="gap-6 mb-10">
                <!-- Data Counter -->
                <div class="bg-slate-50/50 hover:bg-slate-50 border border-slate-100 rounded-2xl p-6 transition-all group">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-campground text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-500 uppercase tracking-widest mb-1">Posko Dilaporkan</p>
                        <h4 class="text-3xl font-black text-slate-800">{{ Auth::user()->relawan && Auth::user()->relawan->lokasi ? Auth::user()->relawan->lokasi->count() : 0 }} <span class="text-base font-bold text-slate-400">Tergabung</span></h4>
                    </div>
                </div>
            </div>

            <!-- Logout Section -->
            <div class="flex justify-center border-t border-slate-100 pt-8 mt-2">
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                    @csrf
                </form>
                <button onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="inline-flex items-center gap-2 px-8 py-3.5 bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold rounded-xl border border-rose-100 transition-all hover:shadow-md hover:shadow-rose-100 w-full sm:w-auto justify-center group">
                    <i class="fas fa-sign-out-alt md:group-hover:-translate-x-1 transition-transform"></i>
                    Keluar dari Sistem
                </button>
            </div>

        </div>
    </div>
</div>
@endsection