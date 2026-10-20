@extends('layouts.dashboard')

@section('title', 'Monitoring Logistik')

@section('content')

<div class="container mx-auto px-6 py-8">

    <div class="flex items-center justify-between mb-8">

        <div>
            <h1 class="text-3xl font-bold text-slate-800">
                Monitoring Logistik
            </h1>

            <p class="text-slate-500 mt-2">
                {{ $lokasi->nama_lokasi }}
            </p>
        </div>

        <!-- BUTTON GROUP -->
        <div class="flex flex-wrap items-center gap-3">

            @if ($bisaKelola)
                <a href="{{ route('logistik.create', $lokasi->id) }}"
                   class="inline-flex items-center gap-2 px-5 py-3 bg-green-600 hover:bg-green-700
                   text-white rounded-xl font-bold shadow transition">
                    <i class="fas fa-arrow-down"></i> Barang Masuk
                </a>

                <a href="{{ route('logistik.keluar', $lokasi->id) }}"
                   class="inline-flex items-center gap-2 px-5 py-3 bg-orange-500 hover:bg-orange-600
                   text-white rounded-xl font-bold shadow transition">
                    <i class="fas fa-arrow-up"></i> Barang Keluar
                </a>
            @endif

            <a href="{{ route('lokasi.show', $lokasi->id) }}"
               class="inline-flex items-center gap-2 px-5 py-3 bg-white hover:bg-slate-50 border border-slate-200
               text-slate-700 rounded-xl font-semibold transition">
                <i class="fas fa-arrow-left text-xs"></i> Kembali
            </a>

        </div>
</div>

@php
    // Istilah, batas & warna sama dengan panel detail posko (includes/operasional-lokasi)
    $batasMerah  = \App\Services\OperasionalLokasiService::BATAS_MERAH;
    $batasKuning = \App\Services\OperasionalLokasiService::BATAS_KUNING;
    $warnaStok = [
        'merah'  => ['bg-red-50 text-red-700',     'bg-red-500',   'Kritis'],
        'kuning' => ['bg-amber-50 text-amber-700', 'bg-amber-500', 'Menipis'],
        'hijau'  => ['bg-green-50 text-green-700', 'bg-green-500', 'Aman'],
        'netral' => ['bg-slate-50 text-slate-500', 'bg-slate-300', 'Belum dihitung'],
    ];
    $tanpaKebutuhan = $stok->whereNull('kebutuhan_harian');
@endphp

{{-- RINGKASAN STATUS --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">

    <div class="bg-red-50 border border-red-200 rounded-2xl p-5">
        <p class="text-sm text-red-600 font-semibold"><i class="fas fa-circle-exclamation mr-1"></i> Kritis</p>
        <h2 class="text-3xl font-bold text-red-700 mt-2">{{ $stokStatus['merah'] }}</h2>
        <p class="text-xs text-red-500 mt-1">Habis kurang dari {{ $batasMerah }} hari</p>
    </div>

    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5">
        <p class="text-sm text-amber-600 font-semibold"><i class="fas fa-triangle-exclamation mr-1"></i> Menipis</p>
        <h2 class="text-3xl font-bold text-amber-700 mt-2">{{ $stokStatus['kuning'] }}</h2>
        <p class="text-xs text-amber-600 mt-1">Habis kurang dari {{ $batasKuning }} hari</p>
    </div>

    <div class="bg-green-50 border border-green-200 rounded-2xl p-5">
        <p class="text-sm text-green-600 font-semibold"><i class="fas fa-circle-check mr-1"></i> Aman</p>
        <h2 class="text-3xl font-bold text-green-700 mt-2">{{ $stokStatus['hijau'] }}</h2>
        <p class="text-xs text-green-600 mt-1">Cukup {{ $batasKuning }} hari atau lebih</p>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-2xl p-5">
        <p class="text-sm text-blue-600 font-semibold"><i class="fas fa-hourglass-half mr-1"></i> Paling Cepat Habis</p>
        <h2 class="text-3xl font-bold text-blue-700 mt-2">
            {{ $palingCepatHabis ? \App\Services\OperasionalLokasiService::teksHari($palingCepatHabis->cukup_hari) : '-' }}
        </h2>
        <p class="text-xs text-blue-500 mt-1">
            {{ $palingCepatHabis ? $palingCepatHabis->nama_item . ' · untuk ' . $jumlahPengungsi . ' pengungsi tercatat' : 'Untuk ' . $jumlahPengungsi . ' pengungsi tercatat' }}
        </p>
    </div>

</div>

    {{-- STOK --}}
    <div class="bg-white rounded-2xl shadow border border-slate-200 overflow-hidden mb-8">

        <div class="px-6 py-4 border-b border-slate-100">
            <h2 class="font-bold text-slate-700">Stok Logistik Saat Ini</h2>
            <p class="text-xs text-slate-400 mt-0.5">
                Kebutuhan/hari = kebutuhan harian per orang (Master Logistik) &times; {{ $jumlahPengungsi }} pengungsi tercatat.
            </p>
        </div>

        @if ($stok->isNotEmpty() && ($tanpaKebutuhan->isNotEmpty() || $jumlahPengungsi === 0))
            <div class="px-6 py-3 bg-amber-50 border-b border-amber-100 text-xs text-amber-800 space-y-1">
                <p class="font-semibold"><i class="fas fa-circle-info mr-1"></i> Daya tahan stok belum bisa dihitung untuk sebagian barang karena:</p>
                <ul class="list-disc pl-5 space-y-0.5">
                    @if ($jumlahPengungsi === 0)
                        <li>
                            Belum ada pengungsi tercatat di posko ini.
                            @if ($bisaKelola)
                                <a href="{{ route('pengungsi.index', $lokasi->id) }}" class="font-bold underline">Tambah data pengungsi</a>.
                            @endif
                        </li>
                    @endif
                    @if ($tanpaKebutuhan->isNotEmpty())
                        <li>
                            Kebutuhan harian belum diatur untuk: <b>{{ $tanpaKebutuhan->pluck('nama_item')->join(', ') }}</b>.
                            @if ($isAdmin)
                                Atur di <a href="{{ route('admin.logistik.index') }}" class="font-bold underline">Master Logistik</a>.
                            @else
                                Minta admin mengaturnya di Master Logistik.
                            @endif
                        </li>
                    @endif
                </ul>
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-100 text-slate-600 text-xs uppercase">
                    <tr>
                        <th class="text-left px-6 py-4">Barang</th>
                        <th class="text-left px-6 py-4">Kategori</th>
                        <th class="text-right px-6 py-4">Stok</th>
                        <th class="text-right px-6 py-4">Kebutuhan/Hari</th>
                        <th class="text-right px-6 py-4">Cukup Untuk</th>
                        <th class="text-right px-6 py-4">Perlu Dikirim<span class="block text-[10px] font-medium normal-case text-slate-400">untuk {{ \App\Services\OperasionalLokasiService::targetHari() }} hari</span></th>
                        <th class="text-center px-6 py-4">Status</th>
                        @if ($bisaKelola)
                            <th class="px-6 py-4"><span class="sr-only">Aksi</span></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stok as $s)
                        @php [$bgBaris, $dot, $teks] = $warnaStok[$s->status]; @endphp
                        <tr class="border-t border-slate-100 hover:bg-slate-50 transition">
                            <td class="px-6 py-4 font-semibold text-slate-800">{{ $s->nama_item }}</td>
                            <td class="px-6 py-4 text-slate-500">{{ $s->nama_kategori ?? '-' }}</td>
                            <td class="px-6 py-4 text-right font-bold text-slate-800">
                                {{ number_format($s->jumlah_stok, 0, ',', '.') }} {{ $s->satuan }}
                                @if ($s->belum_pernah_masuk)<span class="block text-[11px] font-medium text-red-600">belum pernah ada</span>@endif
                            </td>
                            <td class="px-6 py-4 text-right text-slate-500">
                                @if ($s->kebutuhan_harian === null)
                                    @if ($isAdmin)
                                        <a href="{{ route('admin.logistik.edit', $s->logistik_id) }}" class="text-xs font-semibold text-amber-600 hover:underline">Belum diatur</a>
                                    @else
                                        <span class="text-xs text-amber-600">Belum diatur</span>
                                    @endif
                                @elseif ($s->kebutuhan_hari_ini)
                                    {{ number_format($s->kebutuhan_hari_ini, 1, ',', '.') }} {{ $s->satuan }}
                                @else
                                    <span class="text-xs">0 (belum ada pengungsi)</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right font-semibold">
                                {{ \App\Services\OperasionalLokasiService::teksHari($s->cukup_hari) }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if ($s->perlu_dikirim)
                                    <span class="font-bold {{ $s->mendesak ? 'text-red-600' : 'text-slate-800' }}">±{{ number_format($s->kekurangan, 0, ',', '.') }} {{ $s->satuan }}</span>
                                @elseif ($s->kekurangan === 0)
                                    <span class="text-xs font-semibold text-green-700">Cukup</span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold {{ $bgBaris }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $dot }}"></span> {{ $teks }}
                                </span>
                            </td>
                            @if ($bisaKelola)
                                <td class="px-6 py-4 text-right">
                                    @if ($s->jumlah_stok > 0)
                                        <a href="{{ route('logistik.keluar', ['lokasi' => $lokasi->id, 'barang' => $s->logistik_id]) }}"
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-orange-200 bg-orange-50 hover:bg-orange-100 text-orange-700 text-xs font-bold whitespace-nowrap">
                                            <i class="fas fa-arrow-up text-[10px]"></i> Keluarkan
                                        </a>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $bisaKelola ? 8 : 7 }}" class="px-6 py-8 text-center text-slate-400">
                                Belum ada stok logistik di posko ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

    {{-- RIWAYAT KELUAR MASUK --}}
<div class="bg-white rounded-2xl shadow border border-slate-200 overflow-hidden" x-data="{ tab: 'semua' }">

    <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h2 class="font-bold text-slate-700">Riwayat Barang Masuk &amp; Keluar</h2>
            <p class="text-xs text-slate-400">
                <span class="text-green-600 font-bold">+</span> barang masuk menambah stok,
                <span class="text-orange-600 font-bold">−</span> barang keluar mengurangi stok.
            </p>
        </div>

        <div class="inline-flex rounded-xl bg-slate-100 p-1 text-sm font-semibold" role="tablist">
            @foreach (['semua' => 'Semua', 'masuk' => 'Masuk', 'keluar' => 'Keluar'] as $key => $label)
                <button type="button" role="tab" @click="tab = '{{ $key }}'" :aria-selected="tab === '{{ $key }}'"
                        :class="tab === '{{ $key }}' ? 'bg-white shadow text-slate-900' : 'text-slate-500 hover:text-slate-700'"
                        class="px-4 py-1.5 rounded-lg transition">
                    {{ $label }}
                    <span class="text-xs text-slate-400">{{ $key === 'semua' ? $riwayat->count() : $riwayat->where('jenis', $key)->count() }}</span>
                </button>
            @endforeach
        </div>
    </div>

    <ul class="divide-y divide-slate-100">
        @forelse ($riwayat as $r)
            @php $masuk = $r->jenis === 'masuk'; @endphp
            <li class="px-6 py-4 flex items-center gap-4 hover:bg-slate-50 transition"
                x-show="tab === 'semua' || tab === '{{ $r->jenis }}'">

                <div class="w-10 h-10 shrink-0 rounded-xl flex items-center justify-center {{ $masuk ? 'bg-green-100 text-green-600' : 'bg-orange-100 text-orange-600' }}">
                    <i class="fas {{ $masuk ? 'fa-arrow-down' : 'fa-arrow-up' }}"></i>
                </div>

                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-slate-800">
                        {{ $r->item }}
                        <span class="ml-1 text-[11px] font-bold uppercase tracking-wide {{ $masuk ? 'text-green-600' : 'text-orange-600' }}">{{ $masuk ? 'Masuk' : 'Keluar' }}</span>
                    </p>
                    <p class="text-sm text-slate-500 truncate">{{ $r->keterangan }}</p>
                    @if ($r->catatan)
                        <p class="text-xs text-slate-400 truncate">{{ $r->catatan }}</p>
                    @endif
                </div>

                <div class="text-right shrink-0">
                    <p class="text-lg font-black {{ $masuk ? 'text-green-600' : 'text-orange-600' }}">
                        {{ $masuk ? '+' : '−' }}{{ number_format($r->jumlah, 0, ',', '.') }}
                        <span class="text-xs font-semibold text-slate-400">{{ $r->satuan }}</span>
                    </p>
                    <p class="text-xs text-slate-400">{{ $r->tanggal->translatedFormat('d M Y') }}</p>
                </div>
            </li>
        @empty
            <li class="px-6 py-10 text-center text-slate-400">
                <i class="fas fa-clipboard-list text-3xl mb-2"></i>
                <p>Belum ada barang masuk atau keluar.</p>
            </li>
        @endforelse
    </ul>

</div>

</div>{{-- /container --}}

@endsection