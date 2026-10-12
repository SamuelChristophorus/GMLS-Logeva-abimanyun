@extends('layouts.app')

@section('title', 'Monitoring Logistik')

@section('content')

<div class="container mx-auto px-6 py-8">

    <div class="flex items-center justify-between mb-8">

        <div>
            <h1 class="text-3xl font-bold text-slate-800">
                Monitoring Logistik
            </h1>

            <p class="text-slate-500 mt-2">
                {{ $lokasi->nama_lokasi }}
            </p>
        </div>

        <!-- BUTTON GROUP -->
        <div class="flex items-center gap-3">

            <a href="{{ route('logistik.create', $lokasi->id) }}"
               class="px-5 py-3 bg-orange-500 hover:bg-orange-600
               text-white rounded-xl font-semibold shadow transition">
                Tambah Donasi
            </a>

            <a href="{{ route('lokasi.show', $lokasi->id) }}"
               class="px-5 py-3 bg-slate-600 hover:bg-slate-700
               text-white rounded-xl font-semibold shadow transition">
                Kembali
            </a>

        </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">

    {{-- KRITIS --}}
    <div class="bg-red-50 border border-red-200 rounded-2xl p-5">

        <p class="text-sm text-red-500 font-semibold">
            Item Kritis
        </p>

        <h2 class="text-3xl font-bold text-red-700 mt-2">
            {{ $jumlahKritis }}
        </h2>

        <p class="text-xs text-red-400 mt-1">
            Harus segera diprioritaskan
        </p>

    </div>

    {{-- WASPADA --}}
    <div class="bg-yellow-50 border border-yellow-200 rounded-2xl p-5">

        <p class="text-sm text-yellow-600 font-semibold">
            Item Waspada
        </p>

        <h2 class="text-3xl font-bold text-yellow-700 mt-2">
            {{ $jumlahWaspada }}
        </h2>

        <p class="text-xs text-yellow-500 mt-1">
            Perlu pemantauan
        </p>

    </div>

    {{-- RATA-RATA --}}
    <div class="bg-blue-50 border border-blue-200 rounded-2xl p-5">

        <p class="text-sm text-blue-600 font-semibold">
            Rata-rata Hari Bertahan
        </p>

        <h2 class="text-3xl font-bold text-blue-700 mt-2">
            {{ $rataHariBertahan }}
        </h2>

        <p class="text-xs text-blue-500 mt-1">
            Hari
        </p>

    </div>

    {{-- AMAN --}}
    <div class="bg-green-50 border border-green-200 rounded-2xl p-5">

        <p class="text-sm text-green-600 font-semibold">
            Item Aman
        </p>

        <h2 class="text-3xl font-bold text-green-700 mt-2">
            {{ $jumlahAman }}
        </h2>

        <p class="text-xs text-green-500 mt-1">
            Kondisi logistik aman
        </p>

    </div>

</div>
<p>{{ $lokasi->kapasitas }}</p>
{{-- STOK --}}
<div class="bg-white rounded-2xl shadow border border-slate-200 overflow-hidden mb-8">
    {{-- STOK --}}
    <div class="bg-white rounded-2xl shadow border border-slate-200 overflow-hidden mb-8">

        <div class="px-6 py-4 border-b border-slate-100">
            <h2 class="font-bold text-slate-700">
                Stok Logistik Saat Ini
            </h2>
        </div>

        <div class="overflow-x-auto">

            <table class="w-full table-fixed">

    <thead class="bg-slate-100">

        <tr>

             <th class="px-6 py-4 text-center text-sm font-bold text-slate-700">
            Item
        </th>

        <th class="px-6 py-4 text-center text-sm font-bold text-slate-700">
            Stok
        </th>

        <th class="px-6 py-4 text-center text-sm font-bold text-slate-700">
            Kebutuhan/Hari
        </th>

        <th class="px-6 py-4 text-center text-sm font-bold text-slate-700">
            Hari Bertahan
        </th>

         <th class="px-6 py-4 text-center text-sm font-bold text-slate-700">
        Persentase Kecukupan
        </th>

        <th class="px-6 py-4 text-center text-sm font-bold text-slate-700">
            Status
        </th>

            

        </tr>

    </thead>

    <tbody>

        @forelse($stokLogistiks as $stok)

        @php

        $kebutuhanPerHari = optional($stok->logistik)->kebutuhan_harian ?? 0;

        /*
        Khusus item yang memakai standar SPHERE
        */
        if(strtolower(optional($stok->logistik)->nama_item) == 'beras')
        {
            $kebutuhanPerHari =
            round(
            ($lokasi->sphereLokasi->kalori / 180 * 100) / 1000, 2
            );
        }

        elseif(strtolower(optional($stok->logistik)->nama_item) == 'air mineral')
        {
            $kebutuhanPerHari =
            $lokasi->sphereLokasi->air_hidup +
            $lokasi->sphereLokasi->air_kebersihan +
            $lokasi->sphereLokasi->air_memasak;
        }

        $hariBertahan =
        $kebutuhanPerHari > 0
        ? round($stok->jumlah_stok / $kebutuhanPerHari, 2)
        : 0;

        @endphp

        <tr class="border-t border-slate-100 hover:bg-slate-50 transition">

            <td class="px-6 py-5 text-center font-medium text-slate-700">
                {{ optional($stok->logistik)->nama_item ?? 'Data tidak ditemukan' }}
            </td>

            <td class="px-6 py-5 text-center font-bold text-slate-800">
                {{ number_format($stok->jumlah_stok, 0, ',', '.') }}
                {{ optional($stok->logistik)->satuan }}
            </td>

            <td class="px-6 py-5 text-center">

             @if($kebutuhanPerHari > 0)

    {{ number_format($kebutuhanPerHari, 2, ',', '.') }}
    {{ optional($stok->logistik)->satuan }}/hari

@else

    -

@endif

</td>

<td class="px-6 py-5 text-center font-bold">

    @if($hariBertahan > 0)

        {{ $hariBertahan }} Hari

    @else

        -

    @endif

</td>
<td class="px-6 py-5 text-center">

    @php
        $persentaseKecukupan = min(
            round(($hariBertahan / 7) * 100, 2),
            999
        );
    @endphp

    @if($persentaseKecukupan >= 100)

        <span class="font-bold text-green-600">
            {{ $persentaseKecukupan }}%
        </span>

    @elseif($persentaseKecukupan >= 50)

        <span class="font-bold text-yellow-600">
            {{ $persentaseKecukupan }}%
        </span>

    @else

        <span class="font-bold text-red-600">
            {{ $persentaseKecukupan }}%
        </span>

    @endif

</td>

            <td class="px-6 py-5 text-center">

    @if($hariBertahan >= 14)

        <span class="px-3 py-1 rounded-lg bg-green-100 text-green-700 font-semibold">
            Surplus
        </span>

    @elseif($hariBertahan >= 7)

        <span class="px-3 py-1 rounded-lg bg-blue-100 text-blue-700 font-semibold">
            Aman
        </span>

    @elseif($hariBertahan >= 3)

        <span class="px-3 py-1 rounded-lg bg-yellow-100 text-yellow-700 font-semibold">
            Waspada
        </span>

    @else

        <span class="px-3 py-1 rounded-lg bg-red-100 text-red-700 font-semibold">
            Kritis
        </span>

    @endif

</td>

        </tr>

        @empty

        <tr>

             <td colspan="6"
             class="px-6 py-8 text-center text-slate-400">

                Belum ada stok logistik

            </td>

        </tr>

        @endforelse

    </tbody>

</table>

        </div>

    </div>

    {{-- RIWAYAT --}}
<div class="bg-white rounded-2xl shadow border border-slate-200 overflow-hidden">

    <div class="px-6 py-4 border-b border-slate-100">
        <h2 class="font-bold text-slate-700">
            Riwayat Donasi Masuk
        </h2>
    </div>

    <div class="overflow-x-auto">

        <table class="w-full table-fixed">

            {{-- HEADER --}}
            <thead class="bg-slate-100">

                <tr>

                    <th class="w-1/5 px-6 py-4 text-center text-sm font-bold text-slate-700">
                        Tanggal
                    </th>

                    <th class="w-1/5 px-6 py-4 text-center text-sm font-bold text-slate-700">
                        Item
                    </th>

                    <th class="w-1/5 px-6 py-4 text-center text-sm font-bold text-slate-700">
                        Jumlah
                    </th>

                    <th class="w-1/5 px-6 py-4 text-center text-sm font-bold text-slate-700">
                        Sumber Bantuan
                    </th>

                    <th class="w-1/5 px-6 py-4 text-center text-sm font-bold text-slate-700">
                        Keterangan
                    </th>

                </tr>

            </thead>

            {{-- BODY --}}
            <tbody>

                @forelse($riwayatMasuk as $item)

                <tr class="border-t border-slate-100 hover:bg-slate-50 transition">

                    {{-- TANGGAL --}}
                    <td class="px-6 py-5 text-center text-slate-700">

                        {{ $item->tanggal_masuk }}

                    </td>

                    {{-- ITEM --}}
                    <td class="px-6 py-5 text-center font-medium text-slate-700">

                        {{ optional($item->logistik)->nama_item ?? 'Data tidak ditemukan' }}

                    </td>

                    {{-- JUMLAH --}}
                    <td class="px-6 py-5 text-center font-bold text-slate-800">

                        {{ number_format($item->jumlah_masuk, 0, ',', '.') }}
                        {{ optional($item->logistik)->satuan }}

                    </td>

                    {{-- SUMBER --}}
                    <td class="px-6 py-5 text-center text-slate-700">

                        {{ $item->sumber_bantuan }}

                    </td>

                    {{-- KETERANGAN --}}
                    <td class="px-6 py-5 text-center text-slate-700">

                        {{ $item->keterangan ?? '-' }}

                    </td>

                </tr>

                @empty

                <tr>

                    <td colspan="5"
                        class="px-6 py-8 text-center text-slate-400">

                        Belum ada donasi masuk

                    </td>

                </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

@endsection