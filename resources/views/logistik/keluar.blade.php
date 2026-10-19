@extends('layouts.dashboard')

@section('title', 'Catat Barang Keluar')

@section('content')
@php
    $dipilih = old('logistik_id', request('barang'));
    $stokJs  = $stoks->mapWithKeys(fn ($s) => [$s->logistik_id => [
        'nama'   => $s->logistik->nama_item,
        'satuan' => $s->logistik->satuan,
        'sisa'   => (int) $s->jumlah_stok,
    ]]);
@endphp

<div class="max-w-3xl mx-auto flex flex-col gap-6"
     x-data="{
        stok: @js($stokJs),
        barang: @js($dipilih ? (string) $dipilih : null),
        jumlah: @js(old('jumlah_keluar') ? (int) old('jumlah_keluar') : null),
        tujuan: @js(old('tujuan')),
        get pilihan() { return this.barang ? this.stok[this.barang] : null },
        get sisaSetelah() { return this.pilihan ? this.pilihan.sisa - (this.jumlah || 0) : null },
        ubah(n) { this.jumlah = Math.max(1, Math.min((this.jumlah || 0) + n, this.pilihan?.sisa ?? 1)) },
     }">

    {{-- Breadcrumb --}}
    <nav aria-label="Breadcrumb">
        <ol class="flex flex-wrap items-center gap-2 text-xs font-medium text-slate-400">
            <li><a href="{{ route('lokasi.index') }}" class="hover:text-slate-600">Manajemen Posko</a></li>
            <li><i class="fas fa-chevron-right text-[9px]"></i></li>
            <li><a href="{{ route('logistik.index', $lokasi->id) }}" class="hover:text-slate-600">Logistik {{ $lokasi->nama_lokasi }}</a></li>
            <li><i class="fas fa-chevron-right text-[9px]"></i></li>
            <li class="text-slate-600 font-semibold">Barang Keluar</li>
        </ol>
    </nav>

    {{-- Judul --}}
    <header class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 flex items-start gap-4">
        <div class="w-12 h-12 shrink-0 rounded-2xl bg-orange-100 text-orange-600 flex items-center justify-center text-xl">
            <i class="fas fa-box-open"></i>
        </div>
        <div>
            <h1 class="text-2xl font-black text-slate-900">Catat Barang Keluar</h1>
            <p class="text-sm text-slate-500 mt-1">
                Isi setiap kali barang di <b class="text-slate-700">{{ $lokasi->nama_lokasi }}</b> dibagikan, dimasak, dikirim, atau rusak.
                Stok akan berkurang otomatis.
            </p>
        </div>
    </header>

    @if ($stoks->isEmpty())
        {{-- Tidak ada stok --}}
        <div class="bg-white rounded-3xl border border-dashed border-slate-300 p-10 text-center">
            <i class="fas fa-box text-4xl text-slate-300"></i>
            <p class="mt-3 font-bold text-slate-700">Belum ada barang di gudang posko ini</p>
            <p class="text-sm text-slate-500 mt-1">Catat dulu barang yang masuk, baru barang bisa dikeluarkan.</p>
            <a href="{{ route('logistik.create', $lokasi->id) }}"
               class="mt-5 inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-green-600 hover:bg-green-700 text-white font-bold">
                <i class="fas fa-plus"></i> Catat Barang Masuk
            </a>
        </div>
    @else
        <form method="POST" action="{{ route('logistik.keluar.store', $lokasi->id) }}" class="flex flex-col gap-6"
              @submit="if (pilihan && jumlah > pilihan.sisa) { $event.preventDefault() }">
            @csrf

            {{-- 1. Pilih barang --}}
            <section class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6">
                <h2 class="font-bold text-slate-900 flex items-center gap-3 mb-4">
                    <span class="w-7 h-7 rounded-full bg-slate-900 text-white text-sm flex items-center justify-center">1</span>
                    Barang apa yang keluar?
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" role="radiogroup">
                    @foreach ($stoks as $s)
                        <label class="relative cursor-pointer rounded-2xl border-2 p-4 flex items-center gap-3 transition"
                               :class="barang === '{{ $s->logistik_id }}' ? 'border-orange-500 bg-orange-50' : 'border-slate-200 hover:border-slate-300'">
                            <input type="radio" name="logistik_id" value="{{ $s->logistik_id }}" class="sr-only"
                                   x-model="barang" @change="jumlah = null" required>
                            <div class="w-10 h-10 shrink-0 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center">
                                <i class="fas fa-box"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="font-bold text-slate-900 truncate">{{ $s->logistik->nama_item }}</p>
                                <p class="text-sm text-slate-500">
                                    Sisa <b class="text-slate-800">{{ number_format($s->jumlah_stok, 0, ',', '.') }}</b> {{ $s->logistik->satuan }}
                                </p>
                            </div>
                            <i class="fas fa-circle-check text-orange-500 text-xl ml-auto" x-show="barang === '{{ $s->logistik_id }}'" x-cloak></i>
                        </label>
                    @endforeach
                </div>
                @error('logistik_id')
                    <p class="mt-2 text-sm font-semibold text-rose-600"><i class="fas fa-circle-exclamation"></i> {{ $message }}</p>
                @enderror
            </section>

            {{-- 2. Jumlah --}}
            <section class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6">
                <h2 class="font-bold text-slate-900 flex items-center gap-3 mb-4">
                    <span class="w-7 h-7 rounded-full bg-slate-900 text-white text-sm flex items-center justify-center">2</span>
                    Berapa banyak?
                </h2>

                <div class="flex items-center gap-3">
                    <button type="button" @click="ubah(-1)" :disabled="!pilihan"
                            class="w-14 h-14 shrink-0 rounded-2xl border-2 border-slate-200 text-xl font-black text-slate-700 hover:bg-slate-50 disabled:opacity-40"
                            aria-label="Kurangi">−</button>
                    <div class="relative flex-1">
                        <input type="number" name="jumlah_keluar" min="1" x-model.number="jumlah" :max="pilihan?.sisa" required
                               placeholder="0" inputmode="numeric"
                               class="w-full h-14 rounded-2xl border-2 border-slate-200 px-4 pr-20 text-2xl font-black text-center focus:outline-none focus:border-orange-500 @error('jumlah_keluar') border-rose-400 @enderror">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 font-semibold" x-text="pilihan?.satuan ?? ''"></span>
                    </div>
                    <button type="button" @click="ubah(1)" :disabled="!pilihan"
                            class="w-14 h-14 shrink-0 rounded-2xl border-2 border-slate-200 text-xl font-black text-slate-700 hover:bg-slate-50 disabled:opacity-40"
                            aria-label="Tambah">+</button>
                </div>

                <p class="mt-3 text-sm" x-show="pilihan" x-cloak>
                    <template x-if="sisaSetelah !== null && sisaSetelah >= 0">
                        <span class="text-slate-500">Sisa stok setelah ini:
                            <b class="text-slate-900" x-text="sisaSetelah.toLocaleString('id-ID') + ' ' + pilihan.satuan"></b>
                            <span x-show="sisaSetelah === 0" class="ml-1 font-semibold text-amber-600">(stok akan habis)</span>
                        </span>
                    </template>
                    <template x-if="sisaSetelah !== null && sisaSetelah < 0">
                        <span class="font-semibold text-rose-600">
                            <i class="fas fa-triangle-exclamation"></i>
                            Melebihi stok. Sisa hanya <span x-text="pilihan.sisa.toLocaleString('id-ID') + ' ' + pilihan.satuan"></span>.
                        </span>
                    </template>
                </p>
                <p class="mt-3 text-sm text-slate-400" x-show="!pilihan">Pilih barang dulu di langkah 1.</p>
                @error('jumlah_keluar')
                    <p class="mt-2 text-sm font-semibold text-rose-600"><i class="fas fa-circle-exclamation"></i> {{ $message }}</p>
                @enderror
            </section>

            {{-- 3. Tujuan --}}
            <section class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6">
                <h2 class="font-bold text-slate-900 flex items-center gap-3 mb-4">
                    <span class="w-7 h-7 rounded-full bg-slate-900 text-white text-sm flex items-center justify-center">3</span>
                    Untuk apa?
                </h2>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3" role="radiogroup">
                    @foreach (\App\Models\StokKeluar::TUJUAN as $key => [$label, $ikon])
                        <label class="cursor-pointer rounded-2xl border-2 p-4 flex flex-col items-center text-center gap-2 transition"
                               :class="tujuan === '{{ $key }}' ? 'border-orange-500 bg-orange-50 text-orange-700' : 'border-slate-200 text-slate-600 hover:border-slate-300'">
                            <input type="radio" name="tujuan" value="{{ $key }}" class="sr-only" x-model="tujuan" required>
                            <i class="fas {{ $ikon }} text-2xl"></i>
                            <span class="text-sm font-bold leading-tight">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                @error('tujuan')
                    <p class="mt-2 text-sm font-semibold text-rose-600"><i class="fas fa-circle-exclamation"></i> {{ $message }}</p>
                @enderror
            </section>

            {{-- Tambahan (opsional) --}}
            <section class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="tanggal_keluar" class="block text-sm font-bold text-slate-700 mb-1">Tanggal</label>
                    <input type="date" id="tanggal_keluar" name="tanggal_keluar" required
                           value="{{ old('tanggal_keluar', now()->toDateString()) }}" max="{{ now()->toDateString() }}"
                           class="w-full h-12 rounded-xl border-2 border-slate-200 px-3 focus:outline-none focus:border-orange-500">
                    @error('tanggal_keluar')
                        <p class="mt-1 text-sm font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="penerima" class="block text-sm font-bold text-slate-700 mb-1">
                        <span x-text="tujuan === 'dikirim' ? 'Dikirim ke posko mana?' : 'Diterima oleh'"></span>
                        <span class="font-normal text-slate-400">(boleh kosong)</span>
                    </label>
                    <input type="text" id="penerima" name="penerima" value="{{ old('penerima') }}" maxlength="255"
                           :placeholder="tujuan === 'dikirim' ? 'Contoh: Posko Balai Desa Sukamaju' : 'Contoh: Keluarga Pak Budi / 25 KK RT 02'"
                           class="w-full h-12 rounded-xl border-2 border-slate-200 px-3 focus:outline-none focus:border-orange-500">
                </div>
                <div class="sm:col-span-2">
                    <label for="keterangan" class="block text-sm font-bold text-slate-700 mb-1">
                        Catatan <span class="font-normal text-slate-400">(boleh kosong)</span>
                    </label>
                    <textarea id="keterangan" name="keterangan" rows="2" maxlength="1000"
                              placeholder="Contoh: dibagikan untuk makan siang"
                              class="w-full rounded-xl border-2 border-slate-200 px-3 py-2 focus:outline-none focus:border-orange-500">{{ old('keterangan') }}</textarea>
                </div>
            </section>

            {{-- Tombol --}}
            <div class="flex flex-col-reverse sm:flex-row gap-3 sm:justify-end pb-4">
                <a href="{{ route('logistik.index', $lokasi->id) }}"
                   class="inline-flex items-center justify-center h-14 px-6 rounded-2xl border-2 border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold">
                    Batal
                </a>
                <button type="submit"
                        :disabled="pilihan && jumlah > pilihan.sisa"
                        class="inline-flex items-center justify-center gap-2 h-14 px-8 rounded-2xl bg-orange-500 hover:bg-orange-600 disabled:opacity-50 disabled:cursor-not-allowed text-white text-lg font-black shadow-lg shadow-orange-500/30">
                    <i class="fas fa-check"></i> Simpan Barang Keluar
                </button>
            </div>
        </form>
    @endif
</div>
@endsection
