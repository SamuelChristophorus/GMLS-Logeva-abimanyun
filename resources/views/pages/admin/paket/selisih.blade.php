@extends('layouts.admin')

@section('title', 'Selisih Stok Posko')

@section('content')
<div class="relative z-10 container mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10 min-h-screen flex flex-col gap-8">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white/60 backdrop-blur-xl p-6 md:p-8 rounded-3xl border border-white/80 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-amber-50 border border-amber-100/50 text-amber-600 text-xs font-bold tracking-wide uppercase mb-3">
                <i class="fas fa-balance-scale opacity-70"></i> Aplikasi Offline
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 tracking-tight">Selisih Stok Posko</h1>
            <p class="text-slate-500 mt-1 font-medium text-sm">
                Barang yang stoknya di HP posko berbeda dengan stok pusat, dari paket terbaru tiap posko.
                Stok pusat tidak diubah otomatis.
            </p>
        </div>
        <a href="{{ route('admin.paket.index') }}" class="inline-flex items-center justify-center gap-2 bg-white hover:bg-slate-50 text-slate-600 font-bold px-5 py-3 rounded-xl shadow-sm border border-slate-200 transition-all hover:-translate-x-1">
            <i class="fas fa-arrow-left text-sm opacity-70"></i> Kembali
        </a>
    </div>

    @if ($selisih->isEmpty())
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-sm text-emerald-800">
            <i class="fas fa-check-circle mr-1"></i> Tidak ada selisih: stok di paket terbaru setiap posko sama dengan stok pusat.
        </div>
    @else
        <div class="rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-600">
            <p class="font-bold text-slate-800 mb-2">Cara menyelidiki</p>
            <ul class="space-y-1 list-disc list-inside">
                <li>Pastikan semua HP Anggota di posko itu sudah sinkron ke HP Induk, lalu minta paket baru dari HP Induk.</li>
                <li>Cek barang masuk/keluar posko itu yang dicatat langsung di website.</li>
                <li>Kalau stok pusat memang salah, perbaiki lewat menu Logistik. Paket berikutnya akan memperbarui daftar ini.</li>
            </ul>
        </div>

        @foreach ($selisih as $baris)
            @php $lokasi = $baris->first()->lokasi; $paket = $baris->first()->riwayat; @endphp
            <div class="bg-white rounded-3xl border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] p-6 md:p-8">
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 mb-5">
                    <div>
                        <h2 class="text-lg font-extrabold text-slate-800">
                            {{ $lokasi->nama_lokasi ?? 'Posko terhapus' }}
                            @if ($lokasi?->trashed()) <span class="text-xs text-slate-400">(dihapus)</span> @endif
                        </h2>
                        <p class="text-xs text-slate-500 mt-1">
                            Paket {{ $paket->nama_file }} · {{ $paket->labelPerangkat() }}
                            @if ($paket->paket_dibuat_pada) · dibuat {{ $paket->paket_dibuat_pada->format('d-m-Y H:i') }} WIB @endif
                            · diimpor {{ $paket->created_at->format('d-m-Y H:i') }} oleh {{ $paket->user->name ?? 'akun terhapus' }}
                        </p>
                    </div>
                    @if ($lokasi && ! $lokasi->trashed())
                        <a href="{{ route('admin.lokasi.show', $lokasi->id) }}" class="self-start inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-50 text-blue-600 font-bold text-sm border border-blue-100 hover:bg-blue-100">
                            <i class="fas fa-eye text-xs"></i> Detail Posko
                        </a>
                    @endif
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100 rounded-tl-2xl">Barang</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100 text-right">Stok HP</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100 text-right">Stok pusat</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100 text-right">Selisih</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100 rounded-tr-2xl">Dugaan penyebab</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($baris as $s)
                                <tr class="align-top">
                                    <td class="py-3 px-4 font-bold text-slate-700">{{ $s->nama_item }}</td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap {{ $s->stok_hp < 0 ? 'text-rose-600 font-bold' : 'text-slate-700' }}">
                                        {{ number_format($s->stok_hp, 0, ',', '.') }} {{ $s->satuan }}
                                    </td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap text-slate-700">
                                        {{ number_format($s->stok_pusat, 0, ',', '.') }} {{ $s->satuan }}
                                    </td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap font-bold text-amber-600">
                                        {{ $s->selisih() > 0 ? '+' : '' }}{{ number_format($s->selisih(), 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 px-4 text-xs text-slate-500 min-w-[16rem]">{{ $s->dugaan() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    @endif
</div>
@endsection
