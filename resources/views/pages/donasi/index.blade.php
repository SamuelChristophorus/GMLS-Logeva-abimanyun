@extends('layouts.dashboard')

@section('title', 'Donasi')

@section('content')
@php
    $rp = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');
    $warnaTingkat = [
        'segera' => ['bg-red-100 text-red-700', 'Butuh segera'],
        'pantau' => ['bg-amber-100 text-amber-700', 'Perlu dipantau'],
    ];
@endphp

<div class="max-w-6xl mx-auto space-y-6">

    {{-- HERO --}}
    <section class="rounded-3xl bg-gradient-to-br from-red-600 to-rose-700 text-white p-6 md:p-10 shadow-lg">
        <p class="text-xs font-bold uppercase tracking-widest text-red-100">Donasi untuk penyintas bencana Lebak Selatan</p>
        <h1 class="text-2xl md:text-4xl font-extrabold mt-2 max-w-2xl">Salurkan bantuan, lalu lihat sendiri ke mana bantuan Anda sampai.</h1>
        <p class="mt-3 text-red-100 max-w-2xl">
            Donasi uang maupun barang untuk GMLS Pusat atau langsung ke posko. Setiap donasi mendapat
            <b class="text-white">kode lacak</b> untuk memantau penyalurannya.
        </p>
        <div class="mt-6 flex flex-wrap gap-3">
            <a href="{{ route('donasi.create') }}" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-white text-red-700 font-bold shadow hover:bg-red-50">
                <i class="fas fa-hand-holding-heart"></i> Donasi Sekarang
            </a>
            <a href="{{ route('donasi.lacak') }}" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-white/10 border border-white/30 font-bold hover:bg-white/20">
                <i class="fas fa-magnifying-glass-location"></i> Lacak Donasi
            </a>
            <a href="{{ route('donasi.laporan') }}" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-white/10 border border-white/30 font-bold hover:bg-white/20">
                <i class="fas fa-file-invoice"></i> Laporan Donasi
            </a>
        </div>
    </section>

    {{-- RINGKASAN --}}
    <section class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <p class="text-xs font-semibold text-slate-500">Dana diterima</p>
            <p class="text-xl md:text-2xl font-extrabold text-slate-800 mt-1">{{ $rp($ringkas['dana_diterima']) }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <p class="text-xs font-semibold text-slate-500">Dana sudah disalurkan</p>
            <p class="text-xl md:text-2xl font-extrabold text-green-700 mt-1">{{ $rp($ringkas['dana_disalurkan']) }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <p class="text-xs font-semibold text-slate-500">Donasi diterima</p>
            <p class="text-xl md:text-2xl font-extrabold text-slate-800 mt-1">{{ number_format($ringkas['jumlah_donasi'], 0, ',', '.') }}</p>
            <p class="text-xs text-slate-400">{{ $ringkas['donasi_barang'] }} berupa barang</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <p class="text-xs font-semibold text-slate-500">Posko terbantu</p>
            <p class="text-xl md:text-2xl font-extrabold text-slate-800 mt-1">{{ $ringkas['posko_terbantu'] }}</p>
        </div>
    </section>

    <div class="grid lg:grid-cols-3 gap-6">

        {{-- POSKO BUTUH BANTUAN --}}
        <section class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="font-bold text-slate-800">Posko yang sedang butuh bantuan</h2>
                <p class="text-xs text-slate-500">Dihitung otomatis dari stok dan jumlah pengungsi di tiap posko.</p>
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($butuh as $b)
                    @php [$kelas, $label] = $warnaTingkat[$b['tingkat']]; @endphp
                    <li class="px-6 py-4 flex flex-col sm:flex-row sm:items-center gap-3">
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-slate-800">
                                {{ $b['lokasi']->nama_lokasi }}
                                <span class="ml-1 px-2 py-0.5 rounded-full text-[11px] font-bold {{ $kelas }}">{{ $label }}</span>
                            </p>
                            <p class="text-xs text-slate-500">{{ $b['lokasi']->desa->nama_desa ?? '' }} · {{ $b['jumlah_pengungsi'] }} pengungsi</p>
                            @if ($b['kebutuhan']->isNotEmpty())
                                <p class="text-sm text-slate-600 mt-1">
                                    Butuh:
                                    {{ $b['kebutuhan']->take(3)->map(fn ($k) => $k->nama_item . ' ±' . number_format($k->kekurangan, 0, ',', '.') . ' ' . $k->satuan)->implode(', ') }}
                                </p>
                            @endif
                        </div>
                        <a href="{{ route('donasi.create', ['posko' => $b['lokasi']->id]) }}"
                           class="shrink-0 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-sm font-bold">
                            <i class="fas fa-hand-holding-heart"></i> Bantu posko ini
                        </a>
                    </li>
                @empty
                    <li class="px-6 py-10 text-center text-slate-400">
                        <i class="fas fa-check-circle text-2xl text-green-500 mb-2"></i>
                        <p>Saat ini tidak ada posko yang kekurangan logistik. Donasi ke GMLS Pusat akan disalurkan saat dibutuhkan.</p>
                    </li>
                @endforelse
            </ul>
        </section>

        {{-- GMLS PUSAT --}}
        <section class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">GMLS Pusat</p>
                <h2 class="font-bold text-slate-800 mt-1">{{ $gudang->nama ?? 'Posko Pusat GMLS' }}</h2>
                @if ($gudang?->alamat)
                    <p class="text-sm text-slate-500 mt-1"><i class="fas fa-location-dot mr-1 text-red-500"></i>{{ $gudang->alamat }}</p>
                @endif
                @if ($gudang?->kontak)
                    <p class="text-sm text-slate-500 mt-1"><i class="fas fa-phone mr-1 text-red-500"></i>{{ $gudang->kontak }} {{ $gudang->penanggung_jawab ? '(' . $gudang->penanggung_jawab . ')' : '' }}</p>
                @endif
            </div>

            @if ($gudang?->punyaRekening())
                <div class="rounded-xl bg-slate-50 border border-slate-200 p-4">
                    <p class="text-xs font-semibold text-slate-500">Rekening donasi</p>
                    <p class="font-bold text-slate-800 mt-1">{{ $gudang->nama_bank }}</p>
                    <p class="text-lg font-mono font-bold text-slate-900 tracking-wider">{{ $gudang->nomor_rekening }}</p>
                    <p class="text-sm text-slate-600">a.n. {{ $gudang->atas_nama_rekening }}</p>
                </div>
            @else
                <p class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-xl p-3">
                    Rekening donasi belum diumumkan. Untuk saat ini donasi bisa berupa barang.
                </p>
            @endif

            <div class="text-sm text-slate-600 space-y-2">
                <p class="font-semibold text-slate-800">Cara kerja</p>
                <p><b>1.</b> Isi form donasi, lalu simpan <b>kode lacak</b> yang muncul.</p>
                <p><b>2.</b> GMLS mengecek transfer / relawan posko mengecek barang yang datang.</p>
                <p><b>3.</b> Setiap penyaluran dicatat. Anda bisa melihatnya dengan kode lacak.</p>
            </div>
        </section>
    </div>

    {{-- DONASI TERBARU --}}
    <section class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="font-bold text-slate-800">Donasi terbaru yang sudah diterima</h2>
            <a href="{{ route('donasi.laporan') }}" class="text-sm font-semibold text-red-600 hover:underline">Lihat laporan lengkap</a>
        </div>
        <ul class="divide-y divide-slate-100">
            @forelse ($terbaru as $d)
                <li class="px-6 py-3 flex items-center gap-4">
                    <span class="w-10 h-10 shrink-0 rounded-xl flex items-center justify-center {{ $d->isUang() ? 'bg-green-100 text-green-600' : 'bg-blue-100 text-blue-600' }}">
                        <i class="fas {{ $d->isUang() ? 'fa-money-bill-wave' : 'fa-box' }}"></i>
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-slate-800">{{ $d->namaTampil() }}</p>
                        <p class="text-sm text-slate-500 truncate">{{ $d->ringkasan() }} · untuk {{ $d->namaTujuan() }}</p>
                    </div>
                    <p class="text-xs text-slate-400 shrink-0">{{ $d->diverifikasi_pada?->diffForHumans() }}</p>
                </li>
            @empty
                <li class="px-6 py-8 text-center text-slate-400">Belum ada donasi yang diterima.</li>
            @endforelse
        </ul>
    </section>
</div>
@endsection
