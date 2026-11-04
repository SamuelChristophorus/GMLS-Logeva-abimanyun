@extends('layouts.dashboard')

@section('title', 'Form Donasi')

@section('content')
@php
    $input = 'w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition';
    $poskoData = $poskos->map(fn ($p) => [
        'id'     => $p->id,
        'nama'   => $p->nama_lokasi,
        'desa'   => $p->desa->nama_desa ?? '',
        'alamat' => $p->alamat_lokasi,
    ])->values();
    $itemsLama = collect(old('items', []))->filter(fn ($i) => ! empty($i['logistik_id']))->values();
    $bisaUang  = $gudang?->punyaRekening();
@endphp

<div class="max-w-3xl mx-auto"
     x-data="formDonasi({
        jenis: @js(old('jenis', $bisaUang ? 'uang' : 'logistik')),
        tujuan: @js(old('tujuan', $pilihPosko ? 'posko' : 'pusat')),
        posko: @js((string) old('lokasi_id', $pilihPosko ?? '')),
        items: @js($itemsLama->isNotEmpty() ? $itemsLama : [['logistik_id' => '', 'jumlah' => '']]),
        poskos: @js($poskoData),
        kebutuhan: @js($kebutuhan),
     })">

    <div class="mb-6">
        <a href="{{ route('donasi.index') }}" class="text-sm text-slate-500 hover:text-red-600"><i class="fas fa-arrow-left mr-1"></i> Kembali</a>
        <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 mt-2">Form Donasi</h1>
        <p class="text-slate-500">Tidak perlu membuat akun. Setelah dikirim, Anda mendapat kode untuk melacak donasi.</p>
    </div>

    @if ($errors->any())
        <div class="mb-6 px-4 py-3 rounded-xl bg-rose-50 text-rose-700 border border-rose-200 text-sm space-y-1">
            @foreach ($errors->all() as $e)
                <p><i class="fas fa-exclamation-circle mr-1"></i>{{ $e }}</p>
            @endforeach
        </div>
    @endif

    <form action="{{ route('donasi.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        {{-- Jebakan bot (tidak terlihat) --}}
        <div class="hidden" aria-hidden="true"><input type="text" name="situs_web" tabindex="-1" autocomplete="off"></div>

        {{-- 1. JENIS --}}
        <section class="bg-white rounded-2xl border border-slate-200 p-6">
            <h2 class="font-bold text-slate-800 mb-4">1. Mau berdonasi apa?</h2>
            <div class="grid sm:grid-cols-2 gap-3">
                <label class="cursor-pointer rounded-xl border-2 p-4 transition"
                       :class="jenis === 'uang' ? 'border-red-500 bg-red-50' : 'border-slate-200 hover:border-slate-300'">
                    <input type="radio" name="jenis" value="uang" x-model="jenis" class="sr-only" @disabled(! $bisaUang)>
                    <p class="font-bold text-slate-800"><i class="fas fa-money-bill-wave text-green-600 mr-2"></i>Uang</p>
                    <p class="text-sm text-slate-500 mt-1">
                        @if ($bisaUang)
                            Transfer ke rekening GMLS. GMLS membelikan kebutuhan posko dan melaporkannya.
                        @else
                            Belum tersedia, rekening donasi belum diumumkan.
                        @endif
                    </p>
                </label>
                <label class="cursor-pointer rounded-xl border-2 p-4 transition"
                       :class="jenis === 'logistik' ? 'border-red-500 bg-red-50' : 'border-slate-200 hover:border-slate-300'">
                    <input type="radio" name="jenis" value="logistik" x-model="jenis" class="sr-only">
                    <p class="font-bold text-slate-800"><i class="fas fa-box text-blue-600 mr-2"></i>Barang / Logistik</p>
                    <p class="text-sm text-slate-500 mt-1">Anda kirim sendiri barangnya ke GMLS Pusat atau langsung ke posko.</p>
                </label>
            </div>
        </section>

        {{-- 2. TUJUAN --}}
        <section class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
            <h2 class="font-bold text-slate-800">2. Untuk siapa?</h2>
            <div class="grid sm:grid-cols-2 gap-3">
                <label class="cursor-pointer rounded-xl border-2 p-4 transition"
                       :class="tujuan === 'pusat' ? 'border-red-500 bg-red-50' : 'border-slate-200 hover:border-slate-300'">
                    <input type="radio" name="tujuan" value="pusat" x-model="tujuan" class="sr-only">
                    <p class="font-bold text-slate-800"><i class="fas fa-warehouse text-red-600 mr-2"></i>GMLS Pusat</p>
                    <p class="text-sm text-slate-500 mt-1">GMLS menyalurkan ke posko yang paling membutuhkan.</p>
                </label>
                <label class="cursor-pointer rounded-xl border-2 p-4 transition"
                       :class="tujuan === 'posko' ? 'border-red-500 bg-red-50' : 'border-slate-200 hover:border-slate-300'">
                    <input type="radio" name="tujuan" value="posko" x-model="tujuan" class="sr-only" @disabled($poskos->isEmpty())>
                    <p class="font-bold text-slate-800"><i class="fas fa-campground text-red-600 mr-2"></i>Posko tertentu</p>
                    <p class="text-sm text-slate-500 mt-1">
                        {{ $poskos->isEmpty() ? 'Belum ada posko yang sedang beroperasi.' : 'Pilih posko yang ingin Anda bantu.' }}
                    </p>
                </label>
            </div>

            <div x-show="tujuan === 'posko'" x-cloak class="space-y-3">
                <select name="lokasi_id" x-model="posko" class="{{ $input }}" :required="tujuan === 'posko'">
                    <option value="">-- Pilih posko --</option>
                    @foreach ($poskos as $p)
                        <option value="{{ $p->id }}">{{ $p->nama_lokasi }}{{ $p->desa ? ' · ' . $p->desa->nama_desa : '' }}</option>
                    @endforeach
                </select>

                <template x-if="posko && (kebutuhan[posko] || []).length">
                    <div class="rounded-xl bg-amber-50 border border-amber-200 p-4 text-sm">
                        <p class="font-semibold text-amber-800 mb-1"><i class="fas fa-triangle-exclamation mr-1"></i> Yang sedang dibutuhkan posko ini:</p>
                        <ul class="list-disc pl-5 text-amber-900">
                            <template x-for="k in kebutuhan[posko]" :key="k.id">
                                <li><span x-text="k.nama"></span> ±<span x-text="k.jumlah.toLocaleString('id-ID')"></span> <span x-text="k.satuan"></span></li>
                            </template>
                        </ul>
                        <button type="button" x-show="jenis === 'logistik'" @click="isiKebutuhan()"
                                class="mt-2 text-xs font-bold text-amber-800 underline">Isi daftar barang sesuai kebutuhan ini</button>
                    </div>
                </template>
            </div>
        </section>

        {{-- 3a. UANG --}}
        <section x-show="jenis === 'uang'" x-cloak class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
            <h2 class="font-bold text-slate-800">3. Transfer &amp; bukti</h2>
            @if ($bisaUang)
                <div class="rounded-xl bg-slate-50 border border-slate-200 p-4">
                    <p class="text-xs text-slate-500">Transfer ke rekening resmi GMLS:</p>
                    <p class="font-bold text-slate-800">{{ $gudang->nama_bank }} · <span class="font-mono tracking-wider">{{ $gudang->nomor_rekening }}</span></p>
                    <p class="text-sm text-slate-600">a.n. {{ $gudang->atas_nama_rekening }}</p>
                    <p class="text-xs text-slate-500 mt-2" x-show="tujuan === 'posko'">Dana untuk posko tetap ditransfer ke rekening ini, lalu GMLS membelikan kebutuhan posko yang Anda pilih.</p>
                </div>
            @endif
            <div>
                <label class="block mb-2 text-sm font-semibold text-slate-700">Jumlah yang ditransfer (Rp)</label>
                <input type="number" name="nominal" min="10000" step="1" value="{{ old('nominal') }}" class="{{ $input }}"
                       placeholder="contoh: 250000" :required="jenis === 'uang'" :disabled="jenis !== 'uang'">
            </div>
            <div>
                <label class="block mb-2 text-sm font-semibold text-slate-700">Bukti transfer (foto / tangkapan layar)</label>
                <input type="file" name="bukti_transfer" accept="image/jpeg,image/png,image/webp"
                       class="block w-full text-sm text-slate-600 file:mr-3 file:px-4 file:py-2 file:rounded-lg file:border-0 file:bg-red-50 file:text-red-700 file:font-semibold"
                       :required="jenis === 'uang'" :disabled="jenis !== 'uang'">
                <p class="text-xs text-slate-400 mt-1">JPG / PNG, maksimal 4 MB. Hanya bisa dilihat admin GMLS.</p>
            </div>
        </section>

        {{-- 3b. LOGISTIK --}}
        <section x-show="jenis === 'logistik'" x-cloak class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
            <h2 class="font-bold text-slate-800">3. Barang yang dikirim</h2>

            <div class="space-y-2">
                <template x-for="(item, i) in items" :key="i">
                    <div class="flex gap-2">
                        <select :name="`items[${i}][logistik_id]`" x-model="item.logistik_id" class="{{ $input }} flex-1" :disabled="jenis !== 'logistik'">
                            <option value="">-- Pilih barang --</option>
                            @foreach ($logistiks as $l)
                                <option value="{{ $l->id }}">{{ $l->nama_item }} ({{ $l->satuan }})</option>
                            @endforeach
                        </select>
                        <input type="number" min="1" :name="`items[${i}][jumlah]`" x-model="item.jumlah" placeholder="Jumlah"
                               class="{{ $input }} w-32" :disabled="jenis !== 'logistik'">
                        <button type="button" @click="items.splice(i, 1)" x-show="items.length > 1"
                                class="px-3 rounded-xl border border-slate-200 text-slate-400 hover:text-red-600" aria-label="Hapus barang">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </template>
                <button type="button" @click="items.push({ logistik_id: '', jumlah: '' })"
                        class="text-sm font-semibold text-red-600 hover:underline"><i class="fas fa-plus mr-1"></i> Tambah barang</button>
                <p class="text-xs text-slate-400">Barang tidak ada di daftar? Tulis di kolom pesan di bawah.</p>
            </div>

            <div class="rounded-xl bg-blue-50 border border-blue-200 p-4 text-sm text-blue-900">
                <p class="font-semibold"><i class="fas fa-location-dot mr-1"></i> Kirim barang ke:</p>
                <p x-show="tujuan === 'pusat'">{{ $gudang->nama ?? 'Posko Pusat GMLS' }}{{ $gudang?->alamat ? ' · ' . $gudang->alamat : '' }}{{ $gudang?->kontak ? ' · ' . $gudang->kontak : '' }}</p>
                <p x-show="tujuan === 'posko'" x-text="alamatPosko() || 'Pilih posko terlebih dahulu.'"></p>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-2 text-sm font-semibold text-slate-700">Perkiraan tiba</label>
                    <input type="date" name="perkiraan_tiba" min="{{ now()->toDateString() }}" value="{{ old('perkiraan_tiba') }}" class="{{ $input }}" :disabled="jenis !== 'logistik'">
                </div>
                <div>
                    <label class="block mb-2 text-sm font-semibold text-slate-700">Dikirim lewat <span class="font-normal text-slate-400">(opsional)</span></label>
                    <input type="text" name="pengirim" value="{{ old('pengirim') }}" maxlength="120" class="{{ $input }}" placeholder="contoh: diantar sendiri / JNE / truk" :disabled="jenis !== 'logistik'">
                </div>
            </div>
        </section>

        {{-- 4. DATA DONATUR --}}
        <section class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
            <h2 class="font-bold text-slate-800">4. Data donatur</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-2 text-sm font-semibold text-slate-700">Nama / instansi</label>
                    <input type="text" name="nama_donatur" value="{{ old('nama_donatur') }}" maxlength="120" required class="{{ $input }}">
                </div>
                <div>
                    <label class="block mb-2 text-sm font-semibold text-slate-700">No. HP / WhatsApp / email</label>
                    <input type="text" name="kontak" value="{{ old('kontak') }}" maxlength="100" required class="{{ $input }}">
                    <p class="text-xs text-slate-400 mt-1">Hanya untuk dihubungi GMLS, tidak ditampilkan ke publik.</p>
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="anonim" value="1" @checked(old('anonim')) class="rounded border-slate-300 text-red-600 focus:ring-red-500">
                Sembunyikan nama saya di laporan publik (tampil sebagai "Donatur Anonim")
            </label>
            <div>
                <label class="block mb-2 text-sm font-semibold text-slate-700">Pesan <span class="font-normal text-slate-400">(opsional)</span></label>
                <textarea name="pesan" rows="3" maxlength="500" class="{{ $input }}" placeholder="Pesan / doa untuk penyintas, atau catatan barang">{{ old('pesan') }}</textarea>
            </div>
        </section>

        <button type="submit" class="w-full py-4 rounded-2xl bg-red-600 hover:bg-red-700 text-white font-bold text-lg shadow-lg">
            <i class="fas fa-paper-plane mr-2"></i> Kirim Donasi
        </button>
    </form>
</div>
@endsection

@push('scripts')
<script>
    function formDonasi(awal) {
        return {
            ...awal,
            alamatPosko() {
                const p = this.poskos.find(p => String(p.id) === String(this.posko));
                return p ? `${p.nama} · ${p.alamat}${p.desa ? ', ' + p.desa : ''}` : '';
            },
            isiKebutuhan() {
                this.items = (this.kebutuhan[this.posko] || []).map(k => ({ logistik_id: String(k.id), jumlah: k.jumlah }));
            },
        };
    }
</script>
@endpush
