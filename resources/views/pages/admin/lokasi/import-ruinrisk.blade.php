@extends('layouts.admin')

@section('title', 'Import TEA RUinRISK')

@section('content')
@php
    $baris   = collect($pratinjau['baris'] ?? []);
    $jumlah  = $baris->countBy('status');
    $angka   = fn ($v, $d = 0) => $v === null || $v === '' ? '-' : number_format((float) $v, $d, ',', '.');
    $labelUbah = ['nama_lokasi' => 'Nama', 'kapasitas_pengungsi' => 'Kapasitas', 'latitude' => 'Latitude', 'longitude' => 'Longitude', 'ketinggian_mdpl' => 'Ketinggian'];
    $statusUi = [
        'tambah'   => ['Posko baru',      'bg-blue-100 text-blue-700'],
        'perbarui' => ['Diperbarui',      'bg-amber-100 text-amber-800'],
        'sama'     => ['Tidak berubah',   'bg-slate-100 text-slate-600'],
        'lewati'   => ['Dilewati',        'bg-slate-100 text-slate-500'],
        'error'    => ['Error',           'bg-rose-100 text-rose-700'],
    ];
    $pilih = 'w-full rounded-lg border border-slate-200 px-2 py-1.5 text-xs focus:ring-2 focus:ring-blue-500 outline-none';
    $bisaAmbil = fn ($b) => in_array($b['status'], ['tambah', 'perbarui'], true);
    $adaIsianLama = session()->hasOldInput();
    $ambilAwal = $baris->filter($bisaAmbil)->map(fn ($b, $i) => $adaIsianLama
        ? (bool) old("pilihan.$i.ambil")
        : ($b['status'] === 'perbarui' || empty($b['peringatan'])));
@endphp

