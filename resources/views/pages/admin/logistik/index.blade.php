@extends('layouts.admin')

@section('title', 'Master Logistik')

@section('content')

<div class="container mx-auto px-6 py-8">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">

        <div>

            <h1 class="text-3xl font-bold text-slate-800">
                Master Logistik
            </h1>

            <p class="text-slate-500 mt-1">
                Kelola item logistik bantuan
            </p>

        </div>

        <div class="flex flex-wrap items-center gap-3">

            @include('includes.admin-import-export', [
                'judul'         => 'Master Logistik',
                'keterangan'    => 'Item dengan nama & kategori yang sama akan diperbarui, bukan dibuat ganda.',
                'templateRoute' => route('admin.logistik.import.template'),
                'previewRoute'  => route('admin.logistik.import.preview'),
                'exportRoute'   => route('admin.logistik.export'),
                'exportCsvRoute'=> route('admin.logistik.export.csv'),
                'templateFile'  => 'template_import_logistik.xlsx',
            ])

            <a href="{{ route('admin.logistik.create') }}"
               class="px-5 py-3 bg-red-600 hover:bg-red-700
               text-white rounded-xl font-semibold shadow">

                + Tambah Item

            </a>

        </div>

    </div>

    @if(session('error'))
        <div class="bg-rose-100 text-rose-700 p-4 rounded-xl mb-5">
            <i class="fas fa-exclamation-triangle mr-1"></i> {{ session('error') }}
        </div>
    @endif

    @if(session('success'))

        <div class="bg-green-100 text-green-700 p-4 rounded-xl mb-5">
            {{ session('success') }}
        </div>

    @endif

    <div class="bg-white rounded-2xl shadow border border-slate-200 overflow-hidden">

        <table class="w-full table-fixed">

            <thead class="bg-slate-100">

                <tr>

                    <th class="px-6 py-4 text-center">
                        Nama Item
                    </th>

                    <th class="px-6 py-4 text-center">
                        Satuan
                    </th>

                    <th class="px-6 py-4 text-center">
                        Kebutuhan Harian
                    </th>

                    <th class="px-6 py-4 text-center">
                        Aksi
                    </th>

                </tr>

            </thead>

            <tbody>

                @forelse($logistiks as $item)

                <tr class="border-t border-slate-100">

                    <td class="px-6 py-4 text-center font-medium">
                        {{ $item->nama_item }}
                    </td>

                    <td class="px-6 py-4 text-center">
                        {{ $item->satuan }}
                    </td>

                    <td class="px-6 py-4 text-center">
                        {{ $item->kebutuhan_harian ?? '-' }}
                    </td>

                    <td class="px-6 py-4">

                        <div class="flex items-center justify-center gap-2">

                            <a href="{{ route('admin.logistik.edit', $item->id) }}"
                               class="px-4 py-2 bg-blue-500 hover:bg-blue-600
                               text-white rounded-lg text-sm">

                                Edit

                            </a>

                            <form action="{{ route('admin.logistik.destroy', $item->id) }}"
                                  method="POST">

                                @csrf
                                @method('DELETE')

                                <button type="submit"
                                        onclick="return confirm('Hapus item ini?')"
                                        class="px-4 py-2 bg-red-500 hover:bg-red-600
                                        text-white rounded-lg text-sm">

                                    Hapus

                                </button>

                            </form>

                        </div>

                    </td>

                </tr>

                @empty

                <tr>

                    <td colspan="4"
                        class="px-6 py-8 text-center text-slate-400">

                        Belum ada item logistik

                    </td>

                </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

@endsection
