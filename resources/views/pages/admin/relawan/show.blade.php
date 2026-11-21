@extends('layouts.admin')

@section('title', 'Detail Relawan')

@section('content')
<div class="relative z-10 container mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10 min-h-screen flex flex-col gap-8">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white/60 backdrop-blur-xl p-6 md:p-8 rounded-3xl border border-white/80 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-50 border border-blue-100/50 text-blue-600 text-xs font-bold tracking-wide uppercase mb-3">
                <i class="fas fa-user-circle opacity-70"></i>
                Profil Relawan
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 tracking-tight">
                {{ $relawan->user->name }}
            </h1>
            <p class="text-slate-500 mt-1 font-medium text-sm">Informasi lengkap profil dan akun relawan.</p>
        </div>

        <div>
            <a href="{{route('admin.relawan.index')}}" class="inline-flex items-center justify-center gap-2 bg-white hover:bg-slate-50 text-slate-600 font-bold px-5 py-3 rounded-xl shadow-sm border border-slate-200 transition-all hover:-translate-x-1">
                <i class="fas fa-arrow-left text-sm opacity-70"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Detail Card -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden">
        
        <!-- Cover Background -->
        <div class="h-32 bg-gradient-to-r from-blue-500 to-indigo-600 w-full relative">
            <!-- Decorative overlay -->
            <div class="absolute inset-0 bg-white/10" style="background-image: radial-gradient(circle at 2px 2px, rgba(255,255,255,0.15) 1px, transparent 0); background-size: 20px 20px;"></div>
        </div>

        <div class="p-6 md:p-8 relative">
            
            <!-- Avatar positioned over cover -->
            <div class="absolute -top-16 left-6 md:left-8">
                <div class="w-24 h-24 sm:w-32 sm:h-32 rounded-3xl bg-white p-1.5 shadow-xl border border-slate-100">
                    <img src="{{asset('storage/'. $relawan->avatar)}}" alt="Avatar" class="w-full h-full rounded-2xl object-cover">
                </div>
            </div>

            <!-- Spacing for avatar -->
            <div class="h-12 sm:h-20"></div>

            <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2 mt-4">
                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <i class="fas fa-id-card"></i>
                </div>
                Data Pribadi & Akun
            </h3>

            <div class="bg-slate-50/50 rounded-2xl border border-slate-100 p-2">
                <div class="grid grid-cols-1 divide-y divide-slate-100/80">
                    
                    <div class="flex flex-col sm:flex-row p-4 hover:bg-white transition-colors rounded-xl">
                        <div class="w-full sm:w-1/3 md:w-1/4 text-sm font-bold text-slate-500 mb-1 sm:mb-0 flex items-center gap-2">
                            <i class="fas fa-user text-slate-300 w-4 text-center"></i> Nama Lengkap
                        </div>
                        <div class="w-full sm:w-2/3 md:w-3/4 text-sm font-bold text-slate-800">
                            {{ $relawan->user->name }}
                        </div>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row p-4 hover:bg-white transition-colors rounded-xl">
                        <div class="w-full sm:w-1/3 md:w-1/4 text-sm font-bold text-slate-500 mb-1 sm:mb-0 flex items-center gap-2">
                            <i class="fas fa-envelope text-slate-300 w-4 text-center"></i> Alamat Email
                        </div>
                        <div class="w-full sm:w-2/3 md:w-3/4 text-sm text-slate-800">
                            {{ $relawan->user->email }}
                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-100">Terverifikasi</span>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row p-4 hover:bg-white transition-colors rounded-xl">
                        <div class="w-full sm:w-1/3 md:w-1/4 text-sm font-bold text-slate-500 mb-1 sm:mb-0 flex items-center gap-2">
                            <i class="fas fa-calendar-plus text-slate-300 w-4 text-center"></i> Akun Dibuat
                        </div>
                        <div class="w-full sm:w-2/3 md:w-3/4 text-sm text-slate-800">
                            {{ $relawan->created_at?->timezone(config('app.timezone'))->format('d-m-Y H:i') ?? '-' }} WIB
                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                {{ \App\Support\LamaWaktu::sejak($relawan->created_at) }}
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row p-4 hover:bg-white transition-colors rounded-xl">
                        <div class="w-full sm:w-1/3 md:w-1/4 text-sm font-bold text-slate-500 mb-1 sm:mb-0 flex items-center gap-2">
                            <i class="fas fa-map-marker-alt text-slate-300 w-4 text-center"></i> Posko Tugas
                        </div>
                        <div class="w-full sm:w-2/3 md:w-3/4 text-sm text-slate-800">
                            @php $daftarPosko = $relawan->lokasi; @endphp
                            @forelse ($daftarPosko as $p)
                                <a href="{{ route('admin.lokasi.show', $p->id) }}" class="font-bold text-blue-600 hover:underline">{{ $p->nama_lokasi }}</a>@if (! $loop->last), @endif
                            @empty
                                <span class="text-slate-500">Belum ditugaskan</span>
                            @endforelse
                            @if ($daftarPosko->count() > 1)
                                <span class="block text-xs font-bold text-rose-600 mt-1">Satu relawan seharusnya hanya di satu posko: atur lewat Edit Relawan.</span>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row p-4 hover:bg-white transition-colors rounded-xl">
                        <div class="w-full sm:w-1/3 md:w-1/4 text-sm font-bold text-slate-500 mb-1 sm:mb-0 flex items-center gap-2">
                            <i class="fas fa-user-shield text-slate-300 w-4 text-center"></i> Role Sistem
                        </div>
                        <div class="w-full sm:w-2/3 md:w-3/4 text-sm text-slate-800">
                            Relawan Lapangan
                        </div>
                    </div>
                    
                </div>
            </div>
            
            <div class="mt-8 flex justify-end gap-3">
                <a href="{{route('admin.relawan.edit', $relawan->id)}}" class="inline-flex items-center gap-2 bg-amber-50 hover:bg-amber-100 text-amber-600 font-bold px-5 py-2.5 rounded-xl transition-colors border border-amber-200/50">
                    <i class="fas fa-edit text-sm"></i> Edit Relawan
                </a>
            </div>
        </div>
        
    </div>
</div>
@endsection