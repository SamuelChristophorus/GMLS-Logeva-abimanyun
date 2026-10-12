@extends('layouts.app')

@section('title', 'Lokasi Saya')

@section('content')
<div class="relative z-10 container mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12 min-h-[calc(100vh-80px)] flex flex-col gap-8">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white/60 backdrop-blur-xl p-6 md:p-8 rounded-3xl border border-white/80 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">

        <div>

            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-50 border border-blue-100/50 text-blue-600 text-xs font-bold tracking-wide uppercase mb-3">

                <i class="fas fa-map-marked-alt opacity-70"></i>

                Daftar Integrasi

            </div>

            <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 tracking-tight">

                Lokasi Saya

            </h1>

            <p class="text-slate-500 mt-1 font-medium text-sm">

                Kelola dan pantau seluruh posko pengungsian yang Anda laporkan.

            </p>

        </div>

        <div>

            <a href="{{route('lokasi.create')}}"
               class="inline-flex items-center justify-center gap-2 bg-gradient-to-tr from-emerald-600 to-teal-500 hover:from-emerald-700 hover:to-teal-600 text-white font-bold px-6 py-3 rounded-xl shadow-lg shadow-emerald-500/30 transform transition-all hover:-translate-y-0.5 border border-emerald-400/20">

                <i class="fas fa-plus-circle text-sm opacity-90"></i>

                Tambah Lokasi

            </a>

        </div>

    </div>

    <!-- Tabs -->
    <div class="bg-white/50 backdrop-blur-md rounded-2xl p-1.5 border border-white max-w-2xl mx-auto shadow-sm">

        <nav class="flex flex-wrap justify-between md:justify-center gap-1" aria-label="Tabs">

            <a href="{{url()->current() . '?status=pending'}}"
               class="flex-1 text-center {{request('status') === 'pending' || !request('status') ? 'bg-white shadow-sm text-blue-600 font-bold border border-slate-100' : 'text-slate-500 hover:text-slate-700 hover:bg-white/50 font-medium border border-transparent'}} rounded-xl py-2.5 px-4 text-sm transition-all duration-200">

                Menunggu

            </a>

            <a href="{{url()->current() . '?status=approved'}}"
               class="flex-1 text-center {{request('status') === 'approved' ? 'bg-white shadow-sm text-emerald-600 font-bold border border-slate-100' : 'text-slate-500 hover:text-slate-700 hover:bg-white/50 font-medium border border-transparent'}} rounded-xl py-2.5 px-4 text-sm transition-all duration-200">

                Disetujui

            </a>

            <a href="{{url()->current() . '?status=rejected'}}"
               class="flex-1 text-center {{request('status') === 'rejected' ? 'bg-white shadow-sm text-rose-600 font-bold border border-slate-100' : 'text-slate-500 hover:text-slate-700 hover:bg-white/50 font-medium border border-transparent'}} rounded-xl py-2.5 px-4 text-sm transition-all duration-200">

                Ditolak

            </a>

        </nav>

    </div>

    <!-- GRID -->
    @if($lokasis->count() > 0)

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">

        @foreach ($lokasis as $lokasi)

        <div class="bg-white rounded-3xl overflow-hidden shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group flex flex-col h-full">

            <!-- IMAGE -->
            <a href="{{ route('lokasi.show', $lokasi->id) }}"
               class="block relative h-48 sm:h-56 overflow-hidden">

                <img src="{{ asset('storage/'. $lokasi->gambar_lokasi) }}"
                     alt="{{ $lokasi->nama_lokasi }}"
                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">

                <!-- OVERLAY -->
                <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-transparent to-transparent opacity-60"></div>

                <!-- STATUS -->
                @if ($lokasi->statusLokasi->last()?->status === 'pending')

                <div class="absolute top-4 right-4 bg-amber-50 text-amber-600 border border-amber-200/60 shadow-lg text-[10px] font-bold px-3 py-1.5 rounded-full uppercase tracking-widest backdrop-blur-md">

                    Pending

                </div>

                @elseif ($lokasi->statusLokasi->last()?->status === 'approved')

                <div class="absolute top-4 right-4 bg-emerald-50 text-emerald-600 border border-emerald-200/60 shadow-lg text-[10px] font-bold px-3 py-1.5 rounded-full uppercase tracking-widest backdrop-blur-md">

                    Disetujui

                </div>

                @elseif ($lokasi->statusLokasi->last()?->status === 'rejected')

                <div class="absolute top-4 right-4 bg-rose-50 text-rose-600 border border-rose-200/60 shadow-lg text-[10px] font-bold px-3 py-1.5 rounded-full uppercase tracking-widest backdrop-blur-md">

                    Ditolak

                </div>

                @elseif ($lokasi->statusLokasi->last()?->status == 'done')

                <div class="absolute top-4 right-4 bg-purple-50 text-purple-600 border border-purple-200/60 shadow-lg text-[10px] font-bold px-3 py-1.5 rounded-full uppercase tracking-widest backdrop-blur-md">

                    Selesai

                </div>

                @endif

            </a>

            <!-- CONTENT -->
            <div class="p-5 md:p-6 flex flex-col flex-1 relative bg-white">

                <a href="{{ route('lokasi.show', $lokasi->id) }}"
                   class="block mb-2 group-hover:text-emerald-600 transition-colors">

                    <h3 class="text-xl font-bold text-slate-800 line-clamp-1 leading-tight">

                        {{ $lokasi->nama_lokasi }}

                    </h3>

                </a>

                <div class="flex items-start text-slate-500 mb-4 flex-1">

                    <i class="fas fa-map-marker-alt mt-1.5 mr-2.5 text-slate-300"></i>

                    <span class="text-sm leading-relaxed line-clamp-2">

                        {{ $lokasi->alamat_lokasi }}

                    </span>

                </div>

                <!-- FOOTER -->
                <div class="pt-4 border-t border-slate-100 flex items-center justify-between mt-auto">

                    <div class="flex items-center gap-2">

                        <div class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">

                            <i class="fas fa-map-signs text-[10px]"></i>

                        </div>

                        <span class="text-xs font-bold text-slate-600 line-clamp-1">

                            {{ $lokasi->desa->nama_desa }}

                        </span>

                    </div>

                    <div class="text-[11px] font-medium text-slate-400 bg-slate-50 px-2 py-1 rounded-md">

                        {{ \Carbon\Carbon::parse($lokasi->created_at)->timezone('Asia/Jakarta')->format('d M y') }}

                    </div>

                </div>

            </div>
            
            @if ($lokasi->statusLokasi->last()?->status === 'approved')
            <!-- BUTTON PENGUNGSI -->
            <div class="px-5 pb-5">

                <a href="{{ route('pengungsi.index', $lokasi->id) }}"
                   class="group relative inline-flex items-center justify-center gap-3
                          w-full overflow-hidden
                          px-5 py-4 rounded-2xl
                          bg-gradient-to-br from-slate-900 via-slate-800 to-black
                          hover:from-slate-800 hover:to-black
                          text-white font-bold
                          shadow-lg transition-all duration-500 hover:-translate-y-1">

                    <!-- GLOW -->
                    <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition duration-500">

                        <div class="absolute -top-10 -right-10 w-32 h-32 bg-white/10 rounded-full blur-3xl"></div>

                    </div>

                    <!-- ICON -->
                    <div class="relative z-10 w-11 h-11 rounded-2xl
                                bg-white/10 border border-white/10
                                flex items-center justify-center">

                        <i class="fas fa-users text-white"></i>

                    </div>

                    <!-- TEXT -->
                    <div class="relative z-10 flex flex-col items-start text-left">

                        <span class="text-[10px] uppercase tracking-[0.25em] text-slate-400">

                            Evacuation

                        </span>

                        <span class="text-sm font-black">

                            Kelola Pengungsi

                        </span>

                    </div>

                    <!-- ARROW -->
                    <i class="fas fa-arrow-right relative z-10 text-sm"></i>

                </a>
            </div>
        @endif
        </div>

        @endforeach

    </div>

    @else

    <!-- EMPTY -->
    <div class="flex flex-col items-center justify-center py-16 px-4 bg-white/50 backdrop-blur-sm rounded-3xl border border-white border-dashed text-center">

        <div class="w-24 h-24 rounded-full bg-slate-100/80 flex items-center justify-center text-slate-300 mb-4 shadow-inner">

            <i class="fas fa-folder-open text-4xl"></i>

        </div>

        <h3 class="text-xl font-bold text-slate-700 mb-2">

            Belum ada data

        </h3>

        <p class="text-slate-500 max-w-sm">

            Mulai laporkan posko pengungsian di lapangan untuk mengorganisir data kedaruratan.

        </p>

    </div>

    @endif

</div>
@endsection