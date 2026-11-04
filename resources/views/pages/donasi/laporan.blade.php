@extends('layouts.dashboard')

@section('title', 'Laporan Donasi')

@section('content')
@php
    $rp = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');
    $persen = $ringkas['dana_diterima'] > 0 ? min(100, round($ringkas['dana_disalurkan'] / $ringkas['dana_diterima'] * 100)) : 0;
@endphp

<div class="max-w-6xl mx-auto space-y-6">

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <a href="{{ route('donasi.index') }}" class="text-sm text-slate-500 hover:text-red-600"><i class="fas fa-arrow-left mr-1"></i> Donasi</a>
            <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 mt-2">Laporan Donasi</h1>
            <p class="text-slate-500">Semua donasi yang sudah diterima GMLS dan ke mana disalurkan. Diperbarui otomatis.</p>
        </div>
        <a href="{{ route('donasi.lacak') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white border border-slate-200 font-semibold text-slate-700 hover:bg-slate-50">
            <i class="fas fa-magnifying-glass-location"></i> Lacak donasi saya
        </a>
    </div>

    {{-- DANA --}}
    <section class="bg-white rounded-2xl border border-slate-200 p-6">
        <div class="grid sm:grid-cols-3 gap-4">
            <div>
                <p class="text-xs font-semibold text-slate-500">Dana diterima</p>
                <p class="text-2xl font-extrabold text-slate-800">{{ $rp($ringkas['dana_diterima']) }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500">Dana disalurkan</p>
                <p class="text-2xl font-extrabold text-green-700">{{ $rp($ringkas['dana_disalurkan']) }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500">Belum disalurkan</p>
                <p class="text-2xl font-extrabold text-slate-800">{{ $rp(max(0, $ringkas['dana_diterima'] - $ringkas['dana_disalurkan'])) }}</p>
            </div>
        </div>
        <div class="mt-4">
            <div class="h-3 rounded-full bg-slate-100 overflow-hidden" role="progressbar" aria-valuenow="{{ $persen }}" aria-valuemin="0" aria-valuemax="100" aria-label="Dana tersalurkan">
                <div class="h-full bg-green-500" style="width: {{ $persen }}%"></div>
            </div>
            <p class="text-xs text-slate-500 mt-1">{{ $persen }}% dana sudah disalurkan · {{ number_format($ringkas['jumlah_donatur'], 0, ',', '.') }} donatur · {{ $ringkas['posko_terbantu'] }} posko terbantu</p>
        </div>
    </section>

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- BARANG DITERIMA --}}
        <section class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="font-bold text-slate-800">Barang donasi diterima</h2>
            </div>
            <ul class="divide-y divide-slate-100 text-sm">
                @forelse ($barang as $b)
                    <li class="px-6 py-3 flex justify-between gap-3">
                        <span class="text-slate-700">{{ $b->nama_item }}</span>
                        <span class="font-bold text-slate-800">{{ number_format($b->total, 0, ',', '.') }} {{ $b->satuan }}</span>
                    </li>
                @empty
                    <li class="px-6 py-8 text-center text-slate-400">Belum ada barang donasi.</li>
                @endforelse
            </ul>
        </section>

        {{-- PENYALURAN PER TUJUAN --}}
        <section class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="font-bold text-slate-800">Penyaluran per posko</h2>
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($perPosko as $p)
                    <li class="px-6 py-4">
                        <div class="flex flex-wrap justify-between gap-2">
                            <p class="font-bold text-slate-800">{{ $p['nama'] }} <span class="font-normal text-sm text-slate-500">{{ $p['desa'] ? '· ' . $p['desa'] : '' }}</span></p>
                            <p class="text-xs text-slate-400">{{ $p['kali'] }}x penyaluran · terakhir {{ \Illuminate\Support\Carbon::parse($p['terakhir'])->translatedFormat('d M Y') }}</p>
                        </div>
                        <p class="text-sm text-slate-600 mt-1">
                            @if ($p['dana'])<b>{{ $rp($p['dana']) }}</b>@endif
                            @if ($p['dana'] && $p['barang']->isNotEmpty()) · @endif
                            {{ $p['barang']->map(fn ($b) => $b['nama'] . ' ' . number_format($b['jumlah'], 0, ',', '.') . ' ' . $b['satuan'])->implode(', ') }}
                        </p>
                    </li>
                @empty
                    <li class="px-6 py-8 text-center text-slate-400">Belum ada penyaluran.</li>
                @endforelse
            </ul>
        </section>
    </div>

    {{-- DAFTAR DONASI --}}
    <section class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h2 class="font-bold text-slate-800">Daftar donasi diterima</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                    <tr>
                        <th class="text-left px-6 py-3">Tanggal</th>
                        <th class="text-left px-6 py-3">Donatur</th>
                        <th class="text-left px-6 py-3">Donasi</th>
                        <th class="text-left px-6 py-3">Untuk</th>
                        <th class="text-left px-6 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($donasis as $d)
                        @php $st = $d->labelStatus(); @endphp
                        <tr>
                            <td class="px-6 py-3 text-slate-500 whitespace-nowrap">{{ $d->diverifikasi_pada?->translatedFormat('d M Y') }}</td>
                            <td class="px-6 py-3 font-semibold text-slate-800">{{ $d->namaTampil() }}</td>
                            <td class="px-6 py-3 text-slate-600">{{ $d->ringkasan() }}</td>
                            <td class="px-6 py-3 text-slate-600">{{ $d->namaTujuan() }}</td>
                            <td class="px-6 py-3"><span class="text-xs font-bold text-{{ $st['warna'] }}-700">{{ $st['label'] }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-8 text-center text-slate-400">Belum ada donasi yang diterima.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($donasis->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">{{ $donasis->links() }}</div>
        @endif
    </section>
</div>
@endsection
