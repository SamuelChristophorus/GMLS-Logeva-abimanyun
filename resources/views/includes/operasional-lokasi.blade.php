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
    // Permintaan mulai/tutup dari relawan yang menunggu keputusan admin
    $permintaan = $bisaKelola ? $lokasi->permintaanMenunggu()->with('peminta')->first() : null;
    $warnaBadge = [
        'green' => 'bg-green-100 text-green-700 border-green-200',
        'amber' => 'bg-amber-100 text-amber-700 border-amber-200',
        'slate' => 'bg-slate-100 text-slate-600 border-slate-200',
    ][$badge['warna']];
    $warnaStok = [
        'merah'  => ['bg-red-50 text-red-700', 'bg-red-500', 'Kritis'],
        'kuning' => ['bg-amber-50 text-amber-700', 'bg-amber-500', 'Menipis'],
        'hijau'  => ['bg-green-50 text-green-700', 'bg-green-500', 'Aman'],
        'netral' => ['bg-slate-50 text-slate-500', 'bg-slate-300', 'Belum dihitung'],
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

            @elseif ($isAdmin)
                {{-- ADMIN: ubah status operasional langsung --}}
                @if (! $lokasi->isBeroperasi())
                    <form method="POST" action="{{ route('lokasi.operasional', $lokasi->id) }}"
                          onsubmit="return confirm('Mulai operasi posko ini? Relawan penanggung jawab akan diberi notifikasi.')">
                        @csrf @method('PATCH')
                        <input type="hidden" name="aksi" value="mulai">
                        <button class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-green-600 hover:bg-green-700 text-white text-sm font-semibold">
                            <i class="fas fa-play"></i> {{ $lokasi->status_operasional === 'selesai' ? 'Mulai Operasi Lagi' : 'Mulai Operasi' }}
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('lokasi.operasional', $lokasi->id) }}"
                          onsubmit="return confirm('Tutup operasi posko ini? Relawan penanggung jawab akan diberi notifikasi.')">
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

            @elseif ($permintaan)
                {{-- RELAWAN: permintaan sudah dikirim --}}
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 text-sm font-semibold">
                    <i class="fas fa-hourglass-half"></i> Permintaan {{ $permintaan->labelAksi() }} menunggu keputusan admin
                </span>

            @else
                {{-- RELAWAN: ajukan permintaan ke admin --}}
                @php $aksiAju = $lokasi->isBeroperasi() ? 'tutup' : 'mulai'; @endphp
                <form method="POST" action="{{ route('lokasi.permintaan-operasional', $lokasi->id) }}"
                      x-data="{ alasan: false }" class="flex flex-wrap items-center gap-2"
                      onsubmit="return confirm('{{ $aksiAju === 'mulai' ? 'Ajukan permintaan MULAI operasi ke admin?' : 'Ajukan permintaan PENUTUPAN operasi ke admin?' }}')">
                    @csrf
                    <input type="hidden" name="aksi" value="{{ $aksiAju }}">
                    <input x-show="alasan" x-cloak type="text" name="alasan" maxlength="500"
                           placeholder="{{ $aksiAju === 'mulai' ? 'Alasan (contoh: warga mulai mengungsi)' : 'Alasan (contoh: warga sudah kembali ke rumah)' }}"
                           class="h-10 w-64 max-w-full rounded-xl border border-slate-200 px-3 text-sm focus:outline-none focus:border-red-400">
                    <button type="button" x-show="!alasan" @click="alasan = true"
                            class="text-xs font-semibold text-slate-500 hover:text-slate-700 underline">+ alasan</button>
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold {{ $aksiAju === 'mulai' ? 'bg-green-600 hover:bg-green-700 text-white' : 'bg-white border border-red-200 text-red-700 hover:bg-red-50' }}">
                        <i class="fas {{ $aksiAju === 'mulai' ? 'fa-paper-plane' : 'fa-stop' }}"></i>
                        {{ $aksiAju === 'mulai' ? 'Ajukan Mulai Operasi' : 'Ajukan Penutupan' }}
                    </button>
                </form>
            @endif
        </div>
    @endif
</div>

{{-- Relawan yang BUKAN penanggung jawab: jelaskan kenapa tidak ada tombol pengajuan --}}
@if (! $bisaKelola && auth()->user()?->hasRole('relawan') && $lokasi->isApproved())
    <p class="-mt-4 mb-6 flex items-center gap-2 text-xs text-slate-500">
        <i class="fas fa-circle-info text-slate-400"></i>
        Pengajuan mulai / tutup operasi posko ini hanya dapat dilakukan oleh relawan penanggung jawab
        <b class="text-slate-700">{{ optional(optional($lokasi->relawan)->user)->name ?? '-' }}</b>.
    </p>
@endif

{{-- ================= PERMINTAAN DARI RELAWAN (untuk admin) ================= --}}
@if ($isAdmin && $permintaan)
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 mb-6 flex flex-col md:flex-row md:items-center gap-4">
        <div class="flex items-start gap-3 flex-1">
            <div class="w-10 h-10 shrink-0 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center">
                <i class="fas {{ $permintaan->aksi === 'mulai' ? 'fa-play' : 'fa-stop' }}"></i>
            </div>
            <div>
                <p class="font-bold text-amber-900">
                    Permintaan {{ $permintaan->labelAksi() }} dari {{ $permintaan->peminta->name ?? 'relawan' }}
                </p>
                <p class="text-sm text-amber-800">
                    Diajukan {{ $permintaan->created_at->diffForHumans() }}{{ $permintaan->alasan ? ' · Alasan: ' . $permintaan->alasan : '' }}
                </p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2" x-data="{ tolak: false }">
            <form method="POST" action="{{ route('admin.permintaan-operasional.tolak', $permintaan->id) }}" class="flex flex-wrap items-center gap-2" x-show="tolak" x-cloak>
                @csrf @method('PATCH')
                <input type="text" name="catatan" maxlength="500" placeholder="Catatan untuk relawan (opsional)"
                       class="h-10 w-60 max-w-full rounded-xl border border-amber-200 bg-white px-3 text-sm focus:outline-none focus:border-red-400">
                <button class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold">
                    <i class="fas fa-xmark"></i> Kirim Penolakan
                </button>
                <button type="button" @click="tolak = false" class="text-xs font-semibold text-slate-500 underline">batal</button>
            </form>
            <button type="button" x-show="!tolak" @click="tolak = true"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-rose-200 bg-white hover:bg-rose-50 text-rose-700 text-sm font-semibold">
                <i class="fas fa-xmark"></i> Tolak
            </button>
            <form method="POST" action="{{ route('admin.permintaan-operasional.setujui', $permintaan->id) }}" x-show="!tolak"
                  onsubmit="return confirm('Setujui permintaan ini? Status operasional posko akan langsung berubah.')">
                @csrf @method('PATCH')
                <button class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-green-600 hover:bg-green-700 text-white text-sm font-semibold">
                    <i class="fas fa-check"></i> Setujui
                </button>
            </form>
        </div>
    </div>
@endif

{{-- ================= DASHBOARD SAAT BEROPERASI ================= --}}
@if ($lokasi->isBeroperasi() && $operasional)
    @php
        $p       = $operasional['pengungsi'];
        $bantuan = $operasional['bantuan'];
        $angkaB  = fn ($v) => rtrim(rtrim(number_format((float) $v, 1, ',', '.'), '0'), ',');
        $tingkatB = [
            'segera' => ['Posko ini butuh bantuan segera', 'bg-red-600 text-white',          'fa-triangle-exclamation'],
            'pantau' => ['Posko ini perlu dipantau',       'bg-amber-100 text-amber-900',    'fa-eye'],
            'cukup'  => ['Kebutuhan posko saat ini tercukupi', 'bg-green-100 text-green-800', 'fa-circle-check'],
            'belum'  => ['Kebutuhan belum bisa dihitung',  'bg-slate-100 text-slate-700',    'fa-circle-question'],
        ][$bantuan['tingkat']] ?? null;
    @endphp

    {{-- ================= KEBUTUHAN BANTUAN (untuk donatur) ================= --}}
    <section id="kebutuhan-bantuan" class="scroll-mt-24 bg-white rounded-2xl border border-slate-200 overflow-hidden mb-6">
        @if ($tingkatB)
            <div class="px-5 py-4 flex flex-col sm:flex-row sm:items-center gap-3 {{ $tingkatB[1] }}">
                <i class="fas {{ $tingkatB[2] }} text-xl"></i>
                <div class="flex-1">
                    <p class="font-bold">{{ $tingkatB[0] }}</p>
                    @if ($bantuan['alasan'])
                        <p class="text-sm opacity-90">{{ implode(' · ', $bantuan['alasan']) }}</p>
                    @endif
                </div>
                <p class="text-xs opacity-80 shrink-0">
                    <i class="far fa-clock mr-1"></i>
                    {{ $bantuan['diperbarui'] ? 'Stok diperbarui ' . $bantuan['diperbarui']->diffForHumans() : 'Stok belum pernah dicatat' }}
                </p>
            </div>
        @endif

        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fas fa-hand-holding-heart text-red-500"></i> Kebutuhan Bantuan
            </h3>
            <p class="text-xs text-slate-500">
                Jumlah yang perlu dikirim agar stok cukup untuk <b>{{ $bantuan['target_hari'] }} hari</b> ke depan
                bagi {{ number_format($bantuan['jumlah_pengungsi'], 0, ',', '.') }} pengungsi. Barang berlabel <b>cukup</b> belum perlu dikirim.
            </p>
        </div>

        @if ($bantuan['stok']->isEmpty())
            <p class="px-5 py-6 text-sm text-slate-500 text-center">Belum ada data kebutuhan logistik untuk posko ini.</p>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($bantuan['stok']->sortBy(fn ($s) => [$s->perlu_dikirim ? 0 : 1, $s->mendesak ? 0 : 1, $s->cukup_hari ?? 999999]) as $s)
                    @php
                        [$lbl, $warna] = match (true) {
                            $s->kekurangan === null => ['Belum dihitung', 'bg-slate-100 text-slate-500'],
                            $s->status === 'merah'  => ['Mendesak',       'bg-red-100 text-red-700'],
                            $s->status === 'kuning' => ['Segera',         'bg-amber-100 text-amber-800'],
                            $s->perlu_dikirim       => ['Tidak mendesak', 'bg-blue-50 text-blue-700'],
                            default                 => ['Cukup',          'bg-green-100 text-green-700'],
                        };
                    @endphp
                    <li class="px-5 py-3 flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-slate-800">
                                {{ $s->nama_item }}
                                <span class="ml-1 px-2 py-0.5 rounded-full text-[11px] font-bold {{ $warna }}">{{ $lbl }}</span>
                            </p>
                            <p class="text-xs text-slate-500">
                                Sisa {{ $angkaB($s->jumlah_stok) }} {{ $s->satuan }}
                                @if ($s->belum_pernah_masuk) (belum pernah ada) @endif
                                @if ($s->cukup_hari !== null) · {{ $s->cukup_hari < 1 ? \App\Services\OperasionalLokasiService::teksHari($s->cukup_hari) : 'cukup untuk ' . \App\Services\OperasionalLokasiService::teksHari($s->cukup_hari) }} @endif
                                @if ($s->kebutuhan_hari_ini) · butuh {{ $angkaB($s->kebutuhan_hari_ini) }} {{ $s->satuan }}/hari @endif
                            </p>
                        </div>
                        <div class="sm:text-right shrink-0">
                            @if ($s->perlu_dikirim)
                                <p class="text-xs text-slate-500">Perlu dikirim</p>
                                <p class="text-lg font-black {{ $s->mendesak ? 'text-red-600' : 'text-slate-800' }} tabular-nums">
                                    ±{{ $angkaB($s->kekurangan) }} <span class="text-sm font-semibold text-slate-500">{{ $s->satuan }}</span>
                                </p>
                            @elseif ($s->kekurangan === 0)
                                <p class="text-sm font-semibold text-green-700"><i class="fas fa-check mr-1"></i>Belum perlu dikirim</p>
                            @else
                                <p class="text-xs text-slate-400">Kebutuhan harian belum diatur</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($bantuan['rentan']->isNotEmpty())
            <div class="px-5 py-4 border-t border-slate-100 bg-purple-50/40">
                <p class="text-sm font-bold text-slate-800 mb-2"><i class="fas fa-person-cane text-purple-500 mr-1"></i> Kebutuhan khusus kelompok rentan</p>
                <ul class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                    @foreach ($bantuan['rentan'] as $kelompok => $r)
                        <li class="rounded-xl bg-white border border-purple-100 px-3 py-2">
                            <span class="font-semibold text-purple-800">{{ $kelompok }} ({{ $r['jumlah'] }} orang)</span>
                            <span class="block text-xs text-slate-600">{{ $r['kebutuhan'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </section>

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

            @if ($p['total'] === 0)
                <div class="mb-4 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    <i class="fas fa-circle-info mt-0.5"></i>
                    <div>
                        <p class="font-semibold">Belum ada pengungsi yang tercatat di posko ini.</p>
                        <p class="text-xs text-amber-700 mt-0.5">Kebutuhan air, kalori, toilet, dan logistik dihitung dari jumlah pengungsi, jadi masih 0.
                            @if ($bisaKelola)
                                <a href="{{ route('pengungsi.index', $lokasi->id) }}" class="font-bold underline hover:text-amber-900">Tambah data pengungsi &rarr;</a>
                            @endif
                        </p>
                    </div>
                </div>
            @endif

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

        @php $tanpaKebutuhan = $operasional['stok']->whereNull('kebutuhan_harian'); @endphp
        @if ($operasional['stok']->isNotEmpty() && ($tanpaKebutuhan->isNotEmpty() || $p['total'] === 0))
            <div class="px-5 py-3 bg-amber-50 border-b border-amber-100 text-xs text-amber-800 space-y-1">
                <p class="font-semibold"><i class="fas fa-circle-info mr-1"></i> Daya tahan stok belum bisa dihitung untuk sebagian item karena:</p>
                <ul class="list-disc pl-5 space-y-0.5">
                    @if ($p['total'] === 0)
                        <li>Belum ada pengungsi tercatat (rumus: kebutuhan harian per orang &times; jumlah pengungsi).</li>
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
                        @php [$bgBaris, $dot, $teks] = $warnaStok[$s->status]; @endphp
                        <tr class="border-t border-slate-100">
                            <td class="px-5 py-3 font-semibold text-slate-800">{{ $s->nama_item }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ $s->nama_kategori ?? '-' }}</td>
                            <td class="px-5 py-3 text-right">{{ number_format($s->jumlah_stok, 0, ',', '.') }} {{ $s->satuan }}</td>
                            <td class="px-5 py-3 text-right text-slate-500">
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
                            <td class="px-5 py-3 text-right font-semibold">{{ \App\Services\OperasionalLokasiService::teksHari($s->cukup_hari) }}</td>
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