@extends('layouts.admin')

@section('title', 'Edit Logistik')

@section('content')
@php
    $input = 'w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all';
    $inputError = 'border-rose-500 focus:ring-rose-500/20 focus:border-rose-500';
@endphp

<div class="relative z-10 container mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10 min-h-screen flex flex-col gap-8">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white/60 backdrop-blur-xl p-6 md:p-8 rounded-3xl border border-white/80 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-amber-50 border border-amber-100/50 text-amber-600 text-xs font-bold tracking-wide uppercase mb-3">
                <i class="fas fa-edit opacity-70"></i>
                Master Logistik
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 tracking-tight">
                Edit Item Logistik
            </h1>
            <p class="text-slate-500 mt-1 font-medium text-sm">Perbarui data <b class="text-slate-700">{{ $logistik->nama_item }}</b>. Perubahan berlaku untuk perhitungan stok di semua posko.</p>
        </div>

        <div>
            <a href="{{ route('admin.logistik.index') }}" class="inline-flex items-center justify-center gap-2 bg-white hover:bg-slate-50 text-slate-600 font-bold px-5 py-3 rounded-xl shadow-sm border border-slate-200 transition-all hover:-translate-x-1">
                <i class="fas fa-arrow-left text-sm opacity-70"></i> Batal / Kembali
            </a>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden max-w-3xl w-full">
        <div class="p-6 md:p-8">
            <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i class="fas fa-boxes"></i>
                </div>
                Data Item
            </h3>

            <form action="{{ route('admin.logistik.update', $logistik->id) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- Kategori -->
                <div class="space-y-2">
                    <label for="kategori_logistik_id" class="block text-sm font-bold text-slate-700">Kategori <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                            <i class="fas fa-layer-group text-slate-400"></i>
                        </div>
                        <select name="kategori_logistik_id" id="kategori_logistik_id" required
                                class="{{ $input }} appearance-none @error('kategori_logistik_id') {{ $inputError }} @enderror">
                            @foreach ($kategoris as $kategori)
                                <option value="{{ $kategori->id }}" @selected(old('kategori_logistik_id', $logistik->kategori_logistik_id) == $kategori->id)>
                                    {{ $kategori->nama_kategori }}
                                </option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none z-10">
                            <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                        </div>
                    </div>
                    @error('kategori_logistik_id')
                        <p class="mt-1.5 text-xs font-bold text-rose-500 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                    @enderror
                </div>

                <!-- Nama Item -->
                <div class="space-y-2">
                    <label for="nama_item" class="block text-sm font-bold text-slate-700">Nama Item <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                            <i class="fas fa-tag text-slate-400"></i>
                        </div>
                        <input type="text" id="nama_item" name="nama_item" value="{{ old('nama_item', $logistik->nama_item) }}" required
                               placeholder="Contoh: Beras, Air Mineral, Selimut"
                               class="{{ $input }} @error('nama_item') {{ $inputError }} @enderror">
                    </div>
                    @error('nama_item')
                        <p class="mt-1.5 text-xs font-bold text-rose-500 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Satuan -->
                    <div class="space-y-2">
                        <label for="satuan" class="block text-sm font-bold text-slate-700">Satuan <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                                <i class="fas fa-ruler text-slate-400"></i>
                            </div>
                            <input type="text" id="satuan" name="satuan" value="{{ old('satuan', $logistik->satuan) }}" required
                                   placeholder="Contoh: liter, kg, dus"
                                   class="{{ $input }} @error('satuan') {{ $inputError }} @enderror">
                        </div>
                        @error('satuan')
                            <p class="mt-1.5 text-xs font-bold text-rose-500 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Kebutuhan Harian -->
                    <div class="space-y-2">
                        <label for="kebutuhan_harian" class="block text-sm font-bold text-slate-700">Kebutuhan Harian</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                                <i class="fas fa-calendar-day text-slate-400"></i>
                            </div>
                            <input type="number" step="0.01" min="0" id="kebutuhan_harian" name="kebutuhan_harian" value="{{ old('kebutuhan_harian', $logistik->kebutuhan_harian) }}"
                                   placeholder="Contoh: 0.4"
                                   class="{{ $input }} @error('kebutuhan_harian') {{ $inputError }} @enderror">
                        </div>
                        <p class="text-xs text-slate-500">Kebutuhan rata-rata per orang per hari (dalam satuan di samping).</p>
                        @error('kebutuhan_harian')
                            <p class="mt-1.5 text-xs font-bold text-rose-500 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 pt-6 border-t border-slate-100">
                    <a href="{{ route('admin.logistik.index') }}"
                       class="inline-flex items-center justify-center gap-2 bg-white hover:bg-slate-50 text-slate-600 font-bold px-6 py-3 rounded-xl border border-slate-200 transition-all">
                        Batal
                    </a>
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-2 bg-gradient-to-tr from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white font-bold px-8 py-3 rounded-xl shadow-lg shadow-amber-500/30 transition-all hover:-translate-y-0.5">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
