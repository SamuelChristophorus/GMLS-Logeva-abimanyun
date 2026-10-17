{{--
    Panel status operasional posko.
    Pemakaian di halaman detail lokasi (admin & publik):
        @include('includes.operasional-lokasi', ['lokasi' => $lokasi, 'operasional' => $operasional ?? null])

    - Selalu menampilkan badge status + tombol (hanya untuk admin / relawan penanggung jawab).
    - Jika posko BEROPERASI, menampilkan dashboard pengungsi & stok logistik.
--}}
@php
    $badge    = $lokasi->labelOperasional();
    $bisaKelola = $lokasi->bisaDikelolaOleh(auth()->user());
    $isAdmin    = auth()->user()?->hasRole('admin') ?? false;
    $statusPersetujuan = optional($lokasi->latestStatusView)->status ?? 'pending';
    $warnaBadge = [
        'green' => 'bg-green-100 text-green-700 border-green-200',
        'amber' => 'bg-amber-100 text-amber-700 border-amber-200',
        'slate' => 'bg-slate-100 text-slate-600 border-slate-200',
    ][$badge['warna']];
    $warnaStok = [
        'merah'  => ['bg-red-50 text-red-700', 'bg-red-500', 'Kritis'],
        'kuning' => ['bg-amber-50 text-amber-700', 'bg-amber-500', 'Menipis'],
        'hijau'  => ['bg-green-50 text-green-700', 'bg-green-500', 'Aman'],
        'netral' => ['bg-slate-50 text-slate-500', 'bg-slate-300', '-'],
    ];
@endphp

{{-- ================= HEADER STATUS OPERASIONAL ================= --}}
<div class="bg-white rounded-2xl border border-slate-200 p-5 mb-6 flex flex-col md:flex-row md:items-center gap-4">
    <div class="flex-1">
        <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Status Operasional Posko</p>
        <div class="flex flex-wrap items-center gap-3">
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full border text-sm font-bold {{ $warnaBadge }}">
                @if ($lokasi->isBeroperasi())
                    <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                @endif
                {{ $badge['label'] }}
            </span>

            @if ($lokasi->isBeroperasi() && $lokasi->mulai_beroperasi_at)
                <span class="text-sm text-slate-500">
                    Hari ke-<b>{{ $lokasi->hariOperasi() }}</b> · sejak {{ $lokasi->mulai_beroperasi_at->translatedFormat('d M Y, H:i') }}
                </span>
            @elseif ($lokasi->status_operasional === 'selesai' && $lokasi->selesai_beroperasi_at)
                <span class="text-sm text-slate-500">Ditutup {{ $lokasi->selesai_beroperasi_at->translatedFormat('d M Y, H:i') }}</span>
            @elseif ($statusPersetujuan === 'rejected')
                <span class="text-sm text-rose-600">Laporan lokasi ini ditolak admin sehingga tidak dapat beroperasi.</span>
            @elseif (! $lokasi->isApproved())
                <span class="text-sm text-slate-500">Lokasi masih <b>menunggu persetujuan admin</b> sebelum dapat beroperasi.</span>
            @endif
        </div>
    </div>

    @if ($bisaKelola)
        <div class="flex flex-wrap gap-2">
            {{-- Belum disetujui: admin bisa langsung approve / tolak, relawan melihat tombol nonaktif --}}
            @if (! $lokasi->isApproved())
                @if ($isAdmin)
                    @if ($statusPersetujuan !== 'rejected')
                        <form method="POST" action="{{ route('admin.lokasi.verifikasi', $lokasi->id) }}"
                              onsubmit="return confirm('Tolak laporan posko ini?')">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="rejected">
                            <button class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-700 text-sm font-semibold">
                                <i class="fas fa-xmark"></i> Tolak
                            </button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('admin.lokasi.verifikasi', $lokasi->id) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="approved">
                        <button class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold">
                            <i class="fas fa-check"></i> Setujui Lokasi
                        </button>
                    </form>
                @endif
                <button type="button" disabled title="Lokasi harus disetujui admin terlebih dahulu"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-100 text-slate-400 text-sm font-semibold cursor-not-allowed border border-slate-200">
                    <i class="fas fa-play"></i> Mulai Operasi
                </button>
            @endif

            @if (! $lokasi->isBeroperasi() && $lokasi->isApproved())
                <form method="POST" action="{{ route('lokasi.operasional', $lokasi->id) }}"
                      onsubmit="return confirm('Mulai operasi posko ini? Tampilan akan beralih ke data pengungsi & stok aktual.')">
                    @csrf @method('PATCH')
                    <input type="hidden" name="aksi" value="mulai">
                    <button class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-green-600 hover:bg-green-700 text-white text-sm font-semibold">
                        <i class="fas fa-play"></i> Mulai Operasi
                    </button>
                </form>
            @endif

            @if ($lokasi->isBeroperasi())
                <form method="POST" action="{{ route('lokasi.operasional', $lokasi->id) }}"
                      onsubmit="return confirm('Tutup operasi posko ini?')">
                    @csrf @method('PATCH')
                    <input type="hidden" name="aksi" value="tutup">
                    <button class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-sm font-semibold">
                        <i class="fas fa-stop"></i> Tutup Operasi
                    </button>
                </form>
            @endif

            @if ($lokasi->status_operasional === 'selesai')
                <form method="POST" action="{{ route('lokasi.operasional', $lokasi->id) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="aksi" value="siaga">
                    <button class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-semibold">
                        <i class="fas fa-rotate-left"></i> Kembalikan ke Siaga
                    </button>
                </form>
            @endif
        </div>
    @endif
