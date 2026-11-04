@extends('layouts.admin')

@section('title', 'Gudang Pusat GMLS')

@section('content')
@php
    $rp    = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');
    $input = 'w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-red-500 outline-none';
    $label = 'block mb-1 text-xs font-bold text-slate-600';
    $stokAda = $stok->where('jumlah_stok', '>', 0);
@endphp

<div class="relative z-10 container mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10 flex flex-col gap-6" x-data="{ tab: '{{ $errors->has('sumber') ? 'masuk' : 'kirim' }}', edit: {{ $errors->has('nama') || $errors->has('nomor_rekening') || $errors->has('nama_bank') ? 'true' : 'false' }} }">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white/60 backdrop-blur-xl p-6 md:p-8 rounded-3xl border border-white/80 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-red-50 border border-red-100/50 text-red-600 text-xs font-bold tracking-wide uppercase mb-3">
                <i class="fas fa-warehouse opacity-70"></i> Pusat
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 tracking-tight">{{ $gudang->nama }}</h1>
            <p class="text-slate-500 mt-1 font-medium text-sm">{{ $gudang->alamat ?: 'Alamat belum diisi' }}</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <button type="button" @click="edit = ! edit" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white border border-slate-200 font-bold text-slate-700 hover:bg-slate-50">
                <i class="fas fa-pen"></i> Ubah info &amp; rekening
            </button>
            <a href="{{ route('admin.donasi.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-red-600 text-white font-bold hover:bg-red-700">
                <i class="fas fa-hand-holding-heart"></i> Donasi
                @if ($donasiMenunggu)<span class="px-2 py-0.5 rounded-full bg-white text-red-700 text-xs">{{ $donasiMenunggu }} menunggu</span>@endif
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="px-4 py-3 rounded-xl bg-rose-50 text-rose-700 border border-rose-200 text-sm">
            @foreach ($errors->all() as $e)<p><i class="fas fa-exclamation-circle mr-1"></i>{{ $e }}</p>@endforeach
        </div>
    @endif

    {{-- INFO & REKENING --}}
    <section x-show="edit" x-cloak class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6">
        <h2 class="font-bold text-slate-800 mb-4">Info gudang pusat &amp; rekening donasi</h2>
        <form method="POST" action="{{ route('admin.gudang.update', $gudang) }}" class="grid sm:grid-cols-2 gap-3">
            @csrf @method('PUT')
            <div><label class="{{ $label }}">Nama</label><input name="nama" value="{{ old('nama', $gudang->nama) }}" required maxlength="255" class="{{ $input }}"></div>
            <div><label class="{{ $label }}">Penanggung jawab</label><input name="penanggung_jawab" value="{{ old('penanggung_jawab', $gudang->penanggung_jawab) }}" maxlength="255" class="{{ $input }}"></div>
            <div class="sm:col-span-2"><label class="{{ $label }}">Alamat (tampil ke donatur sebagai alamat kirim barang)</label><textarea name="alamat" rows="2" maxlength="1000" class="{{ $input }}">{{ old('alamat', $gudang->alamat) }}</textarea></div>
            <div><label class="{{ $label }}">Kontak (HP / WA)</label><input name="kontak" value="{{ old('kontak', $gudang->kontak) }}" maxlength="30" class="{{ $input }}"></div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="{{ $label }}">Latitude</label><input name="latitude" value="{{ old('latitude', $gudang->latitude) }}" class="{{ $input }}" placeholder="-6.9"></div>
                <div><label class="{{ $label }}">Longitude</label><input name="longitude" value="{{ old('longitude', $gudang->longitude) }}" class="{{ $input }}" placeholder="106.2"></div>
            </div>
            <div><label class="{{ $label }}">Nama bank</label><input name="nama_bank" value="{{ old('nama_bank', $gudang->nama_bank) }}" maxlength="60" class="{{ $input }}" placeholder="contoh: BRI"></div>
            <div><label class="{{ $label }}">Nomor rekening</label><input name="nomor_rekening" value="{{ old('nomor_rekening', $gudang->nomor_rekening) }}" maxlength="40" class="{{ $input }}"></div>
            <div><label class="{{ $label }}">Atas nama</label><input name="atas_nama_rekening" value="{{ old('atas_nama_rekening', $gudang->atas_nama_rekening) }}" maxlength="255" class="{{ $input }}"></div>
            <div><label class="{{ $label }}">Keterangan</label><input name="keterangan" value="{{ old('keterangan', $gudang->keterangan) }}" maxlength="1000" class="{{ $input }}"></div>
            <p class="sm:col-span-2 text-xs text-slate-500">Donasi uang di halaman publik baru bisa dipilih setelah nomor rekening diisi.</p>
            <div class="sm:col-span-2"><button class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-sm">Simpan</button></div>
        </form>
    </section>

    {{-- RINGKASAN --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-100 p-5">
            <p class="text-xs font-bold text-slate-500">Jenis barang di gudang</p>
            <p class="text-2xl font-extrabold text-slate-800">{{ $stokAda->count() }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 p-5">
            <p class="text-xs font-bold text-slate-500">Donasi menunggu dicek</p>
            <p class="text-2xl font-extrabold text-amber-600">{{ $donasiMenunggu }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 p-5">
            <p class="text-xs font-bold text-slate-500">Dana donasi belum disalurkan</p>
            <p class="text-2xl font-extrabold text-slate-800">{{ $rp($danaBelumDisalurkan) }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 p-5">
            <p class="text-xs font-bold text-slate-500">Rekening donasi</p>
            <p class="text-sm font-bold {{ $gudang->punyaRekening() ? 'text-green-700' : 'text-amber-600' }} mt-1">
                {{ $gudang->punyaRekening() ? $gudang->nama_bank . ' ' . $gudang->nomor_rekening : 'Belum diisi' }}
            </p>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">

        {{-- STOK --}}
        <section class="lg:col-span-2 bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="font-bold text-slate-800">Stok gudang pusat</h2>
                <p class="text-xs text-slate-400">Bertambah dari donasi ke GMLS Pusat / barang masuk, berkurang saat dikirim ke posko.</p>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs font-bold text-slate-500 uppercase">
                    <tr><th class="text-left px-6 py-3">Barang</th><th class="text-right px-6 py-3">Stok</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($stokAda as $s)
                        <tr>
                            <td class="px-6 py-3 font-semibold text-slate-800">{{ $s->logistik->nama_item }}</td>
                            <td class="px-6 py-3 text-right font-bold">{{ number_format($s->jumlah_stok, 0, ',', '.') }} {{ $s->logistik->satuan }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-6 py-10 text-center text-slate-400">Gudang pusat masih kosong.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        {{-- FORM --}}
        <section class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6">
            <div class="inline-flex rounded-xl bg-slate-100 p-1 text-sm font-semibold mb-4 w-full">
                <button type="button" @click="tab = 'kirim'" :class="tab === 'kirim' ? 'bg-white shadow text-slate-900' : 'text-slate-500'" class="flex-1 px-3 py-1.5 rounded-lg">Kirim ke posko</button>
                <button type="button" @click="tab = 'masuk'" :class="tab === 'masuk' ? 'bg-white shadow text-slate-900' : 'text-slate-500'" class="flex-1 px-3 py-1.5 rounded-lg">Barang masuk</button>
            </div>

            <form x-show="tab === 'kirim'" method="POST" action="{{ route('admin.gudang.kirim', $gudang) }}" class="space-y-3">
                @csrf
                @if ($stokAda->isEmpty())
                    <p class="text-sm text-slate-400">Belum ada stok untuk dikirim.</p>
                @else
                    <div>
                        <label class="{{ $label }}">Posko tujuan</label>
                        <select name="lokasi_id" required class="{{ $input }}">
                            <option value="">-- Pilih posko --</option>
                            @foreach ($poskos as $p)
                                <option value="{{ $p->id }}" @selected(old('lokasi_id') == $p->id)>{{ $p->nama_lokasi }}{{ $p->isBeroperasi() ? '' : ' (tidak beroperasi)' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}">Barang</label>
                        <select name="logistik_id" required class="{{ $input }}">
                            @foreach ($stokAda as $s)
                                <option value="{{ $s->logistik_id }}" @selected(old('logistik_id') == $s->logistik_id)>{{ $s->logistik->nama_item }} (stok {{ $s->jumlah_stok }} {{ $s->logistik->satuan }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="{{ $label }}">Jumlah</label><input type="number" name="jumlah" min="1" value="{{ old('jumlah') }}" required class="{{ $input }}"></div>
                        <div><label class="{{ $label }}">Tanggal</label><input type="date" name="tanggal" value="{{ old('tanggal', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required class="{{ $input }}"></div>
                    </div>
                    <input type="text" name="keterangan" maxlength="500" value="{{ old('keterangan') }}" placeholder="Keterangan (opsional)" class="{{ $input }}">
                    <p class="text-xs text-slate-500">Untuk barang dari donasi tertentu, kirim lewat halaman donasinya supaya tercatat di jejak donatur.</p>
                    <button class="w-full py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm"><i class="fas fa-truck mr-1"></i> Kirim</button>
                @endif
            </form>

            <form x-show="tab === 'masuk'" x-cloak method="POST" action="{{ route('admin.gudang.masuk', $gudang) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="{{ $label }}">Barang</label>
                    <select name="logistik_id" required class="{{ $input }}">
                        <option value="">-- Pilih barang --</option>
                        @foreach ($logistiks as $l)
                            <option value="{{ $l->id }}" @selected(old('logistik_id') == $l->id)>{{ $l->nama_item }} ({{ $l->satuan }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="{{ $label }}">Jumlah</label><input type="number" name="jumlah" min="1" value="{{ old('jumlah') }}" required class="{{ $input }}"></div>
                    <div><label class="{{ $label }}">Tanggal</label><input type="date" name="tanggal" value="{{ old('tanggal', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required class="{{ $input }}"></div>
                </div>
                <input type="text" name="sumber" maxlength="255" value="{{ old('sumber') }}" required placeholder="Sumber, contoh: BPBD Lebak / pengadaan GMLS" class="{{ $input }}">
                <input type="text" name="keterangan" maxlength="500" value="{{ old('keterangan') }}" placeholder="Keterangan (opsional)" class="{{ $input }}">
                <p class="text-xs text-slate-500">Donasi dari donatur tidak perlu dicatat di sini, karena otomatis masuk saat dikonfirmasi di menu Donasi.</p>
                <button class="w-full py-2.5 rounded-xl bg-green-600 hover:bg-green-700 text-white font-bold text-sm"><i class="fas fa-arrow-down mr-1"></i> Catat barang masuk</button>
            </form>
        </section>
    </div>

    {{-- MUTASI --}}
    <section class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100"><h2 class="font-bold text-slate-800">Riwayat barang masuk &amp; keluar gudang pusat</h2></div>
        <ul class="divide-y divide-slate-100">
            @forelse ($mutasi as $m)
                @php $masuk = $m->jenis === \App\Models\MutasiGudang::MASUK; @endphp
                <li class="px-6 py-3 flex items-center gap-4">
                    <span class="w-9 h-9 shrink-0 rounded-xl flex items-center justify-center {{ $masuk ? 'bg-green-100 text-green-600' : 'bg-orange-100 text-orange-600' }}">
                        <i class="fas {{ $masuk ? 'fa-arrow-down' : 'fa-truck' }}"></i>
                    </span>
                    <div class="flex-1 min-w-0 text-sm">
                        <p class="font-semibold text-slate-800">{{ $m->logistik->nama_item ?? '-' }}</p>
                        <p class="text-slate-500 truncate">
                            {{ $masuk ? 'Dari ' . ($m->sumber ?? '-') : 'Dikirim ke ' . ($m->lokasi->nama_lokasi ?? '-') }}
                            @if ($m->donasi)
                                · <a href="{{ route('admin.donasi.show', $m->donasi) }}" class="font-mono text-red-600 hover:underline">{{ $m->donasi->kode }}</a>
                            @endif
                            @if ($m->keterangan) · {{ $m->keterangan }}@endif
                        </p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="font-black {{ $masuk ? 'text-green-600' : 'text-orange-600' }}">{{ $masuk ? '+' : '−' }}{{ number_format($m->jumlah, 0, ',', '.') }} <span class="text-xs font-semibold text-slate-400">{{ $m->logistik->satuan ?? '' }}</span></p>
                        <p class="text-xs text-slate-400">{{ $m->tanggal->translatedFormat('d M Y') }}</p>
                    </div>
                </li>
            @empty
                <li class="px-6 py-10 text-center text-slate-400">Belum ada riwayat.</li>
            @endforelse
        </ul>
        @if ($mutasi->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">{{ $mutasi->links() }}</div>
        @endif
    </section>
</div>
@endsection
