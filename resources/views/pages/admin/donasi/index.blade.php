@extends('layouts.admin')

@section('title', 'Donasi')

@section('content')
@php
    $warna = [
        'green' => 'bg-green-100 text-green-700',
        'red'   => 'bg-red-100 text-red-700',
        'amber' => 'bg-amber-100 text-amber-700',
        'blue'  => 'bg-blue-100 text-blue-700',
        'slate' => 'bg-slate-100 text-slate-700',
    ];
@endphp

<div class="relative z-10 container mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10 flex flex-col gap-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white/60 backdrop-blur-xl p-6 md:p-8 rounded-3xl border border-white/80 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-red-50 border border-red-100/50 text-red-600 text-xs font-bold tracking-wide uppercase mb-3">
                <i class="fas fa-hand-holding-heart opacity-70"></i> Donasi
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 tracking-tight">Donasi Masuk</h1>
            <p class="text-slate-500 mt-1 font-medium text-sm">Cek transfer / barang yang datang, lalu catat penyalurannya supaya donatur bisa melacak.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.gudang.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white border border-slate-200 font-bold text-slate-700 hover:bg-slate-50">
                <i class="fas fa-warehouse"></i> Gudang Pusat
            </a>
            <a href="{{ route('donasi.laporan') }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-red-600 text-white font-bold hover:bg-red-700">
                <i class="fas fa-file-invoice"></i> Laporan Publik
            </a>
        </div>
    </div>

    {{-- FILTER STATUS --}}
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.donasi.index', array_filter(['jenis' => $jenis, 'cari' => $cari])) }}"
           class="px-4 py-2 rounded-xl text-sm font-bold {{ ! $status ? 'bg-slate-800 text-white' : 'bg-white border border-slate-200 text-slate-600' }}">
            Semua <span class="opacity-70">{{ $jumlahStatus->sum() }}</span>
        </a>
        @foreach (\App\Models\Donasi::STATUS as $kunci => [$label, $w])
            <a href="{{ route('admin.donasi.index', array_filter(['status' => $kunci, 'jenis' => $jenis, 'cari' => $cari])) }}"
               class="px-4 py-2 rounded-xl text-sm font-bold {{ $status === $kunci ? 'bg-slate-800 text-white' : 'bg-white border border-slate-200 text-slate-600' }}">
                {{ $label }} <span class="opacity-70">{{ $jumlahStatus[$kunci] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <form method="GET" class="flex flex-wrap gap-2">
        <input type="hidden" name="status" value="{{ $status }}">
        <select name="jenis" class="rounded-xl border border-slate-200 px-3 py-2 text-sm bg-white">
            <option value="">Uang &amp; barang</option>
            <option value="uang" @selected($jenis === 'uang')>Uang</option>
            <option value="logistik" @selected($jenis === 'logistik')>Barang</option>
        </select>
        <input type="text" name="cari" value="{{ $cari }}" placeholder="Cari kode / nama / kontak" class="rounded-xl border border-slate-200 px-3 py-2 text-sm flex-1 min-w-[200px]">
        <button class="px-4 py-2 rounded-xl bg-slate-800 text-white text-sm font-bold">Cari</button>
    </form>

    <div class="bg-white rounded-3xl border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs font-bold text-slate-500 uppercase">
                    <tr>
                        <th class="text-left px-6 py-4">Kode &amp; tanggal</th>
                        <th class="text-left px-6 py-4">Donatur</th>
                        <th class="text-left px-6 py-4">Donasi</th>
                        <th class="text-left px-6 py-4">Tujuan</th>
                        <th class="text-left px-6 py-4">Status</th>
                        <th class="px-6 py-4"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($donasis as $d)
                        @php $st = $d->labelStatus(); @endphp
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-6 py-4">
                                <p class="font-mono font-bold text-slate-800">{{ $d->kode }}</p>
                                <p class="text-xs text-slate-400">{{ $d->created_at->translatedFormat('d M Y, H:i') }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-semibold text-slate-800">{{ $d->nama_donatur }} @if ($d->anonim)<span class="text-[10px] text-slate-400">(anonim)</span>@endif</p>
                                <p class="text-xs text-slate-500">{{ $d->kontak }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-semibold {{ $d->isUang() ? 'text-green-700' : 'text-blue-700' }}">
                                    <i class="fas {{ $d->isUang() ? 'fa-money-bill-wave' : 'fa-box' }} mr-1"></i>{{ $d->isUang() ? 'Uang' : 'Barang' }}
                                </p>
                                <p class="text-xs text-slate-500 max-w-xs truncate">{{ $d->ringkasan() }}</p>
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                <i class="fas {{ $d->isKePosko() ? 'fa-campground' : 'fa-warehouse' }} text-slate-400 mr-1"></i>{{ $d->namaTujuan() }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold {{ $warna[$st['warna']] }}">
                                    <i class="fas {{ $st['ikon'] }}"></i> {{ $st['label'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.donasi.show', $d) }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-slate-800 text-white text-xs font-bold hover:bg-slate-700">
                                    {{ $d->isMenunggu() ? 'Proses' : 'Detail' }} <i class="fas fa-chevron-right text-[10px]"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-12 text-center text-slate-400">Belum ada donasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($donasis->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">{{ $donasis->links() }}</div>
        @endif
    </div>
</div>
@endsection