</div>

{{-- ================= DASHBOARD SAAT BEROPERASI ================= --}}
@if ($lokasi->isBeroperasi() && $operasional)
    @php($p = $operasional['pengungsi'])

    {{-- Kartu ringkasan --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <p class="text-sm text-slate-500">Pengungsi Saat Ini</p>
            <p class="text-3xl font-black text-slate-900 mt-1">{{ number_format($p['total'], 0, ',', '.') }}</p>
            <p class="text-xs text-slate-400 mt-1">dari kapasitas {{ number_format($p['kapasitas'], 0, ',', '.') }} orang</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <p class="text-sm text-slate-500">Keterisian Posko</p>
            <p class="text-3xl font-black mt-1 {{ $p['persen_isi'] >= 90 ? 'text-red-600' : 'text-slate-900' }}">{{ $p['persen_isi'] }}%</p>
            <div class="h-2 bg-slate-100 rounded-full mt-2 overflow-hidden">
                <div class="h-full rounded-full {{ $p['persen_isi'] >= 90 ? 'bg-red-500' : ($p['persen_isi'] >= 70 ? 'bg-amber-500' : 'bg-green-500') }}"
                     style="width: {{ $p['persen_isi'] }}%"></div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <p class="text-sm text-slate-500">Kelompok Rentan</p>
            <p class="text-3xl font-black text-purple-600 mt-1">{{ $p['total_rentan'] }}</p>
            <p class="text-xs text-slate-400 mt-1">perlu perhatian khusus</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <p class="text-sm text-slate-500">Status Stok Logistik</p>
            <div class="flex items-center gap-3 mt-2 text-sm font-semibold">
                <span class="text-red-600"><i class="fas fa-circle text-[8px]"></i> {{ $operasional['stok_status']['merah'] }} kritis</span>
                <span class="text-amber-600"><i class="fas fa-circle text-[8px]"></i> {{ $operasional['stok_status']['kuning'] }} menipis</span>
            </div>
            <p class="text-xs text-slate-400 mt-1">{{ $operasional['stok_status']['hijau'] }} item aman</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

        {{-- Komposisi pengungsi --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <h3 class="font-bold text-slate-900 mb-4 flex items-center gap-2"><i class="fas fa-users text-blue-500"></i> Komposisi Pengungsi</h3>

            <div class="flex gap-3 mb-5">
                <div class="flex-1 rounded-xl bg-blue-50 p-3 text-center">
                    <p class="text-xs text-blue-600">Laki-laki</p>
                    <p class="text-xl font-black text-blue-700">{{ $p['laki_laki'] }}</p>
                </div>
                <div class="flex-1 rounded-xl bg-pink-50 p-3 text-center">
                    <p class="text-xs text-pink-600">Perempuan</p>
                    <p class="text-xl font-black text-pink-700">{{ $p['perempuan'] }}</p>
                </div>
            </div>

            <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Kelompok Usia</p>
            <ul class="space-y-2 mb-5">
                @foreach ($p['usia'] as $label => $jml)
                    <li class="flex items-center gap-3 text-sm">
                        <span class="w-28 text-slate-600">{{ $label }}</span>
                        <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-500 rounded-full" style="width: {{ $p['total'] ? round($jml / $p['total'] * 100) : 0 }}%"></div>
                        </div>
                        <span class="w-8 text-right font-semibold text-slate-800">{{ $jml }}</span>
                    </li>
                @endforeach
            </ul>

            <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Kelompok Rentan</p>
            @forelse ($p['rentan'] as $label => $jml)
                <div class="flex justify-between text-sm py-1 border-b border-slate-100 last:border-0">
                    <span class="text-slate-600">{{ $label }}</span>
                    <span class="font-semibold text-purple-700">{{ $jml }}</span>
                </div>
            @empty
                <p class="text-sm text-slate-400">Tidak ada.</p>
            @endforelse
        </div>

        {{-- Kebutuhan SPHERE aktual --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5 lg:col-span-2">
            <h3 class="font-bold text-slate-900 mb-1 flex items-center gap-2"><i class="fas fa-calculator text-red-500"></i> Kebutuhan Harian Aktual (SPHERE)</h3>
            <p class="text-xs text-slate-400 mb-4">Dihitung dari {{ $p['total'] }} pengungsi yang tercatat saat ini, bukan dari kapasitas.</p>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="rounded-xl bg-sky-50 p-4">
                    <i class="fas fa-droplet text-sky-500"></i>
                    <p class="text-xs text-sky-700 mt-2">Total Air</p>
                    <p class="text-lg font-black text-sky-800">{{ number_format($operasional['sphere']['air_total'], 0, ',', '.') }} L</p>
                </div>
                <div class="rounded-xl bg-cyan-50 p-4">
                    <i class="fas fa-glass-water text-cyan-500"></i>
                    <p class="text-xs text-cyan-700 mt-2">Air Minum</p>
                    <p class="text-lg font-black text-cyan-800">{{ number_format($operasional['sphere']['air_minum'], 0, ',', '.') }} L</p>
                </div>
                <div class="rounded-xl bg-orange-50 p-4">
                    <i class="fas fa-fire text-orange-500"></i>
                    <p class="text-xs text-orange-700 mt-2">Kalori</p>
                    <p class="text-lg font-black text-orange-800">{{ number_format($operasional['sphere']['kalori'], 0, ',', '.') }} kkal</p>
                </div>
                <div class="rounded-xl bg-emerald-50 p-4">
                    <i class="fas fa-restroom text-emerald-500"></i>
                    <p class="text-xs text-emerald-700 mt-2">Toilet Minimal</p>
                    <p class="text-lg font-black text-emerald-800">{{ $operasional['sphere']['toilet'] }} unit</p>
                </div>
            </div>

            <h4 class="font-bold text-slate-900 mt-6 mb-3 text-sm">Bantuan Masuk Terakhir</h4>
            @forelse ($operasional['stok_masuk_terbaru'] as $m)
                <div class="flex items-center justify-between text-sm py-2 border-b border-slate-100 last:border-0">
                    <div>
                        <p class="font-semibold text-slate-800">+{{ $m->jumlah_masuk }} {{ $m->satuan }} {{ $m->nama_item }}</p>
                        <p class="text-xs text-slate-400">{{ $m->sumber_bantuan }}</p>
                    </div>
                    <span class="text-xs text-slate-500">{{ \Carbon\Carbon::parse($m->tanggal_masuk)->translatedFormat('d M Y') }}</span>
                </div>
            @empty
                <p class="text-sm text-slate-400">Belum ada bantuan masuk.</p>
            @endforelse
        </div>
    </div>

    {{-- Tabel stok logistik --}}
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="font-bold text-slate-900 flex items-center gap-2"><i class="fas fa-boxes-stacked text-green-600"></i> Stok Logistik di Posko Saat Ini</h3>
            <p class="text-xs text-slate-400">Status: <b class="text-red-600">Kritis</b> &lt; {{ \App\Services\OperasionalLokasiService::BATAS_MERAH }} hari, <b class="text-amber-600">Menipis</b> &lt; {{ \App\Services\OperasionalLokasiService::BATAS_KUNING }} hari.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                    <tr>
                        <th class="text-left px-5 py-3">Item</th>
                        <th class="text-left px-5 py-3">Kategori</th>
                        <th class="text-right px-5 py-3">Stok</th>
                        <th class="text-right px-5 py-3">Kebutuhan/Hari</th>
                        <th class="text-right px-5 py-3">Cukup Untuk</th>
                        <th class="text-center px-5 py-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($operasional['stok'] as $s)
                        @php([$bgBaris, $dot, $teks] = $warnaStok[$s->status])
                        <tr class="border-t border-slate-100">
                            <td class="px-5 py-3 font-semibold text-slate-800">{{ $s->nama_item }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ $s->nama_kategori ?? '-' }}</td>
                            <td class="px-5 py-3 text-right">{{ number_format($s->jumlah_stok, 0, ',', '.') }} {{ $s->satuan }}</td>
                            <td class="px-5 py-3 text-right text-slate-500">{{ $s->kebutuhan_hari_ini ? number_format($s->kebutuhan_hari_ini, 1, ',', '.') . ' ' . $s->satuan : '-' }}</td>
                            <td class="px-5 py-3 text-right font-semibold">{{ $s->cukup_hari !== null ? number_format($s->cukup_hari, 1, ',', '.') . ' hari' : '-' }}</td>
                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold {{ $bgBaris }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $dot }}"></span> {{ $teks }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-slate-400">Belum ada stok logistik di posko ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif