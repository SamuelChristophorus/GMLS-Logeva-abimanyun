@extends('layouts.admin')

@section('title', 'Detail Status Lokasi')

@section('content')
<div class="relative z-10 container mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10 min-h-screen flex flex-col gap-8">

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white/60 backdrop-blur-xl p-6 md:p-8 rounded-3xl border border-white/80 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-50 border border-blue-100/50 text-blue-600 text-xs font-bold tracking-wide uppercase mb-3">
                <i class="fas fa-clipboard-list opacity-70"></i>
                Detail Log Status
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 tracking-tight">
                Detail Status Lokasi
            </h1>
            <p class="text-slate-500 mt-1 font-medium text-sm flex items-center gap-2">
                <i class="fas fa-map-pin text-slate-400"></i>
                Rekam jejak status untuk posko pengungsian
            </p>
        </div>

        <div class="flex items-center gap-3 mt-2 md:mt-0">
            <a href="{{ route('admin.status-lokasi.edit', $status->id) }}" class="inline-flex items-center justify-center gap-2 bg-amber-50 hover:bg-amber-100 text-amber-600 font-bold px-5 py-3 rounded-xl shadow-sm border border-amber-200 transition-all">
                <i class="fas fa-edit text-sm"></i> Edit
            </a>
            <a href="{{ route('admin.lokasi.show', $status->lokasi_id) }}" class="inline-flex items-center justify-center gap-2 bg-white hover:bg-slate-50 text-slate-600 font-bold px-5 py-3 rounded-xl shadow-sm border border-slate-200 transition-all hover:-translate-x-1">
                <i class="fas fa-arrow-left text-sm opacity-70"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        <!-- Left / Main Column -->
        <div class="xl:col-span-2 flex flex-col gap-6">

            <!-- Status Detail Card -->
            <div class="bg-white rounded-3xl border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] p-6 md:p-8">
                <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    Informasi Status
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Status Badge -->
                    <div class="bg-slate-50/70 p-4 rounded-2xl border border-slate-100 md:col-span-2">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Status Posko</p>
                        @php
                            $statusBg = 'bg-slate-100 text-slate-600 border-slate-200';
                            $statusIcon = 'fa-circle';
                            if($status->status === 'approved') {
                                $statusBg = 'bg-emerald-100 text-emerald-700 border-emerald-200';
                                $statusIcon = 'fa-check-circle';
                            } elseif($status->status === 'pending') {
                                $statusBg = 'bg-amber-100 text-amber-700 border-amber-200';
                                $statusIcon = 'fa-clock';
                            } elseif($status->status === 'rejected') {
                                $statusBg = 'bg-rose-100 text-rose-700 border-rose-200';
                                $statusIcon = 'fa-times-circle';
                            } elseif($status->status === 'done') {
                                $statusBg = 'bg-blue-100 text-blue-700 border-blue-200';
                                $statusIcon = 'fa-flag-checkered';
                            }
                        @endphp
                        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold border {{ $statusBg }}">
                            <i class="fas {{ $statusIcon }}"></i>
                            {{ ucfirst($status->status) }}
                        </span>
                    </div>

                    <!-- Tanggal Dibuat -->
                    <div class="bg-slate-50/70 p-4 rounded-2xl border border-slate-100">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Tanggal Dibuat</p>
                        <p class="text-sm font-bold text-slate-700">{{ $status->created_at->translatedFormat('d M Y') }}</p>
                        <p class="text-xs text-slate-500 font-medium">{{ $status->created_at->format('H:i') }} WIB</p>
                    </div>

                    <!-- Terakhir Diupdate -->
                    <div class="bg-slate-50/70 p-4 rounded-2xl border border-slate-100">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Terakhir Diperbarui</p>
                        <p class="text-sm font-bold text-slate-700">{{ $status->updated_at->translatedFormat('d M Y') }}</p>
                        <p class="text-xs text-slate-500 font-medium">{{ $status->updated_at->format('H:i') }} WIB</p>
                    </div>

                    <!-- Catatan -->
                    <div class="bg-slate-50/70 p-4 rounded-2xl border border-slate-100 md:col-span-2">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Catatan / Keterangan</p>
                        <p class="text-sm font-medium text-slate-700 leading-relaxed">
                            {{ $status->catatan ?? '—' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Delete Action Card -->
            <div class="bg-white rounded-3xl border border-rose-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] p-6 md:p-8">
                <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                        <i class="fas fa-trash-alt"></i>
                    </div>
                    Zona Berbahaya
                </h3>
                <p class="text-sm text-slate-500 font-medium mb-4">Menghapus log status ini bersifat permanen dan tidak dapat dibatalkan.</p>
                <form action="{{ route('admin.status-lokasi.destroy', $status->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus log status ini?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center gap-2 bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white font-bold px-5 py-3 rounded-xl border border-rose-200 hover:border-rose-600 transition-all text-sm">
                        <i class="fas fa-trash-alt text-sm"></i> Hapus Log Status Ini
                    </button>
                </form>
            </div>

        </div>

        <!-- Right Column: Info Lokasi -->
        <div class="flex flex-col gap-6">

            <!-- Info Lokasi Card -->
            <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-3xl shadow-lg p-1 relative overflow-hidden">
                <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMiIgY3k9IjIiIHI9IjIiIGZpbGw9InJnYmEoMjU1LDI1NSwyNTUsMC4wNykiLz48L3N2Zz4=')]"></div>

                <div class="bg-white/10 backdrop-blur-md rounded-[22px] border border-white/20 p-6 relative z-10">
                    <h3 class="text-lg font-bold text-white mb-2 flex items-center gap-2">
                        <i class="fas fa-campground text-blue-200"></i> Info Posko
                    </h3>
                    <p class="text-blue-100/70 text-xs mb-6 font-medium">Data posko terkait log status ini.</p>

                    <div class="space-y-3">
                        <div class="bg-white/5 border border-white/10 rounded-xl p-3 hover:bg-white/10 transition-colors">
                            <p class="text-[10px] text-blue-200 font-bold uppercase tracking-wider mb-1">Nama Lokasi</p>
                            <p class="text-white font-black text-sm">{{ $status->lokasi->nama_lokasi ?? '-' }}</p>
                        </div>
                        <div class="bg-white/5 border border-white/10 rounded-xl p-3 hover:bg-white/10 transition-colors">
                            <p class="text-[10px] text-blue-200 font-bold uppercase tracking-wider mb-1">Wilayah Desa</p>
                            <p class="text-white font-black text-sm">{{ $status->lokasi->desa->nama_desa ?? '-' }}</p>
                        </div>
                        <div class="bg-white/5 border border-white/10 rounded-xl p-3 hover:bg-white/10 transition-colors">
                            <p class="text-[10px] text-blue-200 font-bold uppercase tracking-wider mb-1">Kapasitas</p>
                            <p class="text-white font-black text-sm">{{ number_format($status->lokasi->kapasitas_pengungsi ?? 0, 0, ',', '.') }} Jiwa</p>
                        </div>
                    </div>

                    <a href="{{ route('admin.lokasi.show', $status->lokasi_id) }}" class="mt-5 w-full inline-flex items-center justify-center gap-2 bg-white/15 hover:bg-white/25 text-white font-bold px-4 py-2.5 rounded-xl border border-white/20 transition-all text-sm">
                        <i class="fas fa-external-link-alt text-xs"></i> Lihat Detail Posko
                    </a>
                </div>
            </div>

            <!-- Status Guide Card -->
            <div class="bg-white rounded-3xl border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] p-6">
                <h3 class="text-md font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-lightbulb text-amber-500"></i> Panduan Status
                </h3>
                <div class="space-y-2.5">
                    <div class="flex items-center gap-3 p-2.5 rounded-xl bg-amber-50/50 border border-amber-100">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-400 flex-shrink-0"></span>
                        <div>
                            <p class="text-xs font-bold text-amber-700">Pending</p>
                            <p class="text-[11px] text-slate-500 font-medium">Menunggu verifikasi</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-2.5 rounded-xl bg-emerald-50/50 border border-emerald-100">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 flex-shrink-0"></span>
                        <div>
                            <p class="text-xs font-bold text-emerald-700">Approved</p>
                            <p class="text-[11px] text-slate-500 font-medium">Posko resmi beroperasi</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-2.5 rounded-xl bg-rose-50/50 border border-rose-100">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500 flex-shrink-0"></span>
                        <div>
                            <p class="text-xs font-bold text-rose-700">Rejected</p>
                            <p class="text-[11px] text-slate-500 font-medium">Ditolak / tidak disetujui</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-2.5 rounded-xl bg-blue-50/50 border border-blue-100">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500 flex-shrink-0"></span>
                        <div>
                            <p class="text-xs font-bold text-blue-700">Done</p>
                            <p class="text-[11px] text-slate-500 font-medium">Selesai / posko ditutup</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection