@extends('layouts.dashboard')

@section('title', 'Konfirmasi Donasi')

@section('content')
@php
    $input = 'w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition';
@endphp

<div class="max-w-2xl mx-auto space-y-6">
    <div>
        <a href="{{ route('logistik.index', $donasi->lokasi_id) }}" class="text-sm text-slate-500 hover:text-red-600"><i class="fas fa-arrow-left mr-1"></i> Logistik posko</a>
        <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 mt-2">Konfirmasi Barang Donasi</h1>
        <p class="text-slate-500">Untuk posko <b class="text-slate-700">{{ $donasi->lokasi->nama_lokasi }}</b></p>
    </div>

    @if ($errors->any())
        <div class="px-4 py-3 rounded-xl bg-rose-50 text-rose-700 border border-rose-200 text-sm">
            @foreach ($errors->all() as $e)<p><i class="fas fa-exclamation-circle mr-1"></i>{{ $e }}</p>@endforeach
        </div>
    @endif

    <section class="bg-white rounded-2xl border border-slate-200 p-6 text-sm space-y-1">
        <p class="font-mono font-bold text-slate-500">{{ $donasi->kode }}</p>
        <p><span class="text-slate-500">Donatur:</span> <b>{{ $donasi->nama_donatur }}</b> · {{ $donasi->kontak }}</p>
        @if ($donasi->perkiraan_tiba)<p><span class="text-slate-500">Perkiraan tiba:</span> {{ $donasi->perkiraan_tiba->translatedFormat('d M Y') }}</p>@endif
        @if ($donasi->pengirim)<p><span class="text-slate-500">Dikirim lewat:</span> {{ $donasi->pengirim }}</p>@endif
        @if ($donasi->pesan)<p class="italic text-slate-600">"{{ $donasi->pesan }}"</p>@endif
    </section>

    @if ($donasi->isMenunggu())
        <form method="POST" action="{{ route('donasi.konfirmasi.terima', $donasi) }}" class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
            @csrf
            <h2 class="font-bold text-slate-800">Barang yang benar-benar sampai</h2>
            <p class="text-sm text-slate-500">Ubah jumlahnya jika barang yang datang kurang. Isi 0 jika barang itu tidak datang. Barang langsung masuk ke stok posko.</p>

            @foreach ($donasi->items as $i)
                <div class="flex items-center gap-3">
                    <label for="d{{ $i->id }}" class="flex-1 text-slate-700">
                        <b>{{ $i->logistik->nama_item }}</b>
                        <span class="block text-xs text-slate-400">dijanjikan {{ number_format($i->jumlah, 0, ',', '.') }} {{ $i->logistik->satuan }}</span>
                    </label>
                    <input id="d{{ $i->id }}" type="number" min="0" name="diterima[{{ $i->id }}]" value="{{ old('diterima.' . $i->id, $i->jumlah) }}" class="{{ $input }} w-32">
                    <span class="w-12 text-sm text-slate-500">{{ $i->logistik->satuan }}</span>
                </div>
            @endforeach

            <textarea name="catatan" rows="2" maxlength="500" class="{{ $input }}" placeholder="Catatan (opsional), misalnya: 2 dus rusak saat pengiriman">{{ old('catatan') }}</textarea>

            <button class="w-full py-3 rounded-xl bg-green-600 hover:bg-green-700 text-white font-bold"><i class="fas fa-check mr-1"></i> Barang sudah sampai</button>
        </form>

        <form method="POST" action="{{ route('donasi.konfirmasi.tolak', $donasi) }}" class="bg-white rounded-2xl border border-slate-200 p-6 space-y-3"
              onsubmit="return confirm('Tandai donasi ini tidak diterima?')">
            @csrf
            <h2 class="font-bold text-slate-800">Barang tidak datang?</h2>
            <input type="text" name="alasan" maxlength="500" required class="{{ $input }}" placeholder="Alasan, misalnya: sudah 5 hari tidak ada kiriman">
            <button class="px-4 py-2 rounded-xl border border-red-200 bg-red-50 text-red-700 font-bold hover:bg-red-100">Tandai tidak diterima</button>
        </form>
    @else
        @php $st = $donasi->labelStatus(); @endphp
        <div class="rounded-2xl bg-slate-50 border border-slate-200 p-5 text-slate-700">
            Donasi ini sudah diproses: <b>{{ $st['label'] }}</b>.
            <a href="{{ route('donasi.lacak', ['kode' => $donasi->kode]) }}" class="font-semibold text-red-600 underline">Lihat jejaknya</a>
        </div>
    @endif
</div>
@endsection
