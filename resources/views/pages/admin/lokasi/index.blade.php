@extends('layouts.admin')

@section('title', 'Data Lokasi')

@section('content')
<div class="relative z-10 container mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10 min-h-screen flex flex-col gap-8">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white/60 backdrop-blur-xl p-6 md:p-8 rounded-3xl border border-white/80 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-50 border border-blue-100/50 text-blue-600 text-xs font-bold tracking-wide uppercase mb-3">
                <i class="fas fa-campground opacity-70"></i>
                Manajemen Pengungsian
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 tracking-tight">
                Data Lokasi Pengungsian
            </h1>
            <p class="text-slate-500 mt-1 font-medium text-sm">Kelola informasi titik-titik lokasi posko pengungsian bencana.</p>
        </div>

        <div class="flex flex-wrap items-center gap-3 mt-2 sm:mt-0">
            <!-- Add Button -->
            <a href="{{route('admin.lokasi.create')}}" class="inline-flex items-center justify-center gap-2 bg-gradient-to-tr from-blue-600 to-indigo-500 hover:from-blue-700 hover:to-indigo-600 text-white font-bold px-4 py-2.5 sm:px-5 sm:py-3 rounded-xl shadow-lg shadow-blue-500/30 transform transition-transform duration-300 hover:scale-[1.02] border border-blue-400/20">
                <i class="fas fa-plus text-sm"></i> Tambah Lokasi
            </a>
            
            <!-- Export Button -->
            <a href="{{ route('admin.lokasi.export') }}" class="inline-flex items-center justify-center gap-2 bg-gradient-to-tr from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-bold px-4 py-2.5 sm:px-5 sm:py-3 rounded-xl shadow-lg shadow-emerald-500/30 transform transition-transform duration-300 hover:scale-[1.02] border border-emerald-400/20">
                <i class="fas fa-file-excel text-sm"></i> Export Excel
            </a>

            <!-- Import Button (export lokasi sudah ada di atas, jadi exportRoute = null) -->
            @include('includes.admin-import-export', [
                'judul'         => 'Lokasi Pengungsian',
                'keterangan'    => 'Lokasi dengan nama & desa yang sama akan diperbarui, bukan dibuat ganda.',
                'templateRoute' => route('admin.lokasi.import.template'),
                'previewRoute'  => route('admin.lokasi.import.preview'),
                'exportRoute'   => null,
                'templateFile'  => 'template_import_lokasi.xlsx',
            ])
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden">
        <div class="p-6 md:p-8">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse" id="dataTable">
                    <thead>
                        <tr class="bg-slate-50/50">
                            <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100 rounded-tl-2xl w-16">No</th>
                            <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100 w-24">Media</th>
                            <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">Informasi Lokasi</th>
                            <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100 rounded-tr-2xl text-center w-40">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100/80 bg-white">
                        @foreach ($lokasis as $lokasi)
                        <tr class="hover:bg-slate-50/50 transition-colors group">
                            <!-- No -->
                            <td class="py-4 px-6 text-sm font-semibold text-slate-500 align-middle">{{$loop->iteration}}</td>
                            
                            <!-- Media / Image -->
                            <td class="py-4 px-6 align-middle">
                                <div class="w-20 h-20 rounded-xl overflow-hidden border-2 border-slate-100 shadow-sm shrink-0 bg-slate-100">
                                <img src="{{asset('storage/'. $lokasi->gambar_lokasi)}}" 
                                alt="Gambar Posko" 
                                class="w-full h-full object-cover object-center transition-all duration-300">
                                </div>
                            </td>

                            <!-- Informasi Lokasi -->
                            <td class="py-4 px-6 align-middle">
                                <div class="flex flex-col justify-center">
                                    <h4 class="text-sm font-black text-slate-800 tracking-tight">{{$lokasi->nama_lokasi}}</h4>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                                            <i class="fas fa-map-pin mr-1 text-slate-400"></i> Desa {{$lokasi->desa->nama_desa}}
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-2 line-clamp-2 max-w-sm" title="{{$lokasi->alamat_lokasi}}">
                                        {{$lokasi->alamat_lokasi}}
                                    </p>
                                    @if ($paketPada = $lokasi->paketTerakhirPada())
                                        @php $warna = \App\Models\RiwayatImportPaket::warnaUmur($paketPada); @endphp
                                        <span class="self-start inline-flex items-center mt-2 px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-{{ $warna }}-50 text-{{ $warna }}-700 border border-{{ $warna }}-200"
                                              title="Data dari aplikasi offline, dibuat di HP {{ $paketPada->format('d-m-Y H:i') }} WIB">
                                            <i class="fas fa-file-archive mr-1"></i> Paket terakhir: {{ \App\Models\RiwayatImportPaket::teksUmur($paketPada) }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- Aksi -->
                            <td class="py-4 px-6 align-middle text-center">
                                <div class="flex items-center justify-center gap-2 opacity-80 group-hover:opacity-100 transition-opacity">
                                    <a href="{{route('admin.lokasi.show', $lokasi->id)}}" class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-colors border border-blue-100" title="Detail Lokasi">
                                        <i class="fas fa-eye text-xs"></i>
                                    </a>
                                    <a href="{{route('admin.lokasi.edit', $lokasi->id)}}" class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center hover:bg-amber-500 hover:text-white transition-colors border border-amber-100" title="Edit Data">
                                        <i class="fas fa-edit text-xs"></i>
                                    </a>
                                    <form action="{{route('admin.lokasi.destroy', $lokasi->id)}}" method="POST" class="inline-block m-0" onsubmit="return confirm('Apakah Anda yakin ingin menghapus lokasi ini? Data yang terhubung juga mungkin ikut terhapus.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center hover:bg-rose-600 hover:text-white transition-colors border border-rose-100" title="Hapus Permanen">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection