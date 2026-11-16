@extends('layouts.admin')

@section('title', 'Edit Status Lokasi')

@section('content')
<div class="relative z-10 container mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10 min-h-screen flex flex-col gap-8">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white/60 backdrop-blur-xl p-6 md:p-8 rounded-3xl border border-white/80 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-amber-50 border border-amber-100/50 text-amber-600 text-xs font-bold tracking-wide uppercase mb-3">
                <i class="fas fa-edit opacity-70"></i>
                Pembaruan Status
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 tracking-tight">
                Edit Status Lokasi
            </h1>
            <p class="text-slate-500 mt-1 font-medium text-sm">Perbarui rekam jejak status posko pengungsian.</p>
        </div>

        <div>
            <a href="{{ route('admin.lokasi.show', $status->lokasi_id) }}" class="inline-flex items-center justify-center gap-2 bg-white hover:bg-slate-50 text-slate-600 font-bold px-5 py-3 rounded-xl shadow-sm border border-slate-200 transition-all hover:-translate-x-1">
                <i class="fas fa-arrow-left text-sm opacity-70"></i> Batal / Kembali
            </a>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden">

        <div class="p-6 md:p-8">
            <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                Formulir Pembaruan Status
            </h3>

            <form action="{{ route('admin.status-lokasi.update', $status->id) }}" method="POST" class="space-y-8" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" name="lokasi_id" value="{{ $status->lokasi_id }}">

                <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
                    <!-- Left Column: Status & Catatan -->
                    <div class="space-y-6 bg-slate-50/50 rounded-2xl border border-slate-100 p-6">
                        <h4 class="text-sm font-bold text-slate-800 uppercase tracking-widest flex items-center gap-2 mb-4 border-b border-slate-200 pb-2">
                            <i class="fas fa-info-circle text-amber-500"></i> Detail Status
                        </h4>

                        <!-- Status Field -->
                        <div class="space-y-2">
                            <label for="status" class="block text-sm font-bold text-slate-700">Status Posko <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                                    <i class="fas fa-flag text-slate-400"></i>
                                </div>
                                <select
                                    name="status"
                                    id="status"
                                    class="w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all appearance-none @error('status') border-rose-500 focus:ring-rose-500/20 focus:border-rose-500 @enderror"
                                >
                                    <option value="pending" @if (old('status', $status->status) == 'pending') selected @endif>Pending</option>
                                    <option value="approved" @if (old('status', $status->status) == 'approved') selected @endif>Approved</option>
                                    <option value="rejected" @if (old('status', $status->status) == 'rejected') selected @endif>Rejected</option>
                                    <option value="done" @if (old('status', $status->status) == 'done') selected @endif>Done</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none z-10">
                                    <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                                </div>
                            </div>
                            @error('status')
                                <p class="mt-1.5 text-xs font-bold text-rose-500 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Catatan Field -->
                        <div class="space-y-2">
                            <label for="catatan" class="block text-sm font-bold text-slate-700">Catatan / Keterangan</label>
                            <div class="relative">
                                <div class="absolute top-3.5 left-0 pl-4 flex items-start pointer-events-none z-10">
                                    <i class="fas fa-sticky-note text-slate-400 mt-0.5"></i>
                                </div>
                                <textarea id="catatan" name="catatan" rows="4"
                                    class="w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all @error('catatan') border-rose-500 focus:ring-rose-500/20 focus:border-rose-500 @enderror"
                                    placeholder="Tulis catatan atau keterangan tambahan untuk status ini...">{{ old('catatan', $status->catatan) }}</textarea>
                            </div>
                            @error('catatan')
                                <p class="mt-1.5 text-xs font-bold text-rose-500 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Right Column: Info & Status History -->
                    <div class="space-y-6">
                        <!-- Status Saat Ini -->
                        <div class="bg-amber-50/50 rounded-2xl border border-amber-100 p-6">
                            <h4 class="text-sm font-bold text-slate-800 uppercase tracking-widest flex items-center gap-2 mb-4 border-b border-amber-200/60 pb-2">
                                <i class="fas fa-history text-amber-500"></i> Data Saat Ini
                            </h4>
                            <div class="space-y-3">
                                <div class="bg-white rounded-xl p-4 border border-amber-100 shadow-sm">
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Status Sekarang</p>
                                    @php
                                        $badgeBg = 'bg-slate-100 text-slate-600';
                                        if($status->status === 'approved') $badgeBg = 'bg-emerald-100 text-emerald-700 border border-emerald-200';
                                        elseif($status->status === 'pending') $badgeBg = 'bg-amber-100 text-amber-700 border border-amber-200';
                                        elseif($status->status === 'rejected') $badgeBg = 'bg-rose-100 text-rose-700 border border-rose-200';
                                        elseif($status->status === 'done') $badgeBg = 'bg-blue-100 text-blue-700 border border-blue-200';
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold {{ $badgeBg }}">
                                        {{ ucfirst($status->status) }}
                                    </span>
                                </div>
                                <div class="bg-white rounded-xl p-4 border border-amber-100 shadow-sm">
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Dibuat Pada</p>
                                    <p class="text-sm font-bold text-slate-700">{{ $status->created_at->translatedFormat('d M Y, H:i') }}</p>
                                </div>
                                <div class="bg-white rounded-xl p-4 border border-amber-100 shadow-sm">
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Catatan Sebelumnya</p>
                                    <p class="text-sm font-medium text-slate-600">{{ $status->catatan ?? '-' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Panduan Status -->
                        <div class="bg-slate-50/50 rounded-2xl border border-slate-100 p-6">
                            <h4 class="text-sm font-bold text-slate-800 uppercase tracking-widest flex items-center gap-2 mb-3 border-b border-slate-200 pb-2">
                                <i class="fas fa-lightbulb text-slate-500"></i> Panduan Status
                            </h4>
                            <div class="space-y-2 text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-amber-400 flex-shrink-0"></span>
                                    <span class="text-slate-600 font-medium"><strong class="text-slate-700">Pending</strong> — Menunggu verifikasi.</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 flex-shrink-0"></span>
                                    <span class="text-slate-600 font-medium"><strong class="text-slate-700">Approved</strong> — Posko resmi beroperasi.</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-rose-500 flex-shrink-0"></span>
                                    <span class="text-slate-600 font-medium"><strong class="text-slate-700">Rejected</strong> — Ditolak / tidak disetujui.</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-blue-500 flex-shrink-0"></span>
                                    <span class="text-slate-600 font-medium"><strong class="text-slate-700">Done</strong> — Selesai / posko ditutup.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end pt-4 mt-4 border-t border-slate-100">
                    <button type="submit" class="inline-flex items-center gap-2 bg-gradient-to-tr from-amber-500 to-orange-400 hover:from-amber-600 hover:to-orange-500 text-white font-bold px-8 py-4 rounded-xl shadow-lg shadow-amber-500/30 transform transition-all hover:-translate-y-1 border border-amber-400/20 text-lg">
                        <i class="fas fa-save"></i> Simpan & Perbarui Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection