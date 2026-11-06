@extends('layouts.admin')

@section('title', 'Stok Semua Posko')

@section('content')
@php
    $angka = fn ($v) => number_format((float) $v, 0, ',', '.');
    // Warna & istilah sama dengan Monitoring Logistik posko
    $sel = [
        'merah'  => 'bg-red-50 text-red-700',
        'kuning' => 'bg-amber-50 text-amber-700',
        'hijau'  => 'bg-green-50 text-green-700',
        'netral' => 'text-slate-600',
    ];
    $tingkatUi = [
        'segera' => ['Butuh segera', 'bg-red-600 text-white'],
        'pantau' => ['Pantau',       'bg-amber-100 text-amber-800'],
        'cukup'  => ['Cukup',        'bg-green-100 text-green-700'],
        'belum'  => ['Belum dihitung', 'bg-slate-100 text-slate-600'],
    ];
    $target = \App\Services\OperasionalLokasiService::targetHari();

    // Jendela "Kirim": barang yang ada di gudang pusat + saran jumlah per posko (= kekurangannya)
    $barangGudang = $barang->filter(fn ($l) => isset($stokGudang[$l->id]))->map(fn ($l) => [
        'id' => $l->id, 'nama' => $l->nama_item, 'satuan' => $l->satuan, 'stok' => (int) $stokGudang[$l->id],
    ])->values();
    $dataPosko = $baris->mapWithKeys(fn ($b) => [$b['lokasi']->id => [
        'nama'  => $b['lokasi']->nama_lokasi,
        'saran' => $b['stok']->filter(fn ($s) => $s->perlu_dikirim)->map(fn ($s) => (int) $s->kekurangan),
    ]]);
@endphp

<div class="relative z-10 container mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10 flex flex-col gap-6"
     x-data="kirimPosko({
        poskos: @js($dataPosko),
        barang: @js($barangGudang),
        lamaPosko: @js(old('lokasi_id')),
        lamaJumlah: @js(old('items')),
     })">

    @include('pages.admin.gudang._tab')

    @if ($errors->any())
        <div class="px-4 py-3 rounded-xl bg-rose-50 text-rose-700 border border-rose-200 text-sm">
            @foreach ($errors->all() as $e)<p><i class="fas fa-exclamation-circle mr-1"></i>{{ $e }}</p>@endforeach
        </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 tracking-tight">Stok Semua Posko</h1>
            <p class="text-slate-500 mt-1 text-sm max-w-3xl">
                Bandingkan stok tiap posko dan stok {{ $gudang->nama ?? 'gudang pusat' }} dalam satu tabel untuk menentukan kiriman berikutnya.
                Angka di bawah stok = cukup untuk berapa hari. Warna sama dengan Monitoring Logistik posko.
            </p>
        </div>
        <a href="{{ route('admin.gudang.stok-posko', $semua ? [] : ['semua' => 1]) }}"
           class="shrink-0 inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white border border-slate-200 text-sm font-bold text-slate-700 hover:bg-slate-50">
            <i class="fas {{ $semua ? 'fa-filter' : 'fa-list' }}"></i>
            {{ $semua ? 'Hanya posko beroperasi' : 'Tampilkan juga posko tidak beroperasi' }}
        </a>
    </div>

    <div class="flex flex-wrap gap-3 text-xs font-semibold">
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-red-50 text-red-700"><span class="w-2 h-2 rounded-full bg-red-500"></span> Kritis (&lt; {{ \App\Services\OperasionalLokasiService::BATAS_MERAH }} hari)</span>
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-50 text-amber-700"><span class="w-2 h-2 rounded-full bg-amber-500"></span> Menipis (&lt; {{ \App\Services\OperasionalLokasiService::BATAS_KUNING }} hari)</span>
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-green-50 text-green-700"><span class="w-2 h-2 rounded-full bg-green-500"></span> Aman</span>
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 text-slate-600">"−" = belum pernah ada</span>
    </div>

    <div class="bg-white rounded-3xl border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden">
        @if ($baris->isEmpty())
            <p class="px-6 py-12 text-center text-slate-400">
                {{ $semua ? 'Belum ada posko.' : 'Tidak ada posko yang sedang beroperasi.' }}
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse">
                    <thead class="bg-slate-50 text-xs font-bold text-slate-500 uppercase">
                        <tr>
                            <th scope="col" class="sticky left-0 z-10 bg-slate-50 text-left px-4 py-3 min-w-[220px] border-b border-slate-100">Posko</th>
                            @foreach ($barang as $l)
                                <th scope="col" class="text-right px-3 py-3 whitespace-nowrap border-b border-slate-100">
                                    {{ $l->nama_item }}
                                    <span class="block text-[10px] font-medium normal-case text-slate-400">{{ $l->satuan }}</span>
                                </th>
                            @endforeach
                            <th scope="col" class="px-3 py-3 border-b border-slate-100"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        {{-- Gudang pusat --}}
                        <tr class="bg-blue-50/60">
                            <th scope="row" class="sticky left-0 z-10 bg-blue-50 text-left px-4 py-3 font-bold text-blue-900">
                                <i class="fas fa-warehouse mr-1"></i> {{ $gudang->nama ?? 'Gudang Pusat' }}
                                <span class="block text-xs font-medium text-blue-700">Siap dikirim ke posko</span>
                            </th>
                            @foreach ($barang as $l)
                                <td class="text-right px-3 py-3 font-bold text-blue-900">{{ isset($stokGudang[$l->id]) ? $angka($stokGudang[$l->id]) : '−' }}</td>
                            @endforeach
                            <td class="px-3 py-3 text-right">
                                <a href="{{ route('admin.gudang.index') }}" class="text-xs font-bold text-blue-700 hover:underline whitespace-nowrap">Kelola</a>
                            </td>
                        </tr>

                        @foreach ($baris as $b)
                            @php [$labelTingkat, $kelasTingkat] = $tingkatUi[$b['tingkat']] ?? ['Tidak beroperasi', 'bg-slate-100 text-slate-500']; @endphp
                            <tr class="hover:bg-slate-50/60">
                                <th scope="row" class="sticky left-0 z-10 bg-white text-left px-4 py-3 font-normal">
                                    <a href="{{ route('logistik.index', $b['lokasi']->id) }}" class="font-bold text-slate-800 hover:text-red-600">{{ $b['lokasi']->nama_lokasi }}</a>
                                    <span class="block text-xs text-slate-500">
                                        {{ $b['lokasi']->desa->nama_desa ?? '' }} · {{ $angka($b['jumlah_pengungsi']) }} pengungsi
                                    </span>
                                    <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-[10px] font-bold {{ $kelasTingkat }}">{{ $labelTingkat }}</span>
                                </th>
                                @foreach ($barang as $l)
                                    @php $s = $b['stok'][$l->id] ?? null; @endphp
                                    <td class="text-right px-3 py-3 align-top {{ $s ? $sel[$s->status] : '' }}">
                                        @if (! $s || $s->belum_pernah_masuk)
                                            <span class="font-bold {{ $s && $s->status === 'merah' ? '' : 'text-slate-300' }}">−</span>
                                        @else
                                            <span class="font-bold">{{ $angka($s->jumlah_stok) }}</span>
                                        @endif
                                        @if ($s && $s->cukup_hari !== null)
                                            <span class="block text-[11px] opacity-80 whitespace-nowrap">{{ \App\Services\OperasionalLokasiService::teksHari($s->cukup_hari) }}</span>
                                        @endif
                                        @if ($s && $s->perlu_dikirim && $s->mendesak)
                                            <span class="block text-[11px] font-bold whitespace-nowrap">kirim ±{{ $angka($s->kekurangan) }}</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="px-3 py-3 text-right align-top">
                                    @if ($barangGudang->isNotEmpty())
                                        <button type="button" @click="buka({{ $b['lokasi']->id }})"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold whitespace-nowrap">
                                            <i class="fas fa-truck"></i> Kirim
                                        </button>
                                    @else
                                        <span class="text-[11px] text-slate-400 whitespace-nowrap" title="Belum ada stok di gudang pusat">Gudang kosong</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50 text-sm border-t-2 border-slate-200">
                        <tr>
                            <th scope="row" class="sticky left-0 z-10 bg-slate-50 text-left px-4 py-3 font-bold text-slate-700">
                                Total di {{ $baris->count() }} posko
                                <span class="block text-xs font-medium text-slate-500">Kekurangan agar cukup {{ $target }} hari</span>
                            </th>
                            @foreach ($barang as $l)
                                <td class="text-right px-3 py-3">
                                    <span class="font-bold text-slate-800">{{ $angka($total[$l->id]['stok']) }}</span>
                                    @if ($total[$l->id]['kekurangan'] > 0)
                                        <span class="block text-[11px] font-bold text-red-600 whitespace-nowrap">kurang ±{{ $angka($total[$l->id]['kekurangan']) }}</span>
                                    @endif
                                </td>
                            @endforeach
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>
    {{-- ===== JENDELA KIRIM DARI GUDANG PUSAT KE POSKO ===== --}}
    <div x-show="terbuka" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="judul-kirim"
         @keydown.escape.window="terbuka = false">
        <div class="absolute inset-0 bg-slate-900/50" @click="terbuka = false"></div>

        <form method="POST" action="{{ $gudang ? route('admin.gudang.kirim-banyak', $gudang) : '#' }}" @submit="mengirim = true"
              class="relative w-full max-w-lg max-h-[90vh] overflow-y-auto bg-white rounded-3xl shadow-2xl p-6 space-y-4">
            @csrf
            <input type="hidden" name="lokasi_id" :value="posko">

            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 id="judul-kirim" class="text-lg font-extrabold text-slate-800"><i class="fas fa-truck text-blue-600 mr-1"></i> Kirim ke <span x-text="poskos[posko]?.nama"></span></h2>
                    <p class="text-xs text-slate-500 mt-1">Jumlah sudah diisi sesuai kekurangan posko (maksimal sebanyak stok gudang). Ubah sesuai barang yang benar-benar dikirim, isi 0 untuk barang yang tidak dikirim.</p>
                </div>
                <button type="button" @click="terbuka = false" class="text-slate-400 hover:text-slate-700" aria-label="Tutup"><i class="fas fa-times"></i></button>
            </div>

            <table class="w-full text-sm">
                <thead class="text-xs text-slate-500 uppercase">
                    <tr><th class="text-left py-1">Barang</th><th class="text-right py-1">Stok gudang</th><th class="text-right py-1">Kurang di posko</th><th class="text-right py-1 w-28">Kirim</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="l in barang" :key="l.id">
                        <tr>
                            <td class="py-2 font-semibold text-slate-800" x-text="l.nama"></td>
                            <td class="py-2 text-right text-slate-600" x-text="angka(l.stok) + ' ' + l.satuan"></td>
                            <td class="py-2 text-right" :class="saran(l.id) ? 'text-red-600 font-bold' : 'text-slate-400'" x-text="saran(l.id) ? '±' + angka(saran(l.id)) : '-'"></td>
                            <td class="py-2 text-right">
                                <input type="number" min="0" :max="l.stok" :name="`items[${l.id}]`" x-model.number="jumlah[l.id]"
                                       class="w-24 rounded-lg border border-slate-200 px-2 py-1.5 text-right text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                                       :aria-label="'Jumlah ' + l.nama + ' yang dikirim'">
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label for="tanggal-kirim" class="block mb-1 text-xs font-bold text-slate-600">Tanggal kirim</label>
                    <input id="tanggal-kirim" type="date" name="tanggal" value="{{ old('tanggal', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required
                           class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label for="ket-kirim" class="block mb-1 text-xs font-bold text-slate-600">Keterangan (opsional)</label>
                    <input id="ket-kirim" type="text" name="keterangan" maxlength="500" value="{{ old('keterangan') }}" placeholder="contoh: Diantar mobil GMLS"
                           class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
            </div>

            <p class="text-xs text-slate-500">Stok gudang pusat berkurang dan stok posko bertambah otomatis. Relawan posko mendapat notifikasi.</p>

            <div class="flex justify-end gap-2">
                <button type="button" @click="terbuka = false" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-bold text-slate-600 hover:bg-slate-50">Batal</button>
                <button type="submit" :disabled="mengirim || jumlahDikirim() === 0" :class="(mengirim || jumlahDikirim() === 0) ? 'opacity-50 cursor-not-allowed' : ''"
                        class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold">
                    <i class="fas fa-truck mr-1"></i> Kirim <span x-text="jumlahDikirim()"></span> jenis barang
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function kirimPosko({ poskos, barang, lamaPosko, lamaJumlah }) {
        return {
            poskos, barang,
            terbuka: false, mengirim: false, posko: null, jumlah: {},
            angka: (n) => Number(n).toLocaleString('id-ID'),
            saran(id) { return Number(this.poskos[this.posko]?.saran?.[id] ?? 0); },
            buka(id) {
                this.posko = id;
                this.jumlah = Object.fromEntries(this.barang.map(l => [l.id, Math.min(this.saran(l.id), l.stok)]));
                this.mengirim = false;
                this.terbuka = true;
            },
            jumlahDikirim() { return Object.values(this.jumlah).filter(j => Number(j) > 0).length; },
            init() {
                // Setelah gagal kirim (misal stok tidak cukup), buka lagi dengan isian sebelumnya
                if (lamaPosko && this.poskos[lamaPosko]) {
                    this.buka(Number(lamaPosko));
                    if (lamaJumlah) this.jumlah = Object.fromEntries(Object.entries(lamaJumlah).map(([k, v]) => [k, Number(v) || 0]));
                }
            },
        };
    }
</script>
@endpush

