@extends('layouts.dashboard')

@section('title', 'Lacak Donasi')

@section('content')
@php
    $rp = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');
    $warna = [
        'green' => 'bg-green-100 text-green-600',
        'red'   => 'bg-red-100 text-red-600',
        'amber' => 'bg-amber-100 text-amber-600',
        'blue'  => 'bg-blue-100 text-blue-600',
        'slate' => 'bg-slate-100 text-slate-600',
    ];
@endphp

<div class="max-w-3xl mx-auto space-y-6">

    <div>
        <a href="{{ route('donasi.index') }}" class="text-sm text-slate-500 hover:text-red-600"><i class="fas fa-arrow-left mr-1"></i> Donasi</a>
        <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 mt-2">Lacak Donasi</h1>
    </div>

    <form method="GET" action="{{ route('donasi.lacak') }}" class="flex gap-2">
        <input type="text" name="kode" value="{{ $kode }}" placeholder="Masukkan kode donasi, contoh: DN-7K3QX9MA" required
               class="flex-1 rounded-xl border border-slate-300 px-4 py-3 font-mono uppercase focus:ring-2 focus:ring-red-500 outline-none">
        <button class="px-5 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold"><i class="fas fa-magnifying-glass"></i><span class="hidden sm:inline ml-2">Lacak</span></button>
    </form>

    @if ($kode !== '' && ! $donasi)
        <div class="rounded-2xl bg-amber-50 border border-amber-200 p-5 text-amber-800">
            <i class="fas fa-circle-question mr-1"></i> Kode <b class="font-mono">{{ $kode }}</b> tidak ditemukan. Periksa kembali penulisan kodenya.
        </div>
    @endif

    @if ($donasi)
        @php $st = $donasi->labelStatus(); @endphp

        @if (session('donasi_baru'))
            <div class="rounded-2xl bg-green-50 border border-green-200 p-5 text-green-800">
                <p class="font-bold text-lg"><i class="fas fa-heart mr-1"></i> Terima kasih, donasi Anda sudah tercatat.</p>
                <p class="mt-1">Simpan kode di bawah ini (tangkap layar atau catat) untuk melacak donasi Anda kapan saja:</p>
                <div class="mt-3 flex flex-wrap items-center gap-3" x-data="{ disalin: false }">
                    <span class="px-4 py-2 rounded-xl bg-white border border-green-300 font-mono text-2xl font-bold tracking-widest text-slate-900">{{ $donasi->kode }}</span>
                    <button type="button" class="text-sm font-semibold underline"
                            @click="navigator.clipboard.writeText('{{ $donasi->kode }}'); disalin = true; setTimeout(() => disalin = false, 2000)">
                        <span x-text="disalin ? 'Tersalin' : 'Salin kode'"></span>
                    </button>
                </div>
                @if ($donasi->isUang())
                    <p class="text-sm mt-3">GMLS akan mencocokkan transfer Anda dengan mutasi rekening, biasanya dalam 1x24 jam.</p>
                @else
                    <p class="text-sm mt-3">Saat barang sampai, petugas akan mengonfirmasi dan status di halaman ini berubah.</p>
                @endif
            </div>
        @endif

        {{-- RINGKASAN --}}
        <section class="bg-white rounded-2xl border border-slate-200 p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="font-mono text-sm font-bold text-slate-500">{{ $donasi->kode }}</p>
                    <h2 class="text-xl font-extrabold text-slate-800">{{ $donasi->isUang() ? 'Donasi uang' : 'Donasi barang' }} untuk {{ $donasi->namaTujuan() }}</h2>
                    <p class="text-sm text-slate-500">dari {{ $donasi->namaTampil() }} · {{ $donasi->created_at->translatedFormat('d M Y, H:i') }} WIB</p>
                </div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-bold {{ $warna[$st['warna']] }}">
                    <i class="fas {{ $st['ikon'] }}"></i> {{ $st['label'] }}
                </span>
            </div>

            <div class="mt-5 grid sm:grid-cols-2 gap-4 text-sm">
                @if ($donasi->isUang())
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-slate-500">Dilaporkan</p>
                        <p class="text-lg font-bold text-slate-800">{{ $rp($donasi->nominal) }}</p>
                        @if ($donasi->nominal_diterima !== null)
                            <p class="text-slate-500 mt-2">Diterima di rekening</p>
                            <p class="text-lg font-bold text-green-700">{{ $rp($donasi->nominal_diterima) }}</p>
                        @endif
                    </div>
                    @if ($donasi->nominal_diterima !== null)
                        <div class="rounded-xl bg-slate-50 p-4">
                            <p class="text-slate-500">Sudah disalurkan</p>
                            <p class="text-lg font-bold text-slate-800">{{ $rp($donasi->penyaluran->sum('nominal')) }}</p>
                            <p class="text-slate-500 mt-2">Sisa belum disalurkan</p>
                            <p class="text-lg font-bold text-slate-800">{{ $rp($donasi->sisaDana()) }}</p>
                        </div>
                    @endif
                @else
                    <div class="sm:col-span-2 rounded-xl bg-slate-50 p-4 overflow-x-auto">
                        <table class="w-full">
                            <thead class="text-xs text-slate-500 uppercase">
                                <tr>
                                    <th class="text-left py-1">Barang</th>
                                    <th class="text-right py-1">Dikirim</th>
                                    <th class="text-right py-1">Diterima</th>
                                    @unless ($donasi->isKePosko())<th class="text-right py-1">Disalurkan</th>@endunless
                                </tr>
                            </thead>
                            <tbody>
                                @php $terkirim = $donasi->penyaluran->groupBy('logistik_id')->map->sum('jumlah'); @endphp
                                @foreach ($donasi->items as $i)
                                    <tr class="border-t border-slate-200">
                                        <td class="py-2 font-semibold text-slate-800">{{ $i->logistik->nama_item ?? '-' }}</td>
                                        <td class="py-2 text-right">{{ number_format($i->jumlah, 0, ',', '.') }} {{ $i->logistik->satuan ?? '' }}</td>
                                        <td class="py-2 text-right">{{ $i->jumlah_diterima === null ? '-' : number_format($i->jumlah_diterima, 0, ',', '.') }}</td>
                                        @unless ($donasi->isKePosko())
                                            <td class="py-2 text-right">{{ number_format($terkirim[$i->logistik_id] ?? 0, 0, ',', '.') }}</td>
                                        @endunless
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            @if ($donasi->pesan)
                <p class="mt-4 text-sm text-slate-600 italic">"{{ $donasi->pesan }}"</p>
            @endif
        </section>

        {{-- KE MANA DISALURKAN --}}
        @if ($donasi->penyaluran->isNotEmpty())
            <section class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h2 class="font-bold text-slate-800"><i class="fas fa-route text-red-600 mr-1"></i> Ke mana donasi Anda disalurkan</h2>
                </div>
                <ul class="divide-y divide-slate-100">
                    @foreach ($donasi->penyaluran as $p)
                        <li class="px-6 py-4 flex gap-4">
                            <span class="w-10 h-10 shrink-0 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center"><i class="fas {{ $p->lokasi_id ? 'fa-campground' : 'fa-warehouse' }}"></i></span>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-slate-800">{{ $p->namaTujuan() }}</p>
                                <p class="text-sm text-slate-600">
                                    @if ($p->nominal){{ $rp($p->nominal) }}@endif
                                    @if ($p->logistik)
                                        {{ $p->nominal ? '· dibelikan' : '' }} {{ $p->logistik->nama_item }} {{ number_format($p->jumlah, 0, ',', '.') }} {{ $p->logistik->satuan }}
                                    @endif
                                </p>
                                @if ($p->keterangan)<p class="text-sm text-slate-500">{{ $p->keterangan }}</p>@endif
                                @if ($p->foto)
                                    <a href="{{ asset('storage/' . $p->foto) }}" target="_blank" rel="noopener">
                                        <img src="{{ asset('storage/' . $p->foto) }}" alt="Dokumentasi penyaluran ke {{ $p->namaTujuan() }}" class="mt-2 h-28 rounded-xl object-cover border border-slate-200">
                                    </a>
                                @endif
                            </div>
                            <p class="text-xs text-slate-400 shrink-0">{{ $p->tanggal->translatedFormat('d M Y') }}</p>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- JEJAK --}}
        <section class="bg-white rounded-2xl border border-slate-200 p-6">
            <h2 class="font-bold text-slate-800 mb-4"><i class="fas fa-history text-red-600 mr-1"></i> Jejak donasi</h2>
            <ol class="relative border-l-2 border-slate-200 ml-4 space-y-6">
                @foreach ($donasi->jejak->reverse() as $j)
                    <li class="ml-6">
                        <span class="absolute -left-[17px] w-8 h-8 rounded-full flex items-center justify-center ring-4 ring-white {{ $warna[$j->warna] ?? $warna['slate'] }}">
                            <i class="fas {{ $j->ikon }} text-xs"></i>
                        </span>
                        <p class="font-semibold text-slate-800">{{ $j->judul }}</p>
                        <p class="text-xs text-slate-400">{{ $j->created_at->translatedFormat('d M Y, H:i') }} WIB</p>
                        @if ($j->keterangan)<p class="text-sm text-slate-600 mt-1">{{ $j->keterangan }}</p>@endif
                        @if ($j->foto)
                            <a href="{{ asset('storage/' . $j->foto) }}" target="_blank" rel="noopener">
                                <img src="{{ asset('storage/' . $j->foto) }}" alt="Foto: {{ $j->judul }}" class="mt-2 h-28 rounded-xl object-cover border border-slate-200">
                            </a>
                        @endif
                    </li>
                @endforeach
            </ol>
        </section>
    @endif
</div>
@endsection