<div class="relative z-10 container mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10 flex flex-col gap-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white/60 backdrop-blur-xl p-6 md:p-8 rounded-3xl border border-white/80 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-50 border border-blue-100/50 text-blue-600 text-xs font-bold tracking-wide uppercase mb-3">
                <i class="fas fa-satellite-dish opacity-70"></i> Sumber luar
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 tracking-tight">Import TEA RUinRISK</h1>
            <p class="text-slate-500 mt-1 font-medium text-sm max-w-3xl">
                Tambahkan Tempat Evakuasi Akhir dari file <b>Data TEA</b> aplikasi RUinRISK. Posko baru langsung <b>disetujui</b>.
                File yang sama boleh di-import ulang: posko dengan ID RUinRISK yang sama akan diperbarui, tidak jadi dobel.
            </p>
        </div>
        <a href="{{ route('admin.lokasi.index') }}" class="shrink-0 inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white border border-slate-200 font-bold text-slate-700 hover:bg-slate-50">
            <i class="fas fa-arrow-left text-xs"></i> Lokasi Pengungsian
        </a>
    </div>

    @if ($errors->any())
        <div class="px-4 py-3 rounded-xl bg-rose-50 text-rose-700 border border-rose-200 text-sm">
            @foreach ($errors->all() as $e)<p><i class="fas fa-exclamation-circle mr-1"></i>{{ $e }}</p>@endforeach
        </div>
    @endif

    @if (! $pratinjau)
        {{-- ===== UPLOAD ===== --}}
        <div class="grid lg:grid-cols-5 gap-6">
            <form method="POST" action="{{ route('admin.lokasi.ruinrisk.preview') }}" enctype="multipart/form-data"
                  class="lg:col-span-3 bg-white rounded-3xl border border-slate-100 shadow-sm p-6 md:p-8 space-y-4">
                @csrf
                <h2 class="text-lg font-extrabold text-slate-800">Upload file</h2>
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                       class="block w-full text-sm text-slate-600 border border-slate-200 rounded-xl cursor-pointer file:mr-4 file:py-2.5 file:px-4 file:border-0 file:bg-blue-50 file:text-blue-700 file:font-bold hover:file:bg-blue-100">
                <p class="text-xs text-slate-400">Contoh: <b>data_tea_ruinrisk_20261106.xlsx</b> · .xlsx / .csv · maks. 5 MB</p>
                <div class="flex justify-end">
                    <button class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold shadow-lg shadow-blue-500/30">
                        <i class="fas fa-eye text-sm"></i> Lihat Pratinjau
                    </button>
                </div>
            </form>

            <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-100 shadow-sm p-6 md:p-8 text-sm text-slate-600">
                <h2 class="text-lg font-extrabold text-slate-800 mb-3">Aturan import</h2>
                <ul class="space-y-2 list-disc list-inside">
                    <li>Yang diambil: nama, kapasitas, koordinat, dan <b>ketinggian (m dpl)</b>. Luas dihitung dari kapasitas × 3,5 m².</li>
                    <li><b>Desa</b> dan <b>relawan penanggung jawab</b> dipilih di pratinjau, karena tidak ada di data RUinRISK.</li>
                    <li>Hanya baris berstatus <b>approved</b> dengan asal data <b>RUinRISK</b>. Data yang asalnya dari LOGEVA dilewati.</li>
                    <li>Diberi peringatan jika ada posko lain kurang dari <b>{{ $jarak }} m</b> (kemungkinan tempat yang sama) atau titik di luar {{ config('logeva.wilayah_kerja.nama') }}.</li>
                </ul>
            </div>
        </div>
    @else
        {{-- ===== PRATINJAU ===== --}}
        <form method="POST" action="{{ route('admin.lokasi.ruinrisk.confirm') }}" class="flex flex-col gap-6"
              x-data="{
                  desaSemua: '', relawanSemua: '',
                  ambil: @js((object) $ambilAwal->all()),
                  get jumlahAmbil() { return Object.values(this.ambil).filter(Boolean).length },
                  get semua() { return this.jumlahAmbil === Object.keys(this.ambil).length },
                  pilihSemua(nilai) { Object.keys(this.ambil).forEach(k => this.ambil[k] = nilai) },
              }">
            @csrf

            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 flex flex-col lg:flex-row lg:items-end gap-4 justify-between">
                <div>
                    <p class="text-sm text-slate-500">File: <b class="text-slate-700">{{ $pratinjau['namaFile'] }}</b></p>
                    <p class="text-sm text-slate-700 mt-1">
                        <b x-text="jumlahAmbil"></b> dari {{ $ambilAwal->count() }} baris dicentang untuk diambil.
                        <span class="text-slate-500">Baris yang tidak dicentang tidak diambil. Baris dengan peringatan tidak dicentang otomatis.</span>
                    </p>
                    <div class="flex flex-wrap gap-2 mt-2">
                        @foreach ($statusUi as $kunci => [$label, $kelas])
                            @if ($jumlah[$kunci] ?? 0)
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $kelas }}">{{ $label }}: {{ $jumlah[$kunci] }}</span>
                            @endif
                        @endforeach
                    </div>
                </div>

                @if ($jumlah['tambah'] ?? 0)
                    <div class="grid sm:grid-cols-2 gap-3 lg:w-1/2">
                        <div>
                            <label for="desa-semua" class="block mb-1 text-xs font-bold text-slate-600">Desa untuk semua posko baru</label>
                            <select id="desa-semua" x-model="desaSemua" @change="$root.querySelectorAll('[data-pilih=desa]').forEach(s => s.value = desaSemua)" class="{{ $pilih }}">
                                <option value="">-- pilih --</option>
                                @foreach ($desas as $d)<option value="{{ $d->id }}">{{ $d->nama_desa }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label for="relawan-semua" class="block mb-1 text-xs font-bold text-slate-600">Relawan untuk semua posko baru</label>
                            <select id="relawan-semua" x-model="relawanSemua" @change="$root.querySelectorAll('[data-pilih=relawan]').forEach(s => s.value = relawanSemua)" class="{{ $pilih }}">
                                <option value="">-- pilih --</option>
                                @foreach ($relawans as $r)<option value="{{ $r->id }}">{{ $r->user->name }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                @endif
            </div>

            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-xs font-bold text-slate-500 uppercase">
                            <tr>
                                <th class="px-4 py-3 w-16 text-center">
                                    @if ($ambilAwal->isNotEmpty())
                                        <label class="inline-flex flex-col items-center gap-1 cursor-pointer">
                                            <input type="checkbox" :checked="semua" @change="pilihSemua($event.target.checked)"
                                                   class="w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" aria-label="Centang semua baris">
                                            <span>Ambil</span>
                                        </label>
                                    @else
                                        Ambil
                                    @endif
                                </th>
                                <th class="text-left px-4 py-3">Baris</th>
                                <th class="text-left px-4 py-3">Data RUinRISK</th>
                                <th class="text-left px-4 py-3">Status &amp; peringatan</th>
                                <th class="text-left px-4 py-3 min-w-[260px]">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($baris as $i => $b)
                                @php [$label, $kelas] = $statusUi[$b['status']]; $d = $b['data']; @endphp
                                <tr class="{{ $b['status'] === 'error' ? 'bg-rose-50/40' : '' }} align-top transition"
                                    @if ($bisaAmbil($b)) :class="ambil[{{ $i }}] ? 'bg-emerald-50/40' : 'opacity-60'" @endif>
                                    <td class="px-4 py-3 text-center">
                                        @if ($bisaAmbil($b))
                                            <input type="checkbox" name="pilihan[{{ $i }}][ambil]" value="1" x-model="ambil[{{ $i }}]"
                                                   class="w-5 h-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer"
                                                   aria-label="Ambil baris {{ $b['row'] }} ({{ $b['data']['nama_lokasi'] }})">
                                        @else
                                            <span class="text-slate-300" title="Baris ini tidak bisa diambil">–</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-400">{{ $b['row'] }}</td>
                                    <td class="px-4 py-3">
                                        <p class="font-bold text-slate-800">{{ $d['nama_lokasi'] ?: '-' }}</p>
                                        <p class="text-xs text-slate-500">ID {{ $b['id_sumber'] ?: '-' }} · kapasitas {{ is_numeric($d['kapasitas']) ? $angka($d['kapasitas']) : '-' }} orang
                                            · ketinggian {{ $d['ketinggian'] === null ? '-' : $angka($d['ketinggian'], 1) . ' m dpl' }}</p>
                                        <p class="text-xs text-slate-400">{{ $d['latitude'] ?? '-' }}, {{ $d['longitude'] ?? '-' }}</p>
                                    </td>
                                    <td class="px-4 py-3 space-y-1">
                                        <span class="inline-block px-2 py-0.5 rounded-full text-[11px] font-bold {{ $kelas }}">{{ $label }}</span>
                                        @if ($b['alasan'])<p class="text-xs text-slate-500">{{ $b['alasan'] }}</p>@endif
                                        @foreach ($b['error'] as $e)<p class="text-xs text-rose-600"><i class="fas fa-times-circle mr-1"></i>{{ $e }}</p>@endforeach
                                        @foreach ($b['ubah'] as $k => [$lama, $baru])
                                            <p class="text-xs text-amber-700">{{ $labelUbah[$k] ?? $k }}: <s class="text-slate-400">{{ $lama ?? '-' }}</s> → <b>{{ $baru ?? '-' }}</b></p>
                                        @endforeach
                                        @foreach ($b['peringatan'] as $p)<p class="text-xs text-amber-700"><i class="fas fa-exclamation-triangle mr-1"></i>{{ $p }}</p>@endforeach
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($b['status'] === 'tambah')
                                            <div class="space-y-2" x-show="ambil[{{ $i }}]">
                                                <p class="text-xs font-semibold text-emerald-700">Ditambahkan sebagai posko baru di:</p>
                                                <select name="pilihan[{{ $i }}][desa_id]" data-pilih="desa" class="{{ $pilih }}" aria-label="Desa baris {{ $b['row'] }}">
                                                    <option value="">-- Desa --</option>
                                                    @foreach ($desas as $ds)<option value="{{ $ds->id }}" @selected(old("pilihan.$i.desa_id") == $ds->id)>{{ $ds->nama_desa }}</option>@endforeach
                                                </select>
                                                <select name="pilihan[{{ $i }}][relawan_id]" data-pilih="relawan" class="{{ $pilih }}" aria-label="Relawan baris {{ $b['row'] }}">
                                                    <option value="">-- Relawan penanggung jawab --</option>
                                                    @foreach ($relawans as $r)<option value="{{ $r->id }}" @selected(old("pilihan.$i.relawan_id") == $r->id)>{{ $r->user->name }}</option>@endforeach
                                                </select>
                                            </div>
                                            <p class="text-xs text-slate-400" x-show="! ambil[{{ $i }}]">Tidak diambil</p>
                                        @elseif ($b['status'] === 'perbarui')
                                            <p class="text-xs text-slate-600" x-show="ambil[{{ $i }}]">Posko yang sudah ada akan diperbarui. Desa &amp; relawan tidak berubah.</p>
                                            <p class="text-xs text-slate-400" x-show="! ambil[{{ $i }}]">Tidak diambil, posko tetap seperti sekarang</p>
                                        @else
                                            <p class="text-xs text-slate-400">Tidak ada tindakan</p>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex flex-wrap justify-end gap-3">
                <button type="submit" form="batal-ruinrisk" class="px-5 py-3 rounded-xl border border-slate-200 bg-white font-bold text-slate-600 hover:bg-slate-50">Batal</button>
                @if (($jumlah['tambah'] ?? 0) + ($jumlah['perbarui'] ?? 0) > 0)
                    <button type="submit" :disabled="jumlahAmbil === 0" :class="jumlahAmbil === 0 ? 'opacity-50 cursor-not-allowed' : ''"
                            class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold shadow-lg shadow-emerald-500/30">
                        <i class="fas fa-check"></i> Ambil <span x-text="jumlahAmbil"></span> baris yang dicentang
                    </button>
                @endif
            </div>
        </form>
        <form id="batal-ruinrisk" method="POST" action="{{ route('admin.lokasi.ruinrisk.batal') }}">@csrf</form>
    @endif
</div>
@endsection
