@extends('layouts.admin')

@section('title', 'Pratinjau Import Desa')

@section('content')
@php
    $badge = [
        'tambah'   => ['Ditambahkan', 'bg-emerald-100 text-emerald-700 border-emerald-200', 'fa-plus'],
        'perbarui' => ['Diperbarui',  'bg-blue-100 text-blue-700 border-blue-200',          'fa-pen'],
        'sama'     => ['Tidak berubah','bg-slate-100 text-slate-500 border-slate-200',      'fa-equals'],
        'error'    => ['Error',       'bg-rose-100 text-rose-700 border-rose-200',          'fa-triangle-exclamation'],
    ];
@endphp
<div class="relative z-10 container mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10 min-h-screen flex flex-col gap-8">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white/60 backdrop-blur-xl p-6 md:p-8 rounded-3xl border border-white/80 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-50 border border-blue-100/50 text-blue-600 text-xs font-bold tracking-wide uppercase mb-3">
                <i class="fas fa-file-import opacity-70"></i> Import Data Desa
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 tracking-tight">Pratinjau Import</h1>
            <p class="text-slate-500 mt-1 font-medium text-sm">
                File: <span class="font-bold text-slate-700">{{ $namaFile }}</span> · Periksa data di bawah sebelum disimpan.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('admin.desa.index') }}" class="inline-flex items-center gap-2 bg-white hover:bg-slate-50 text-slate-600 font-bold px-5 py-3 rounded-xl shadow-sm border border-slate-200">
                <i class="fas fa-xmark text-sm"></i> Batal
            </a>
            @if ($jumlahValid > 0)
                <form method="POST" action="{{ route('admin.desa.import.confirm') }}"
                      onsubmit="return confirm('Simpan {{ $jumlahValid }} data desa ke sistem?');">
                    @csrf
                    <button class="inline-flex items-center gap-2 bg-gradient-to-tr from-emerald-600 to-green-500 hover:from-emerald-700 hover:to-green-600 text-white font-bold px-5 py-3 rounded-xl shadow-lg shadow-emerald-500/30">
                        <i class="fas fa-check text-sm"></i> Konfirmasi Import ({{ $jumlahValid }})
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Ringkasan -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach (['tambah', 'perbarui', 'sama', 'error'] as $s)
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ $badge[$s][0] }}</p>
                <p class="text-3xl font-black mt-1 {{ $s === 'error' ? 'text-rose-600' : ($s === 'tambah' ? 'text-emerald-600' : ($s === 'perbarui' ? 'text-blue-600' : 'text-slate-500')) }}">
                    {{ $ringkasan[$s] ?? 0 }}
                </p>
            </div>
        @endforeach
    </div>

    @if (($ringkasan['error'] ?? 0) > 0)
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            <i class="fas fa-circle-info mr-1"></i>
            Baris berstatus <b>Error</b> akan dilewati. Perbaiki di file Excel lalu upload ulang jika ingin ikut disimpan.
        </div>
    @endif

    @if ($jumlahValid === 0)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700">
            <i class="fas fa-circle-info mr-1"></i>
            Tidak ada data baru atau perubahan yang bisa disimpan.
        </div>
    @endif

    <!-- Tabel pratinjau -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden">
        <div class="p-6 md:p-8 overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50">
                        <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">Baris</th>
                        <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">Status</th>
                        <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">Nama Desa</th>
                        <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">Alamat Desa</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100/80">
                    @foreach ($baris as $b)
                        @php [$label, $warna, $ikon] = $badge[$b['status']]; @endphp
                        <tr class="{{ $b['status'] === 'error' ? 'bg-rose-50/40' : '' }}">
                            <td class="py-3 px-4 text-sm font-semibold text-slate-400">{{ $b['row'] }}</td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold border {{ $warna }}">
                                    <i class="fas {{ $ikon }} text-[10px]"></i> {{ $label }}
                                </span>
                                @foreach ($b['error'] as $e)
                                    <p class="text-xs text-rose-600 mt-1">{{ $e }}</p>
                                @endforeach
                            </td>
                            <td class="py-3 px-4 text-sm font-bold text-slate-800">{{ $b['nama_desa'] ?: '-' }}</td>
                            <td class="py-3 px-4 text-sm text-slate-600">
                                {{ $b['alamat_desa'] ?: '-' }}
                                @if ($b['status'] === 'perbarui')
                                    <p class="text-xs text-slate-400 mt-1">Sebelumnya: <span class="line-through">{{ $b['alamat_lama'] }}</span></p>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
