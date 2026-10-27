@extends('layouts.dashboard')

@section('title', 'Monitoring Lokasi')

@php
    // Posko yang laporannya ditolak admin tidak ditampilkan ke publik
    $semuaLokasi = \App\Models\Lokasi::tampilPublik()
        ->with(['latestStatusView', 'desa'])
        ->withCount('pengungsi')
        ->get();

    // Status bantuan posko yang beroperasi (panel "Posko Butuh Bantuan" + penanda di peta)
    $statusBantuan = app(\App\Services\OperasionalLokasiService::class)->semuaStatusBantuan();
    $bantuanPer    = $statusBantuan->keyBy(fn ($b) => $b['lokasi']->id);

    // Demografi pengungsi semua posko sekaligus (panel visualisasi di bawah peta)
    $idLokasi  = $semuaLokasi->pluck('id');
    $demografi = \App\Models\Pengungsi::tabelDiPosko()
        ->whereIn('lokasi_id', $idLokasi)
        ->selectRaw("lokasi_id,
            SUM(CASE WHEN jenis_kelamin = 'Laki-laki' THEN 1 ELSE 0 END) as laki,
            SUM(CASE WHEN jenis_kelamin = 'Perempuan' THEN 1 ELSE 0 END) as perempuan")
        ->groupBy('lokasi_id')
        ->get()
        ->keyBy('lokasi_id');
    $rentanPer = \App\Models\Pengungsi::tabelDiPosko()
        ->whereIn('lokasi_id', $idLokasi)
        ->where('kelompok_rentan', '!=', 'Tidak')
        ->selectRaw('lokasi_id, kelompok_rentan, COUNT(*) as jumlah')
        ->groupBy('lokasi_id', 'kelompok_rentan')
        ->get()
        ->groupBy('lokasi_id');
    $targetHari = \App\Services\OperasionalLokasiService::targetHari();

    // Kondisi posko untuk warna titik di peta publik: hanya beroperasi / tidak beroperasi
    $petaLokasi = $semuaLokasi->map(function ($l) use ($bantuanPer, $demografi, $rentanPer) {
        $kondisi = $l->isBeroperasi() ? 'beroperasi' : 'tidak';
        $b       = $bantuanPer->get($l->id);
        $d       = $demografi->get($l->id);

        return [
            // segera / pantau / cukup / belum (null jika tidak beroperasi)
            'bantuan'   => $b['tingkat'] ?? null,
            'butuh'     => $b ? $b['kebutuhan']->filter->mendesak->take(3)->map(fn ($k) => [
                'nama'   => $k->nama_item,
                'jumlah' => $k->kekurangan,
                'satuan' => $k->satuan,
            ])->values() : [],
            'id'        => $l->id,
            'nama'      => $l->nama_lokasi,
            'alamat'    => $l->alamat_lokasi,
            'desa'      => $l->desa->nama_desa ?? null,
            'latitude'  => $l->latitude,
            'longitude' => $l->longitude,
            'kapasitas' => (int) $l->kapasitas_pengungsi,
            'pengungsi' => $l->pengungsi_count,
            'kondisi'   => $kondisi,
            'hari_ke'   => $l->hariOperasi(),
            'sejak'     => $l->mulai_beroperasi_at?->translatedFormat('d M Y'),
            // Panel visualisasi
            'gender'    => ['laki' => (int) ($d->laki ?? 0), 'perempuan' => (int) ($d->perempuan ?? 0)],
            'rentan'    => ($rentanPer->get($l->id) ?? collect())
                ->map(fn ($r) => ['nama' => $r->kelompok_rentan, 'jumlah' => (int) $r->jumlah])
                ->sortByDesc('jumlah')->values(),
            // Hanya bahan pangan (kategori Makanan & Minuman), paling cepat habis dulu
            'stok'      => $b ? $b['stok']->whereIn('nama_kategori', ['Makanan', 'Minuman'])
                ->sortBy(fn ($s) => $s->cukup_hari ?? PHP_INT_MAX)->take(5)
                ->map(fn ($s) => [
                    'nama'   => $s->nama_item,
                    'satuan' => $s->satuan,
                    'jumlah' => $s->jumlah_stok,
                    'hari'   => $s->cukup_hari,
                    'status' => $s->status,
                ])->values() : [],
        ];
    });
    $jumlahKondisi = $petaLokasi->countBy('kondisi');

    $totalTea       = $semuaLokasi->count();
    $totalDesa      = \App\Models\Desa::count();
    $totalKapasitas = $semuaLokasi->sum('kapasitas_pengungsi');

    $tim = [
        ['anis.jpeg',   'Anis Faisal Reza'],
        ['zedro.jpeg',  'Zedro Deniro Mason'],
        ['prajna.jpeg', 'Prajna Ananda Citra'],
        ['melati.jpeg', 'Melati Octaviana Purba'],
        ['melisa.jpeg', 'Melisa Ashley'],
        ['farrel.jpeg', 'Farrel Safwan'],
        ['abiii.jpeg',     'Septian Abimanyu'],
        ['farhan.jpeg',    'Mohammad Farhan Junialdi'],
        ['farrelaja.jpeg', 'Farrel'],
        ['samuel.jpeg',    'Samuel Christophorus Wijaya'],
    ];
@endphp

@section('content')
<div class="space-y-6">

    {{-- ===== Judul halaman ===== --}}
    <div>
        @auth
            <p class="text-sm text-slate-500">Hi, {{ Auth::user()->name }} 👋</p>
        @endauth
        <h1 class="text-2xl font-black text-slate-900">Pantau &amp; Kelola Tempat Evakuasi Akhir</h1>
        <p class="text-sm text-slate-500 mt-1">
            Sistem monitoring dan pengelolaan TEA untuk membantu mitigasi bencana secara cepat, tepat, dan terintegrasi.
        </p>
    </div>

    {{-- ===== Statistik ===== --}}
    <section id="statistik" class="grid grid-cols-1 sm:grid-cols-3 gap-4 scroll-mt-24">
        <div class="bg-white rounded-2xl border border-slate-200 p-5 flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-slate-500">Total Lokasi TEA</p>
                <p class="text-3xl font-black text-red-600 mt-1">{{ $totalTea }}</p>
                <p class="text-xs text-slate-400 mt-1">Titik evakuasi aktif</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-red-600 text-white flex items-center justify-center text-xl">
                <i class="fas fa-house-circle-check"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-slate-500">Total Desa</p>
                <p class="text-3xl font-black text-blue-600 mt-1">{{ $totalDesa }}</p>
                <p class="text-xs text-slate-400 mt-1">Wilayah terdata sistem</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-600 text-white flex items-center justify-center text-xl">
                <i class="fas fa-map-location-dot"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-slate-500">Total Kapasitas</p>
                <p class="text-3xl font-black text-green-600 mt-1">{{ number_format($totalKapasitas, 0, ',', '.') }}</p>
                <p class="text-xs text-slate-400 mt-1">Orang dapat ditampung</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-green-600 text-white flex items-center justify-center text-xl">
                <i class="fas fa-people-roof"></i>
            </div>
        </div>
    </section>

    {{-- ===== Posko butuh bantuan (untuk donatur) ===== --}}
    @include('includes.posko-butuh-bantuan', ['statusBantuan' => $statusBantuan, 'admin' => false])

    {{-- ===== Peta ===== --}}
    <section class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                <i class="fas fa-map text-red-500"></i> Peta Persebaran TEA
            </h2>
            <p class="text-sm text-slate-500">
                Menampilkan <span class="font-semibold text-red-600">{{ $totalTea }}</span>
                Tempat Evakuasi Akhir (TEA) terdaftar
            </p>
        </div>
        <div class="p-4 space-y-3">
            {{-- Legenda + filter kondisi posko --}}
            <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Filter kondisi posko">
                <span class="text-xs font-semibold text-slate-500 mr-1">Kondisi posko:</span>
                @foreach ([
                    'beroperasi' => ['Beroperasi',       'fa-play'],
                    'tidak'      => ['Tidak beroperasi', 'fa-pause'],
                ] as $key => [$label, $ikon])
                    <button type="button" data-kondisi="{{ $key }}" aria-pressed="true"
                            class="legenda-tea inline-flex items-center gap-2 pl-1 pr-3 py-1 rounded-full border border-slate-200 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                        <span class="tea-dot tea-{{ $key }} w-5 h-5 text-[9px]"><i class="fas {{ $ikon }}"></i></span>
                        {{ $label }}
                        <span class="text-slate-400 font-medium">{{ $jumlahKondisi[$key] ?? 0 }}</span>
                    </button>
                @endforeach

                {{-- Keterangan lencana bantuan (bukan filter) --}}
                <span class="hidden sm:block w-px h-5 bg-slate-200 mx-1"></span>
                <span class="inline-flex items-center gap-1.5 text-xs text-slate-600">
                    <span class="tea-seru tea-seru-segera">!</span> Butuh bantuan segera
                </span>
                <span class="inline-flex items-center gap-1.5 text-xs text-slate-600">
                    <span class="tea-seru tea-seru-pantau">!</span> Perlu dipantau
                </span>
            </div>

            <div id="map" class="h-[460px] md:h-[560px] w-full rounded-xl border border-slate-200"></div>
            <p class="text-xs text-slate-400">
                <i class="fas fa-hand-pointer mr-1"></i>
                Arahkan kursor ke titik untuk melihat ringkasan &amp; visualisasi posko (di bawah peta), klik titik untuk membuka halaman detail.
                <span class="sm:hidden">Di HP: ketuk sekali untuk ringkasan, ketuk lagi untuk detail.</span>
            </p>

            {{-- Visualisasi kondisi posko yang terakhir di-hover (diisi lewat JS) --}}
            {{-- Turun (drop down) otomatis saat titik posko di-hover; tetap terbuka sampai ditutup --}}
            <div id="viz-posko" class="viz-lipat" aria-live="polite">
                <div class="overflow-hidden">
                    <div class="rounded-xl border border-slate-200 bg-slate-50">
                        <div class="flex items-center justify-between gap-3 px-4 py-3">
                            <span class="text-sm font-semibold text-slate-700">
                                <i class="fas fa-chart-simple text-red-500 mr-1"></i>
                                Kondisi Posko: <span id="viz-nama" class="text-slate-900"></span>
                            </span>
                            <button type="button" id="viz-tutup" aria-label="Tutup kondisi posko"
                                    class="w-7 h-7 rounded-full text-slate-400 hover:bg-slate-200 hover:text-slate-700 transition">
                                <i class="fas fa-xmark"></i>
                            </button>
                        </div>
                        <div id="viz-isi" class="px-4 pb-4"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== About Us ===== --}}
    <footer class="bg-slate-900 text-white rounded-2xl overflow-hidden" x-data="{ about: false }">
        <div class="text-center py-4 border-b border-white/10">
            <button @click="about = !about"
                    class="inline-flex items-center gap-2 px-5 py-2 rounded-full bg-white/10 hover:bg-white/20 text-sm font-semibold transition">
                <i class="fas fa-circle-info"></i> About Us
                <i class="fas fa-chevron-down transition-transform" :class="{ 'rotate-180': about }"></i>
            </button>
        </div>

        <div x-show="about" x-cloak x-transition class="max-w-5xl mx-auto p-6 md:p-8">
            <div class="text-center mb-8">
                <h3 class="text-2xl font-bold mb-3">Tentang Sistem</h3>
                <p class="text-slate-300 max-w-3xl mx-auto">
                    GMLS Logeva merupakan platform yang dirancang untuk membantu pengelolaan Tempat Evakuasi Akhir (TEA),
                    pemantauan ketersediaan logistik, serta penyajian informasi mitigasi bencana secara terintegrasi,
                    cepat, dan akurat.
                </p>
            </div>

            <h4 class="text-xl font-bold text-center mb-5">Tim Pengembang Website</h4>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                @foreach ($tim as [$foto, $nama])
                    <div class="bg-white/10 rounded-2xl p-4 text-center">
                        <img src="{{ asset('assets/team/' . $foto) }}" alt="{{ $nama }}"
                             class="w-20 h-20 mx-auto mb-3 rounded-full object-cover border-4 border-white/20">
                        <h5 class="font-semibold text-sm">{{ $nama }}</h5>
                    </div>
                @endforeach
            </div>
        </div>

        <p class="border-t border-white/10 py-4 text-center text-sm text-slate-400">
            © {{ date('Y') }} GMLS. All Rights Reserved.
        </p>
    </footer>

</div>
@endsection

@section('scripts')
<style>
    /* Titik posko: warna + ikon sesuai kondisi (ikon membuat status tetap terbaca tanpa bergantung warna) */
    .tea-dot {
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 9999px; border: 2px solid #fff; color: #fff;
        box-shadow: 0 2px 6px rgba(15, 23, 42, .35);
    }
    .tea-beroperasi { background: #16a34a; }
    .tea-tidak      { background: #64748b; }

    .tea-marker { position: relative; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; }
    .tea-marker .tea-dot { width: 26px; height: 26px; font-size: 10px; position: relative; z-index: 1; transition: transform .15s ease; }
    .tea-marker:hover .tea-dot, .tea-marker.aktif .tea-dot { transform: scale(1.25); }
    /* Denyut hanya untuk posko yang sedang beroperasi */
    .tea-marker .tea-denyut {
        position: absolute; inset: 0; border-radius: 9999px; background: #16a34a; opacity: .35;
        animation: tea-denyut 1.8s ease-out infinite;
    }
    @keyframes tea-denyut { from { transform: scale(.6); opacity: .5; } to { transform: scale(1.6); opacity: 0; } }
    @media (prefers-reduced-motion: reduce) { .tea-marker .tea-denyut { animation: none; } }
    .tea-marker .tea-seru { position: absolute; top: -4px; right: -4px; z-index: 2; }

    /* Panel kondisi posko: animasi turun (tinggi 0 -> isi) saat titik di-hover */
    .viz-lipat {
        display: grid; grid-template-rows: 0fr; opacity: 0; transform: translateY(-8px);
        transition: grid-template-rows .35s ease, opacity .3s ease, transform .35s ease, margin-top .35s ease;
    }
    .viz-lipat:not(.buka) { margin-top: 0 !important; }   /* tanpa celah saat tertutup */
    .viz-lipat.buka { grid-template-rows: 1fr; opacity: 1; transform: none; }
    /* Ganti posko saat panel sudah terbuka: isi muncul halus */
    .viz-masuk { animation: viz-masuk .3s ease; }
    @keyframes viz-masuk { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: none; } }
    @media (prefers-reduced-motion: reduce) { .viz-lipat, .viz-masuk { transition: none; animation: none; } }
</style>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@include('includes.peta-tooltip')

<script>
document.addEventListener('DOMContentLoaded', function () {

    const lokasiData = @json($petaLokasi);
    const detailBase = @json(url('lokasi'));   // otomatis ikut /logeva di hosting

    const map = L.map('map').setView([-6.93, 106.12], 11);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 18
    }).addTo(map);

    const esc = TeaPeta.esc;

    // Tampilan per kondisi posko (warna di CSS .tea-*)
    const KONDISI = {
        beroperasi: { label: 'Beroperasi',       ikon: 'fa-play',  badge: 'bg-green-100 text-green-700' },
        tidak:      { label: 'Tidak beroperasi', ikon: 'fa-pause', badge: 'bg-slate-200 text-slate-700' },
    };

    // Status bantuan posko beroperasi -> lencana "!" di pojok titik
    const BANTUAN = {
        segera: { label: 'Butuh bantuan segera', kotak: 'bg-red-50 border-red-200 text-red-800',       ikon: 'fa-triangle-exclamation' },
        pantau: { label: 'Perlu dipantau',       kotak: 'bg-amber-50 border-amber-200 text-amber-800', ikon: 'fa-eye' },
    };
    const angka = (n) => Number(n || 0).toLocaleString('id-ID');

    const ikonTea = (kondisi, bantuan) => L.divIcon({
        className: '',
        html: `<div class="tea-marker">
                   ${kondisi === 'beroperasi' ? '<span class="tea-denyut"></span>' : ''}
                   <span class="tea-dot tea-${kondisi}"><i class="fas ${KONDISI[kondisi].ikon}"></i></span>
                   ${BANTUAN[bantuan] ? `<span class="tea-seru tea-seru-${bantuan}" aria-hidden="true">!</span>` : ''}
               </div>`,
        iconSize: [32, 32],
        iconAnchor: [16, 16],
        tooltipAnchor: [0, -16],
    });

    const keteranganStatus = (l) => l.kondisi === 'beroperasi'
        ? `Hari ke-${l.hari_ke}${l.sejak ? ' · sejak ' + esc(l.sejak) : ''}`
        : 'Posko sedang tidak menampung pengungsi';

    const isiTooltip = (l) => {
        const k      = KONDISI[l.kondisi];
        const persen = l.kapasitas > 0 ? Math.min(100, Math.round(l.pengungsi / l.kapasitas * 100)) : 0;
        return `
            <div class="w-64 bg-white">
                <div class="px-4 pt-3 pb-2">
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-bold ${k.badge}">
                        <i class="fas ${k.ikon} text-[9px]"></i> ${k.label}
                    </span>
                    <h3 class="mt-2 font-bold text-slate-900 text-sm leading-snug">${esc(l.nama)}</h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">${keteranganStatus(l)}</p>
                </div>
                ${BANTUAN[l.bantuan] ? `
                <div class="mx-4 mb-2 px-3 py-2 rounded-lg border text-[11px] ${BANTUAN[l.bantuan].kotak}">
                    <p class="font-bold"><i class="fas ${BANTUAN[l.bantuan].ikon} mr-1"></i>${BANTUAN[l.bantuan].label}</p>
                    ${l.butuh.length ? `<ul class="mt-1 space-y-0.5">${l.butuh.map(b =>
                        `<li class="flex justify-between gap-2"><span>${esc(b.nama)}</span><b>±${angka(b.jumlah)} ${esc(b.satuan)}</b></li>`).join('')}</ul>` : ''}
                </div>` : ''}
                <div class="px-4 pb-3 space-y-2 text-xs text-slate-600">
                    ${l.desa ? `<p><i class="fas fa-map-signs text-slate-400 w-4"></i> Desa ${esc(l.desa)}</p>` : ''}
                    <div>
                        <div class="flex justify-between mb-1">
                            <span><i class="fas fa-users text-slate-400 w-4"></i> Pengungsi</span>
                            <span class="font-semibold text-slate-800">${l.pengungsi} / ${l.kapasitas} jiwa</span>
                        </div>
                        <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full ${persen >= 90 ? 'bg-red-500' : persen >= 70 ? 'bg-amber-500' : 'bg-blue-600'}" style="width:${persen}%"></div>
                        </div>
                    </div>
                </div>
                <div class="px-4 py-2 bg-slate-50 border-t border-slate-100 text-[11px] font-semibold text-red-600">
                    <i class="fas fa-arrow-pointer mr-1"></i> Klik titik untuk lihat detail &rarr;
                </div>
            </div>`;
    };

    /* ---------- Dropdown kondisi posko (di bawah peta), diisi saat titik di-hover ---------- */
    const vizNama    = document.getElementById('viz-nama');
    const vizIsi     = document.getElementById('viz-isi');
    const targetHari = @json($targetHari);
    const STOK_WARNA = { merah: 'bg-red-500', kuning: 'bg-amber-500', hijau: 'bg-green-600' };
    const teksHari   = (h) => h === null ? '-' : h <= 0 ? 'Sudah habis' : h < 1 ? 'Habis hari ini' : Math.floor(h) + ' hari';

    // Batang horizontal sederhana: label kiri, nilai kanan, batang di bawahnya
    const batang = (label, nilai, persen, warna) => `
        <div>
            <div class="flex justify-between text-xs mb-1">
                <span class="text-slate-600 truncate pr-2">${label}</span>
                <span class="font-semibold text-slate-800 whitespace-nowrap">${nilai}</span>
            </div>
            <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                <div class="h-full rounded-full ${warna}" style="width:${persen}%"></div>
            </div>
        </div>`;

    const kartuViz = (judul, isi) => `
        <div class="bg-white rounded-lg border border-slate-200 p-3 space-y-2">
            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wide">${judul}</p>
            ${isi}
        </div>`;
    const kosong = (teks) => `<p class="text-xs text-slate-400">${teks}</p>`;

    function vizPengungsi(l) {
        const persen = l.kapasitas > 0 ? Math.min(100, Math.round(l.pengungsi / l.kapasitas * 100)) : 0;
        const g      = l.gender;
        const rentan = l.rentan.reduce((s, r) => s + r.jumlah, 0);
        return kartuViz('Pengungsi', `
            ${batang(`Terisi ${persen}%`, `${angka(l.pengungsi)} / ${angka(l.kapasitas)} jiwa`, persen,
                persen >= 90 ? 'bg-red-500' : persen >= 70 ? 'bg-amber-500' : 'bg-blue-600')}
            <p class="text-xs text-slate-600">
                <i class="fas fa-mars text-sky-500"></i> ${angka(g.laki)} laki-laki ·
                <i class="fas fa-venus text-pink-500"></i> ${angka(g.perempuan)} perempuan
            </p>
            <p class="text-xs text-slate-600">
                <i class="fas fa-hand-holding-heart text-rose-500"></i>
                ${rentan ? `<b>${angka(rentan)}</b> rentan (${l.rentan.map(r => `${esc(r.nama)} ${r.jumlah}`).join(', ')})`
                         : 'Tidak ada kelompok rentan'}
            </p>`);
    }

    function vizPangan(l) {
        let isi;
        if (l.kondisi !== 'beroperasi') isi = kosong('Posko tidak beroperasi, stok tidak dihitung.');
        else if (!l.stok.length)        isi = kosong('Belum ada data makanan / minuman.');
        else isi = l.stok.map(s => s.hari === null
                ? batang(esc(s.nama), `${angka(s.jumlah)} ${esc(s.satuan)}`, 0, 'bg-slate-400')
                : batang(esc(s.nama), teksHari(s.hari), Math.min(100, s.hari / targetHari * 100), STOK_WARNA[s.status]))
            .join('') + `<p class="text-[11px] text-slate-400">Batang penuh = cukup ${targetHari} hari</p>`;
        return kartuViz('Ketahanan Pangan', isi);
    }

    const vizPanel = document.getElementById('viz-posko');
    let vizAktif   = null;

    function tampilkanViz(l) {
        if (vizAktif === l.id && vizPanel.classList.contains('buka')) return;
        vizAktif = l.id;
        vizNama.textContent = l.nama;
        vizIsi.innerHTML = `
            <div class="viz-masuk">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">${vizPengungsi(l)}${vizPangan(l)}</div>
                <a href="${detailBase}/${l.id}" class="inline-block mt-3 text-xs font-semibold text-red-600 hover:underline">
                    Lihat detail posko &rarr;
                </a>
            </div>`;
        vizPanel.classList.add('buka');
    }

    document.getElementById('viz-tutup').addEventListener('click', () => vizPanel.classList.remove('buka'));

    const markers = [];

    lokasiData.forEach(lokasi => {
        const lat = parseFloat(lokasi.latitude);
        const lng = parseFloat(lokasi.longitude);
        if (isNaN(lat) || isNaN(lng) || !KONDISI[lokasi.kondisi]) return;

        const marker = L.marker([lat, lng], {
            icon: ikonTea(lokasi.kondisi, lokasi.bantuan),
            title: `${lokasi.nama} (${KONDISI[lokasi.kondisi].label}${BANTUAN[lokasi.bantuan] ? ', ' + BANTUAN[lokasi.bantuan].label.toLowerCase() : ''})`,
            alt: lokasi.nama,
            riseOnHover: true,
            // Posko beroperasi selalu di atas titik lain saat bertumpuk
            // Urutan tumpuk: butuh bantuan segera > perlu dipantau > beroperasi > lainnya
            zIndexOffset: { segera: 3000, pantau: 2000 }[lokasi.bantuan] ?? (lokasi.kondisi === 'beroperasi' ? 1000 : 0),
        }).addTo(map);

        // Kartu hover (tidak terpotong tepi peta) + klik buka detail: lihat includes/peta-tooltip
        TeaPeta.pasang(map, marker, isiTooltip(lokasi), `${detailBase}/${lokasi.id}`, 16);
        // Hover / ketuk / hasil pencarian -> panel visualisasi ikut menampilkan posko ini
        marker.on('tooltipopen', () => tampilkanViz(lokasi));

        markers.push({ nama: String(lokasi.nama ?? '').toLowerCase(), kondisi: lokasi.kondisi, marker });
    });

    if (markers.length) {
        map.fitBounds(L.featureGroup(markers.map(m => m.marker)).getBounds(), { padding: [50, 50] });
    }

    // Legenda = filter: klik untuk menyembunyikan / menampilkan titik dengan kondisi tersebut
    document.querySelectorAll('.legenda-tea').forEach(btn => {
        btn.addEventListener('click', () => {
            const tampil = btn.getAttribute('aria-pressed') !== 'true';
            btn.setAttribute('aria-pressed', tampil);
            btn.classList.toggle('opacity-40', !tampil);
            btn.classList.toggle('line-through', !tampil);
            markers
                .filter(m => m.kondisi === btn.dataset.kondisi)
                .forEach(m => tampil ? m.marker.addTo(map) : m.marker.remove());
        });
    });

    setTimeout(() => map.invalidateSize(), 300);

    // Pencarian dari kotak "Cari lokasi" di topbar (tekan Enter)
    window.addEventListener('tea-search', (e) => {
        const q = String(e.detail || '').trim().toLowerCase();
        if (!q) return;
        const hit = markers.find(m => m.nama.includes(q));
        if (hit) {
            // Kondisi posko sedang disembunyikan lewat legenda? tampilkan lagi
            if (!map.hasLayer(hit.marker)) {
                document.querySelector(`.legenda-tea[data-kondisi="${hit.kondisi}"]`)?.click();
            }
            map.flyTo(hit.marker.getLatLng(), 16);
            map.once('moveend', () => hit.marker.openTooltip());
        } else {
            alert('Lokasi "' + e.detail + '" tidak ditemukan');
        }
    });
});
</script>
@endsection