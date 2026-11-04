@extends('layouts.admin')

@section('title', 'Donasi ' . $donasi->kode)

@section('content')
@php
    $rp    = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');
    $input = 'w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-red-500 outline-none';
    $label = 'block mb-1 text-xs font-bold text-slate-600';
    $warna = [
        'green' => 'bg-green-100 text-green-700',
        'red'   => 'bg-red-100 text-red-700',
        'amber' => 'bg-amber-100 text-amber-700',
        'blue'  => 'bg-blue-100 text-blue-700',
        'slate' => 'bg-slate-100 text-slate-700',
    ];
    $st   = $donasi->labelStatus();
    $sisaBarang = $donasi->isUang() ? collect() : $donasi->sisaBarang();
    $barangBisaDikirim = $donasi->items->filter(fn ($i) => ($sisaBarang[$i->logistik_id] ?? 0) > 0)->unique('logistik_id');
@endphp

<div class="relative z-10 container mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10 flex flex-col gap-6">

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('admin.donasi.index') }}" class="text-sm text-slate-500 hover:text-red-600"><i class="fas fa-arrow-left mr-1"></i> Semua donasi</a>
            <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 mt-1 font-mono">{{ $donasi->kode }}</h1>
            <p class="text-slate-500 text-sm">{{ $donasi->isUang() ? 'Donasi uang' : 'Donasi barang' }} untuk <b>{{ $donasi->namaTujuan() }}</b> · {{ $donasi->created_at->translatedFormat('d M Y, H:i') }} WIB</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-bold {{ $warna[$st['warna']] }}"><i class="fas {{ $st['ikon'] }}"></i> {{ $st['label'] }}</span>
            <a href="{{ route('donasi.lacak', ['kode' => $donasi->kode]) }}" target="_blank" class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                <i class="fas fa-eye mr-1"></i> Tampilan donatur
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="px-4 py-3 rounded-xl bg-rose-50 text-rose-700 border border-rose-200 text-sm">
            @foreach ($errors->all() as $e)<p><i class="fas fa-exclamation-circle mr-1"></i>{{ $e }}</p>@endforeach
        </div>
    @endif

    <div class="grid lg:grid-cols-3 gap-6">

        {{-- KIRI: info & aksi --}}
        <div class="lg:col-span-2 space-y-6">

            <section class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 grid sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase">Donatur</p>
                    <p class="font-bold text-slate-800 text-base">{{ $donasi->nama_donatur }}</p>
                    <p class="text-slate-600">{{ $donasi->kontak }}</p>
                    @if ($donasi->anonim)<p class="text-xs text-slate-400">Minta nama disembunyikan di laporan publik</p>@endif
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase">Tujuan</p>
                    <p class="font-bold text-slate-800">{{ $donasi->namaTujuan() }}</p>
                    @if ($donasi->lokasi?->desa)<p class="text-slate-600">{{ $donasi->lokasi->desa->nama_desa }}</p>@endif
                </div>

                @if ($donasi->isUang())
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase">Nominal dilaporkan</p>
                        <p class="font-bold text-slate-800 text-base">{{ $rp($donasi->nominal) }}</p>
                        @if ($donasi->nominal_diterima !== null)
                            <p class="text-green-700 font-semibold">Diterima {{ $rp($donasi->nominal_diterima) }} · sisa {{ $rp($donasi->sisaDana()) }}</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase mb-1">Bukti transfer</p>
                        @if ($donasi->bukti_transfer)
                            <a href="{{ route('admin.donasi.bukti', $donasi) }}" target="_blank">
                                <img src="{{ route('admin.donasi.bukti', $donasi) }}" alt="Bukti transfer {{ $donasi->kode }}" class="h-40 rounded-xl border border-slate-200 object-contain bg-slate-50">
                            </a>
                        @else
                            <p class="text-slate-400">Tidak ada</p>
                        @endif
                    </div>
                @else
                    <div class="sm:col-span-2 overflow-x-auto">
                        <table class="w-full">
                            <thead class="text-xs text-slate-400 uppercase">
                                <tr><th class="text-left py-1">Barang</th><th class="text-right">Dijanjikan</th><th class="text-right">Diterima</th>@unless ($donasi->isKePosko())<th class="text-right">Sisa di gudang</th>@endunless</tr>
                            </thead>
                            <tbody>
                                @foreach ($donasi->items as $i)
                                    <tr class="border-t border-slate-100">
                                        <td class="py-2 font-semibold">{{ $i->logistik->nama_item }}</td>
                                        <td class="py-2 text-right">{{ number_format($i->jumlah, 0, ',', '.') }} {{ $i->logistik->satuan }}</td>
                                        <td class="py-2 text-right">{{ $i->jumlah_diterima === null ? '-' : number_format($i->jumlah_diterima, 0, ',', '.') }}</td>
                                        @unless ($donasi->isKePosko())<td class="py-2 text-right">{{ $i->jumlah_diterima === null ? '-' : number_format($sisaBarang[$i->logistik_id] ?? 0, 0, ',', '.') }}</td>@endunless
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <p class="text-xs text-slate-500 mt-2">
                            @if ($donasi->perkiraan_tiba)Perkiraan tiba {{ $donasi->perkiraan_tiba->translatedFormat('d M Y') }}.@endif
                            @if ($donasi->pengirim) Dikirim lewat {{ $donasi->pengirim }}.@endif
                        </p>
                    </div>
                @endif

                @if ($donasi->pesan)<p class="sm:col-span-2 italic text-slate-600">"{{ $donasi->pesan }}"</p>@endif
                @if ($donasi->verifikator)
                    <p class="sm:col-span-2 text-xs text-slate-400">Diproses oleh {{ $donasi->verifikator->name }} · {{ $donasi->diverifikasi_pada?->translatedFormat('d M Y, H:i') }}{{ $donasi->catatan_admin ? ' · ' . $donasi->catatan_admin : '' }}</p>
                @endif
            </section>

            {{-- ===== AKSI: MENUNGGU ===== --}}
            @if ($donasi->isMenunggu())
                <section class="bg-white rounded-3xl border-2 border-amber-200 shadow-sm p-6 space-y-4">
                    <h2 class="font-bold text-slate-800"><i class="fas fa-clipboard-check text-amber-500 mr-1"></i>
                        {{ $donasi->isUang() ? 'Cek transfer di rekening GMLS' : 'Konfirmasi barang yang sampai' }}</h2>

                    @if ($donasi->isUang())
                        <form method="POST" action="{{ route('admin.donasi.verifikasi', $donasi) }}" class="grid sm:grid-cols-3 gap-3 items-end">
                            @csrf @method('PATCH')
                            <div>
                                <label class="{{ $label }}">Dana yang benar-benar masuk (Rp)</label>
                                <input type="number" name="nominal_diterima" min="1" value="{{ old('nominal_diterima', $donasi->nominal) }}" required class="{{ $input }}">
                            </div>
                            <div>
                                <label class="{{ $label }}">Catatan (opsional)</label>
                                <input type="text" name="catatan" maxlength="500" class="{{ $input }}">
                            </div>
                            <button class="py-2.5 rounded-xl bg-green-600 hover:bg-green-700 text-white font-bold text-sm"><i class="fas fa-check mr-1"></i> Dana diterima</button>
                        </form>
                    @else
                        @if ($donasi->isKePosko())
                            <p class="text-sm text-slate-500">Biasanya dikonfirmasi relawan posko tujuan. Admin juga bisa mengonfirmasi di sini. Barang langsung masuk stok posko.</p>
                        @else
                            <p class="text-sm text-slate-500">Barang akan masuk stok Gudang Pusat GMLS, lalu bisa dikirim ke posko.</p>
                        @endif
                        <form method="POST" action="{{ route('admin.donasi.terima', $donasi) }}" class="space-y-3">
                            @csrf @method('PATCH')
                            @foreach ($donasi->items as $i)
                                <div class="flex items-center gap-3">
                                    <label for="d{{ $i->id }}" class="flex-1 text-sm"><b>{{ $i->logistik->nama_item }}</b> <span class="text-slate-400">(dijanjikan {{ number_format($i->jumlah, 0, ',', '.') }})</span></label>
                                    <input id="d{{ $i->id }}" type="number" min="0" name="diterima[{{ $i->id }}]" value="{{ old('diterima.' . $i->id, $i->jumlah) }}" class="{{ $input }} !w-28">
                                    <span class="w-12 text-sm text-slate-500">{{ $i->logistik->satuan }}</span>
                                </div>
                            @endforeach
                            <input type="text" name="catatan" maxlength="500" placeholder="Catatan (opsional)" class="{{ $input }}">
                            <button class="px-5 py-2.5 rounded-xl bg-green-600 hover:bg-green-700 text-white font-bold text-sm"><i class="fas fa-check mr-1"></i> Barang sudah sampai</button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('admin.donasi.tolak', $donasi) }}" class="flex flex-wrap gap-2 pt-3 border-t border-slate-100"
                          onsubmit="return confirm('Tandai donasi ini tidak terverifikasi?')">
                        @csrf @method('PATCH')
                        <input type="text" name="alasan" maxlength="500" placeholder="{{ $donasi->isUang() ? 'Alasan, misalnya: transfer tidak ditemukan' : 'Alasan, misalnya: barang tidak datang' }}" class="{{ $input }} flex-1 !w-auto min-w-[200px]">
                        <button class="px-4 py-2 rounded-xl border border-red-200 bg-red-50 text-red-700 font-bold text-sm hover:bg-red-100">Tolak</button>
                    </form>
                </section>
            @endif

            {{-- ===== AKSI: SALURKAN DANA ===== --}}
            @if ($donasi->isUang() && $donasi->bisaDisalurkan())
                <section class="bg-white rounded-3xl border-2 border-blue-200 shadow-sm p-6"
                         x-data="{ tujuan: '{{ old('ke_gudang') ? 'gudang' : 'posko' }}' }">
                    <h2 class="font-bold text-slate-800 mb-1"><i class="fas fa-hand-holding-usd text-blue-500 mr-1"></i> Catat pemakaian dana</h2>
                    <p class="text-sm text-slate-500 mb-4">Sisa dana: <b>{{ $rp($donasi->sisaDana()) }}</b>. Jika dana dibelikan barang, isi barangnya supaya stok tujuan otomatis bertambah.</p>

                    <form method="POST" action="{{ route('admin.donasi.salurkan', $donasi) }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <div class="flex flex-wrap gap-4 text-sm">
                            <label class="flex items-center gap-2"><input type="radio" x-model="tujuan" value="posko"> Untuk posko</label>
                            <label class="flex items-center gap-2"><input type="radio" x-model="tujuan" value="gudang"> Stok gudang pusat</label>
                            <label class="flex items-center gap-2"><input type="radio" x-model="tujuan" value="operasional"> Operasional (transport, dll.)</label>
                        </div>
                        <input type="hidden" name="ke_gudang" :value="tujuan === 'gudang' ? 1 : 0">

                        <div class="grid sm:grid-cols-2 gap-3">
                            <div x-show="tujuan === 'posko'">
                                <label class="{{ $label }}">Posko</label>
                                <select name="lokasi_id" class="{{ $input }}" :disabled="tujuan !== 'posko'" :required="tujuan === 'posko'">
                                    <option value="">-- Pilih posko --</option>
                                    @foreach ($poskos as $p)
                                        <option value="{{ $p->id }}" @selected(old('lokasi_id', $donasi->lokasi_id) == $p->id)>{{ $p->nama_lokasi }}{{ $p->isBeroperasi() ? '' : ' (tidak beroperasi)' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="{{ $label }}">Dana dipakai (Rp)</label>
                                <input type="number" name="nominal" min="1" max="{{ $donasi->sisaDana() }}" value="{{ old('nominal') }}" required class="{{ $input }}">
                            </div>
                            <div x-show="tujuan !== 'operasional'">
                                <label class="{{ $label }}">Dibelikan barang (opsional)</label>
                                <select name="logistik_id" class="{{ $input }}" :disabled="tujuan === 'operasional'">
                                    <option value="">-- Tidak ada --</option>
                                    @foreach ($logistiks as $l)
                                        <option value="{{ $l->id }}" @selected(old('logistik_id') == $l->id)>{{ $l->nama_item }} ({{ $l->satuan }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div x-show="tujuan !== 'operasional'">
                                <label class="{{ $label }}">Jumlah barang</label>
                                <input type="number" name="jumlah" min="1" value="{{ old('jumlah') }}" class="{{ $input }}" :disabled="tujuan === 'operasional'">
                            </div>
                            <div>
                                <label class="{{ $label }}">Tanggal</label>
                                <input type="date" name="tanggal" value="{{ old('tanggal', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required class="{{ $input }}">
                            </div>
                            <div>
                                <label class="{{ $label }}">Foto nota / dokumentasi (opsional, tampil ke donatur)</label>
                                <input type="file" name="foto" accept="image/*" class="text-sm">
                            </div>
                        </div>
                        <div>
                            <label class="{{ $label }}">Keterangan untuk donatur</label>
                            <input type="text" name="keterangan" maxlength="500" value="{{ old('keterangan') }}" required class="{{ $input }}" placeholder="contoh: Beli beras 100 kg di Toko Sumber Rezeki, Bayah">
                        </div>
                        <button class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm"><i class="fas fa-save mr-1"></i> Simpan penyaluran</button>
                    </form>
                </section>
            @endif

            {{-- ===== AKSI: KIRIM BARANG DARI GUDANG ===== --}}
            @if (! $donasi->isUang() && ! $donasi->isKePosko() && $donasi->bisaDisalurkan())
                <section class="bg-white rounded-3xl border-2 border-blue-200 shadow-sm p-6">
                    <h2 class="font-bold text-slate-800 mb-1"><i class="fas fa-truck text-blue-500 mr-1"></i> Kirim barang donasi ke posko</h2>
                    <p class="text-sm text-slate-500 mb-4">Stok gudang pusat berkurang dan stok posko tujuan bertambah otomatis.</p>

                    <form method="POST" action="{{ route('admin.donasi.salurkan', $donasi) }}" enctype="multipart/form-data" class="grid sm:grid-cols-2 gap-3">
                        @csrf
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
                                @foreach ($barangBisaDikirim as $i)
                                    <option value="{{ $i->logistik_id }}" @selected(old('logistik_id') == $i->logistik_id)>
                                        {{ $i->logistik->nama_item }} · sisa donasi {{ $sisaBarang[$i->logistik_id] }} · stok gudang {{ $stokGudang[$i->logistik_id] ?? 0 }} {{ $i->logistik->satuan }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $label }}">Jumlah</label>
                            <input type="number" name="jumlah" min="1" value="{{ old('jumlah') }}" required class="{{ $input }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">Tanggal kirim</label>
                            <input type="date" name="tanggal" value="{{ old('tanggal', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required class="{{ $input }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">Keterangan (opsional)</label>
                            <input type="text" name="keterangan" maxlength="500" value="{{ old('keterangan') }}" class="{{ $input }}" placeholder="contoh: Diantar mobil bak GMLS">
                        </div>
                        <div>
                            <label class="{{ $label }}">Foto dokumentasi (opsional)</label>
                            <input type="file" name="foto" accept="image/*" class="text-sm">
                        </div>
                        <div class="sm:col-span-2">
                            <button class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm"><i class="fas fa-truck mr-1"></i> Kirim ke posko</button>
                        </div>
                    </form>
                </section>
            @endif

            {{-- RIWAYAT PENYALURAN --}}
            <section class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100"><h2 class="font-bold text-slate-800">Penyaluran</h2></div>
                <ul class="divide-y divide-slate-100 text-sm">
                    @forelse ($donasi->penyaluran as $p)
                        <li class="px-6 py-3 flex gap-3">
                            <div class="flex-1">
                                <p class="font-semibold text-slate-800">{{ $p->namaTujuan() }}</p>
                                <p class="text-slate-600">
                                    @if ($p->nominal){{ $rp($p->nominal) }}@endif
                                    @if ($p->logistik) {{ $p->nominal ? '·' : '' }} {{ $p->logistik->nama_item }} {{ number_format($p->jumlah, 0, ',', '.') }} {{ $p->logistik->satuan }}@endif
                                </p>
                                @if ($p->keterangan)<p class="text-slate-500">{{ $p->keterangan }}</p>@endif
                            </div>
                            @if ($p->foto)<a href="{{ asset('storage/' . $p->foto) }}" target="_blank"><img src="{{ asset('storage/' . $p->foto) }}" alt="Dokumentasi" class="h-12 w-12 rounded-lg object-cover"></a>@endif
                            <p class="text-xs text-slate-400">{{ $p->tanggal->translatedFormat('d M Y') }}</p>
                        </li>
                    @empty
                        <li class="px-6 py-6 text-center text-slate-400">Belum ada penyaluran.</li>
                    @endforelse
                </ul>
            </section>
        </div>

        {{-- KANAN: jejak --}}
        <div class="space-y-6">
            <section class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6">
                <h2 class="font-bold text-slate-800 mb-4">Jejak (dilihat donatur)</h2>
                <ol class="relative border-l-2 border-slate-200 ml-3 space-y-5">
                    @foreach ($donasi->jejak->reverse() as $j)
                        <li class="ml-5">
                            <span class="absolute -left-[13px] w-6 h-6 rounded-full flex items-center justify-center ring-4 ring-white {{ $warna[$j->warna] ?? $warna['slate'] }}"><i class="fas {{ $j->ikon }} text-[10px]"></i></span>
                            <p class="font-semibold text-sm text-slate-800">{{ $j->judul }}</p>
                            <p class="text-[11px] text-slate-400">{{ $j->created_at->translatedFormat('d M Y, H:i') }}</p>
                            @if ($j->keterangan)<p class="text-xs text-slate-600 mt-0.5">{{ $j->keterangan }}</p>@endif
                        </li>
                    @endforeach
                </ol>
            </section>

            @unless ($donasi->status === \App\Models\Donasi::DITOLAK)
                <section class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6">
                    <h2 class="font-bold text-slate-800 mb-3">Tambah kabar untuk donatur</h2>
                    <form method="POST" action="{{ route('admin.donasi.kabar', $donasi) }}" enctype="multipart/form-data" class="space-y-2">
                        @csrf
                        <input type="text" name="judul" maxlength="120" required placeholder="contoh: Barang sedang dikemas" class="{{ $input }}">
                        <textarea name="keterangan" rows="2" maxlength="1000" placeholder="Keterangan (opsional)" class="{{ $input }}"></textarea>
                        <input type="file" name="foto" accept="image/*" class="text-sm">
                        <button class="w-full py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-sm">Tambahkan</button>
                    </form>
                </section>
            @endunless
        </div>
    </div>
</div>
@endsection
