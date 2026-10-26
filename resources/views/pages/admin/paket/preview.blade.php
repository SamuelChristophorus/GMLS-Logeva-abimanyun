@extends('layouts.admin')

@section('title', 'Pratinjau Paket Data Posko')

@section('content')
@php
    $badge = [
        'tambah'   => ['Ditambahkan',   'bg-emerald-100 text-emerald-700 border-emerald-200', 'fa-plus',                 'text-emerald-600'],
        'perbarui' => ['Diperbarui',    'bg-blue-100 text-blue-700 border-blue-200',          'fa-pen',                  'text-blue-600'],
        'hapus'    => ['Dihapus',       'bg-amber-100 text-amber-800 border-amber-200',       'fa-trash-alt',            'text-amber-600'],
        'sama'     => ['Tidak berubah', 'bg-slate-100 text-slate-500 border-slate-200',       'fa-equals',               'text-slate-500'],
        'lewati'   => ['Dilewati',      'bg-slate-100 text-slate-500 border-slate-200',       'fa-forward',              'text-slate-500'],
        'error'    => ['Error',         'bg-rose-100 text-rose-700 border-rose-200',          'fa-exclamation-triangle', 'text-rose-600'],
    ];
    $bagian = [
        'pengungsi' => ['Pengungsi',     ['nama' => 'Nama', 'posko' => 'Posko', 'usia' => 'Usia', 'jenis_kelamin' => 'Jenis Kelamin']],
        'masuk'     => ['Barang Masuk',  ['nama_item' => 'Barang', 'posko' => 'Posko', 'jumlah' => 'Jumlah', 'satuan' => 'Satuan', 'tanggal' => 'Tanggal']],
        'keluar'    => ['Barang Keluar', ['nama_item' => 'Barang', 'posko' => 'Posko', 'jumlah' => 'Jumlah', 'satuan' => 'Satuan', 'tanggal' => 'Tanggal']],
    ];
    if ($hasil['ada_petugas']) {
        $bagian['petugas'] = ['Petugas Lapangan', ['nama' => 'Nama', 'peran' => 'Peran', 'posko' => 'Posko', 'kontak' => 'Kontak']];
    }
    $keterangan = [
        'petugas' => 'Nama & kontak admin (penanggung jawab) dan relawan di HP posko, supaya pusat tahu siapa yang bisa dihubungi. Ini bukan akun website. Daftar diganti hanya jika paket ini lebih baru dari paket sebelumnya dari HP yang sama.',
    ];
    $tampil = fn ($v) => ($v === null || $v === '') ? '-' : $v;
    $adaYangDisimpan = collect(['posko', 'pengungsi', 'masuk', 'keluar', 'petugas'])
        ->contains(fn ($b) => collect($hasil[$b])->whereIn('status', ['tambah', 'perbarui', 'hapus'])->isNotEmpty())
        || collect($hasil['posko'])->contains('sambung', true);
    $jumlahError = collect($hasil['ringkasan'])->sum(fn ($r) => $r['error'] ?? 0);
