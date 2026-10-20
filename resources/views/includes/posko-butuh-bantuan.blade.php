{{--
    Panel "Posko Butuh Bantuan" (Monitoring Lokasi & dashboard admin).
        @include('includes.posko-butuh-bantuan', ['statusBantuan' => $service->semuaStatusBantuan(), 'admin' => false])

    Hanya menampilkan posko BEROPERASI yang berstatus "segera" atau "pantau",
    urut dari yang paling mendesak, lengkap dengan barang & jumlah yang perlu dikirim.
--}}
@php
    $admin     = $admin ?? false;
    $mendesak  = $statusBantuan->whereIn('tingkat', ['segera', 'pantau'])->values();
    $belum     = $statusBantuan->where('tingkat', 'belum')->count();
    $aktif     = $statusBantuan->count();
    $target    = \App\Services\OperasionalLokasiService::targetHari();
    $angka     = fn ($v) => rtrim(rtrim(number_format((float) $v, 1, ',', '.'), '0'), ',');
    $tingkatUi = [
        'segera' => ['Butuh bantuan segera', 'bg-red-600 text-white',        'border-red-200',   'fa-triangle-exclamation'],
        'pantau' => ['Perlu dipantau',       'bg-amber-100 text-amber-800',  'border-amber-200', 'fa-eye'],
    ];
@endphp

<section class="bg-white rounded-2xl border border-slate-200 overflow-hidden" aria-labelledby="judul-butuh-bantuan">
    <div class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div>
            <h2 id="judul-butuh-bantuan" class="text-lg font-bold text-slate-900 flex items-center gap-2">
                <i class="fas fa-hand-holding-heart text-red-500"></i> Posko Butuh Bantuan
                @if ($mendesak->isNotEmpty())
                    <span class="px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-xs font-bold">{{ $mendesak->count() }}</span>
                @endif
            </h2>
            <p class="text-sm text-slate-500">
                Salurkan bantuan ke posko di bawah ini lebih dulu, agar bantuan tidak menumpuk di posko yang sudah tercukupi.
                Jumlah dihitung agar stok cukup untuk <b>{{ $target }} hari</b>.
            </p>
        </div>
        <p class="text-xs text-slate-400 shrink-0">{{ $aktif }} posko sedang beroperasi</p>
    </div>

    @if ($mendesak->isEmpty())
        <div class="px-5 py-8 flex flex-col items-center text-center">
            <div class="w-12 h-12 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xl">
                <i class="fas fa-circle-check"></i>
            </div>
            <p class="mt-3 font-bold text-slate-800">
                {{ $aktif ? 'Semua posko yang beroperasi saat ini tercukupi' : 'Belum ada posko yang beroperasi' }}
            </p>
            <p class="text-sm text-slate-500 max-w-md">
                {{ $aktif ? 'Stok logistik setiap posko cukup untuk 3 hari ke depan atau lebih.' : 'Kebutuhan bantuan akan muncul di sini saat posko mulai menampung pengungsi.' }}
            </p>
        </div>
    @else
        <div class="p-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach ($mendesak as $b)
                @php
                    [$label, $warnaBadge, $warnaBorder, $ikon] = $tingkatUi[$b['tingkat']];
                    $l = $b['lokasi'];
                @endphp
                <article class="rounded-2xl border {{ $warnaBorder }} p-4 flex flex-col gap-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold {{ $warnaBadge }}">
                                <i class="fas {{ $ikon }} text-[10px]"></i> {{ $label }}
                            </span>
                            <h3 class="mt-2 font-bold text-slate-900 leading-snug">{{ $l->nama_lokasi }}</h3>
                            <p class="text-xs text-slate-500">
                                {{ $l->desa ? 'Desa ' . $l->desa->nama_desa . ' · ' : '' }}{{ $b['jumlah_pengungsi'] }} pengungsi
                            </p>
                        </div>
                    </div>

                    @if ($b['kebutuhan']->isNotEmpty())
                        <ul class="space-y-1.5 text-sm">
                            @foreach ($b['kebutuhan']->take(3) as $k)
                                <li class="flex items-start justify-between gap-2">
                                    <span class="min-w-0">
                                        <span class="font-semibold text-slate-800">{{ $k->nama_item }}</span>
                                        <span class="block text-[11px] {{ $k->status === 'merah' ? 'text-red-600' : ($k->status === 'kuning' ? 'text-amber-600' : 'text-slate-400') }}">
                                            {{ $k->belum_pernah_masuk ? 'Belum ada stok' : \App\Services\OperasionalLokasiService::teksHari($k->cukup_hari, true) }}
                                        </span>
                                    </span>
                                    <span class="shrink-0 text-right font-bold text-slate-900 tabular-nums">
                                        ±{{ $angka($k->kekurangan) }} <span class="text-xs font-semibold text-slate-500">{{ $k->satuan }}</span>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                        @if ($b['kebutuhan']->count() > 3)
                            <p class="text-xs text-slate-500">+{{ $b['kebutuhan']->count() - 3 }} barang lain</p>
                        @endif
                    @else
                        <p class="text-sm text-slate-600">{{ implode(' · ', $b['alasan']) }}</p>
                    @endif

                    <a href="{{ $admin ? route('admin.lokasi.show', $l->id) : route('lokasi.show', $l->id) }}#kebutuhan-bantuan"
                       class="mt-auto inline-flex items-center gap-1.5 text-sm font-bold text-red-600 hover:text-red-700">
                        Lihat kebutuhan lengkap <i class="fas fa-arrow-right text-xs"></i>
                    </a>
                </article>
            @endforeach
        </div>
    @endif

    @if ($admin && $belum)
        <p class="px-5 py-3 border-t border-slate-100 text-xs text-amber-700 bg-amber-50">
            <i class="fas fa-circle-info mr-1"></i>
            {{ $belum }} posko beroperasi belum bisa dihitung karena kebutuhan harian barang belum diatur di
            <a href="{{ route('admin.logistik.index') }}" class="font-bold underline">Master Logistik</a>.
        </p>
    @endif
</section>
