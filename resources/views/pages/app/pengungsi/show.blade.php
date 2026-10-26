@extends('layouts.dashboard')

@section('content')

<div class="max-w-5xl mx-auto py-10 px-6">

    <!-- CARD -->
    <div class="bg-white rounded-[32px]
                shadow-sm
                border border-slate-200
                overflow-hidden">

        <!-- FOTO -->
        @if($pengungsi->foto)

        <img src="{{ asset('storage/' . $pengungsi->foto) }}"
             class="w-full h-[420px] object-cover">

        @else

        <div class="w-full h-[420px]
                    bg-slate-100
                    flex items-center justify-center">

            <i class="fas fa-user text-7xl text-slate-400"></i>

        </div>

        @endif

        <!-- CONTENT -->
        <div class="p-8 md:p-10">

            <!-- HEADER -->
            <div class="flex items-center justify-between flex-wrap gap-4">

                <div>

                    <p class="uppercase tracking-[0.2em]
                              text-sm text-slate-400 mb-2">

                        Data Pengungsi

                    </p>

                    <h1 class="text-4xl font-black text-slate-900">
                        {{ $pengungsi->nama }}
                    </h1>

                </div>

                <!-- BUTTON -->
                <a href="{{ route('pengungsi.index', $pengungsi->lokasi_id) }}"
                   class="px-6 py-3 rounded-2xl
                          border border-slate-200
                          hover:bg-slate-100
                          text-slate-700
                          font-semibold transition">

                    ← Kembali

                </a>

            </div>

            <!-- DETAIL -->
            <div class="mt-10 grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- ASAL -->
                <div class="bg-slate-50 rounded-2xl p-5">

                    <p class="text-sm text-slate-500 mb-2">
                        Asal Daerah
                    </p>

                    <h3 class="text-lg font-bold text-slate-800">
                        {{ $pengungsi->asal }}
                    </h3>

                </div>

                <!-- TANGGAL LAHIR -->
                <div class="bg-slate-50 rounded-2xl p-5">

                    <p class="text-sm text-slate-500 mb-2">
                        Tanggal Lahir
                    </p>

                    <h3 class="text-lg font-bold text-slate-800">
                        {{ $pengungsi->tanggal_lahir }}
                    </h3>

                </div>

                <!-- NOMOR KK -->
                <div class="bg-slate-50 rounded-2xl p-5">

                    <p class="text-sm text-slate-500 mb-2">
                        Nomor Kartu Keluarga
                    </p>

                    <h3 class="text-lg font-bold text-slate-800">
                        {{ $pengungsi->nomor_kk ?: 'Belum diketahui' }}
                    </h3>

                </div>

                <!-- USIA -->
                <div class="bg-slate-50 rounded-2xl p-5">

                    <p class="text-sm text-slate-500 mb-2">
                        Usia
                    </p>

                    <h3 class="text-lg font-bold text-slate-800">
                        {{ $pengungsi->usia }} Tahun
                    </h3>

                </div>

                <!-- JK -->
                <div class="bg-slate-50 rounded-2xl p-5">

                    <p class="text-sm text-slate-500 mb-2">
                        Jenis Kelamin
                    </p>

                    <h3 class="text-lg font-bold text-slate-800">
                        {{ $pengungsi->jenis_kelamin }}
                    </h3>

                </div>

                <!-- KESEHATAN -->
                <div class="bg-slate-50 rounded-2xl p-5">

                    <p class="text-sm text-slate-500 mb-2">
                        Kondisi Kesehatan
                    </p>

                    <h3 class="text-lg font-bold text-slate-800">
                        {{ $pengungsi->kondisi_kesehatan ?? '-' }}
                    </h3>

                </div>

                <!-- RENTAN -->
                <div class="bg-slate-50 rounded-2xl p-5">

                    <p class="text-sm text-slate-500 mb-2">
                        Kelompok Rentan
                    </p>

                    <h3 class="text-lg font-bold text-slate-800">
                        {{ $pengungsi->kelompok_rentan ?? '-' }}
                    </h3>

                </div>

                <!-- RIWAYAT -->
                <div class="bg-slate-50 rounded-2xl p-5 md:col-span-2">

                    <p class="text-sm text-slate-500 mb-2">
                        Riwayat Penyakit
                    </p>

                    <h3 class="text-lg font-bold text-slate-800 leading-relaxed">
                        {{ $pengungsi->riwayat_penyakit ?? '-' }}
                    </h3>

                </div>

                @if ($pengungsi->dicatat_oleh)
                <!-- PENCATAT (dari aplikasi offline) -->
                <div class="bg-slate-50 rounded-2xl p-5 md:col-span-2">

                    <p class="text-sm text-slate-500 mb-2">
                        Dicatat oleh
                    </p>

                    <h3 class="text-lg font-bold text-slate-800">
                        {{ $pengungsi->dicatat_oleh }}
                        <span class="text-sm font-medium text-slate-500">(aplikasi offline)</span>
                    </h3>

                </div>
                @endif

            </div>

        </div>

    </div>

</div>

@endsection