@endphp
<div class="relative z-10 container mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10 min-h-screen flex flex-col gap-8">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white/60 backdrop-blur-xl p-6 md:p-8 rounded-3xl border border-white/80 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-50 border border-blue-100/50 text-blue-600 text-xs font-bold tracking-wide uppercase mb-3">
                <i class="fas fa-file-archive opacity-70"></i> Import Paket Data Posko
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 tracking-tight">Pratinjau Paket</h1>
            <p class="text-slate-500 mt-1 font-medium text-sm">
                File: <span class="font-bold text-slate-700">{{ $namaFile }}</span>
                · Diekspor {{ $hasil['info']['dibuat_pada'] }} WIB oleh {{ $hasil['info']['diekspor_oleh'] ?: '-' }}
                @if (($hasil['info']['peran_perangkat'] ?? '') !== '')
                    · dari HP {{ $hasil['info']['peran_perangkat'] === 'anggota' ? 'Anggota (relawan)' : 'Induk (supervisor)' }}
                @endif
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <form method="POST" action="{{ route('admin.paket.batal') }}">
                @csrf
                <button class="inline-flex items-center gap-2 bg-white hover:bg-slate-50 text-slate-600 font-bold px-5 py-3 rounded-xl shadow-sm border border-slate-200">
                    <i class="fas fa-times text-sm"></i> Batal
                </button>
            </form>
            @if ($adaYangDisimpan)
                <button type="submit" form="form-konfirmasi"
                        class="inline-flex items-center gap-2 bg-gradient-to-tr from-emerald-600 to-green-500 hover:from-emerald-700 hover:to-green-600 text-white font-bold px-5 py-3 rounded-xl shadow-lg shadow-emerald-500/30">
                    <i class="fas fa-check text-sm"></i> Konfirmasi Gabung
                </button>
            @endif
        </div>
    </div>

    <form id="form-konfirmasi" method="POST" action="{{ route('admin.paket.confirm') }}"
          onsubmit="return confirm('Gabungkan paket ini ke data pusat?');">
        @csrf
    </form>

    @if ($error)
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            <i class="fas fa-exclamation-circle mr-1"></i> {{ $error }}
        </div>
    @endif

    <!-- Ringkasan -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach (['posko' => 'Posko', 'pengungsi' => 'Pengungsi', 'masuk' => 'Barang Masuk', 'keluar' => 'Barang Keluar'] as $k => $judul)
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ $judul }}</p>
                <p class="text-3xl font-black mt-1 text-slate-800">{{ count($hasil[$k]) }}</p>
                <div class="flex flex-wrap gap-1.5 mt-2">
                    @foreach ($hasil['ringkasan'][$k] as $status => $n)
                        <span class="px-2 py-0.5 rounded-md text-[11px] font-bold border {{ $badge[$status][1] }}">{{ $n }} {{ strtolower($badge[$status][0]) }}</span>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    @foreach ($hasil['peringatan'] as $p)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            <i class="fas fa-info-circle mr-1"></i> {{ $p }}
        </div>
    @endforeach

    @if ($hasil['barang_baru'])
        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
            <i class="fas fa-box-open mr-1"></i>
            Barang berikut belum ada di Master Logistik dan akan ditambahkan (kebutuhan harian bisa diatur kemudian):
            <b>{{ collect($hasil['barang_baru'])->map(fn ($b) => "{$b['nama_item']} ({$b['kategori']}, {$b['satuan']})")->implode(', ') }}</b>
        </div>
    @endif

    @if ($jumlahError > 0)
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            <i class="fas fa-info-circle mr-1"></i>
            Baris berstatus <b>Error</b> akan dilewati; baris lain tetap digabung. Perbaiki datanya di aplikasi lalu ekspor ulang paket jika ingin ikut disimpan.
        </div>
    @endif

    @if (! $adaYangDisimpan)
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
            <i class="fas fa-info-circle mr-1"></i> Semua data di paket ini sudah ada di pusat. Tidak ada yang perlu digabung.
        </div>
    @endif

    <!-- Posko -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden">
        <div class="px-6 md:px-8 pt-6 md:pt-8">
            <h2 class="text-lg font-extrabold text-slate-800">Posko</h2>
            @if ($hasil['posko_baru'])
                <p class="text-sm text-slate-500 mt-1">Pilih relawan website yang menjadi penanggung jawab setiap posko baru.</p>
            @endif
        </div>
        <div class="p-6 md:p-8 overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50">
                        @foreach (['Baris', 'Status', 'Nama Posko', 'Desa', 'Kapasitas', 'Status Operasional', 'Relawan'] as $h)
                            <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100 whitespace-nowrap">{{ $h }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100/80">
                    @foreach ($hasil['posko'] as $b)
                        @php [$label, $warna, $ikon] = $badge[$b['status']]; @endphp
                        <tr class="{{ $b['status'] === 'error' ? 'bg-rose-50/40' : '' }} align-top">
                            <td class="py-3 px-4 text-sm font-semibold text-slate-400">{{ $b['row'] }}</td>
                            <td class="py-3 px-4 min-w-[180px]">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold border {{ $warna }}">
                                    <i class="fas {{ $ikon }} text-[10px]"></i> {{ $label }}
                                </span>
                                @if ($b['alasan'])<p class="text-xs text-slate-500 mt-1">{{ $b['alasan'] }}</p>@endif
                                @foreach ($b['error'] as $e)<p class="text-xs text-rose-600 mt-1">{{ $e }}</p>@endforeach
                                @foreach ($b['lama'] as $k => $v)
                                    <p class="text-xs text-slate-400 mt-1">{{ str_replace('_', ' ', $k) }} sebelumnya: <span class="line-through">{{ $tampil($v) }}</span></p>
                                @endforeach
                            </td>
                            <td class="py-3 px-4 text-sm font-bold text-slate-800">{{ $tampil($b['data']['nama_lokasi']) }}</td>
                            <td class="py-3 px-4 text-sm text-slate-600">{{ $tampil($b['data']['nama_desa']) }}</td>
                            <td class="py-3 px-4 text-sm text-slate-600 whitespace-nowrap">{{ $b['data']['kapasitas'] }} orang</td>
                            <td class="py-3 px-4 text-sm text-slate-600">{{ ucfirst($b['data']['status_operasional']) }}</td>
                            <td class="py-3 px-4 text-sm text-slate-600 min-w-[220px]">
                                @if ($b['status'] === 'tambah')
                                    <select name="relawan[{{ $b['kode'] }}]" form="form-konfirmasi" required
                                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-blue-500">
                                        <option value="">Pilih relawan…</option>
                                        @foreach ($relawans as $r)
                                            <option value="{{ $r->id }}" @selected(($relawanDipilih[$b['kode']] ?? null) == $r->id)>
                                                {{ $r->user->name }} ({{ $r->user->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @if ($b['data']['relawan'])
                                        <p class="text-xs text-slate-400 mt-1">Relawan di aplikasi: {{ $b['data']['relawan'] }}</p>
                                    @endif
                                @else
                                    {{ $tampil($b['data']['relawan']) }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pengungsi, barang masuk, barang keluar -->
    @foreach ($bagian as $kunci => [$judul, $kolom])
        <div class="bg-white rounded-3xl border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden">
            <div class="px-6 md:px-8 pt-6 md:pt-8">
                <h2 class="text-lg font-extrabold text-slate-800">{{ $judul }} <span class="text-slate-400 font-bold">({{ count($hasil[$kunci]) }})</span></h2>
                @isset($keterangan[$kunci])
                    <p class="text-sm text-slate-500 mt-1">{{ $keterangan[$kunci] }}</p>
                @endisset
            </div>
            <div class="p-6 md:p-8 overflow-x-auto">
                @if (! $hasil[$kunci])
                    <p class="text-sm text-slate-500">Tidak ada data.</p>
                @else
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50">
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">Baris</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100 min-w-[180px]">Status</th>
                                @foreach ($kolom as $label)
                                    <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100 whitespace-nowrap">{{ $label }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100/80">
                            @foreach ($hasil[$kunci] as $b)
                                @php [$label, $warna, $ikon] = $badge[$b['status']]; @endphp
                                <tr class="{{ $b['status'] === 'error' ? 'bg-rose-50/40' : '' }} align-top">
                                    <td class="py-3 px-4 text-sm font-semibold text-slate-400">{{ $b['row'] }}</td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold border {{ $warna }}">
                                            <i class="fas {{ $ikon }} text-[10px]"></i> {{ $label }}
                                        </span>
                                        @if ($b['alasan'])<p class="text-xs text-slate-500 mt-1">{{ $b['alasan'] }}</p>@endif
                                        @foreach ($b['error'] as $e)<p class="text-xs text-rose-600 mt-1">{{ $e }}</p>@endforeach
                                    </td>
                                    @foreach ($kolom as $k => $_)
                                        <td class="py-3 px-4 text-sm {{ $loop->first ? 'font-bold text-slate-800' : 'text-slate-600' }}">{{ $tampil($b['data'][$k] ?? null) }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    @endforeach
</div>
@endsection
