@extends('layouts.dashboard')

@section('title', 'Catat Barang Masuk')

@section('content')

<div class="bg-slate-50 py-10 px-4 rounded-3xl">

    <div class="max-w-2xl mx-auto">

        {{-- CARD --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

            {{-- HEADER --}}
            <div class="px-8 py-6 border-b border-slate-100">

                <h1 class="text-3xl font-bold text-slate-800">
                    Catat Barang Masuk
                </h1>

                <p class="text-slate-500 mt-2">
                    Isi setiap kali ada bantuan atau barang yang datang ke posko
                    <span class="font-semibold text-slate-700">
                        {{ $lokasi->nama_lokasi }}
                    </span>
                </p>

            </div>

            {{-- CONTENT --}}
            <div class="p-8">

                {{-- ALERT SUCCESS --}}
                @if(session('success'))

                    <div class="mb-6 px-4 py-3 rounded-xl
                                bg-green-100 text-green-700
                                border border-green-200">

                        {{ session('success') }}

                    </div>

                @endif

                @if ($errors->any())
                    <div class="mb-6 px-4 py-3 rounded-xl bg-rose-50 text-rose-700 border border-rose-200 text-sm">
                        @foreach ($errors->all() as $e)
                            <p><i class="fas fa-circle-exclamation mr-1"></i>{{ $e }}</p>
                        @endforeach
                    </div>
                @endif

                {{-- FORM --}}
                <form action="{{ route('stok-masuk.store') }}"
                      method="POST"
                      class="space-y-6">

                    @csrf

                    <input type="hidden"
                           name="lokasi_id"
                           value="{{ $lokasi->id }}">

                    {{-- ITEM LOGISTIK --}}
                    <div>

                        <label class="block mb-2 text-sm font-semibold text-slate-700">
                            Item Logistik
                        </label>

                        <select name="logistik_id"
                                class="w-full rounded-xl border border-slate-300
                                       px-4 py-3
                                       focus:ring-2 focus:ring-red-500
                                       focus:border-red-500
                                       outline-none transition"
                                required>

                            <option value="">
                                -- Pilih Item --
                            </option>

                            @foreach($logistiks as $item)

                                <option value="{{ $item->id }}" @selected(old('logistik_id') == $item->id)>

                                    {{ $item->nama_item }}
                                    ({{ $item->satuan }})

                                </option>

                            @endforeach

                        </select>

                    </div>

                    {{-- JUMLAH --}}
                    <div>

                        <label class="block mb-2 text-sm font-semibold text-slate-700">
                            Jumlah Masuk
                        </label>

                        <input type="number"
                               name="jumlah_masuk"
                               value="{{ old('jumlah_masuk') }}" min="1" inputmode="numeric"
                               placeholder="Masukkan jumlah bantuan"
                               class="w-full rounded-xl border border-slate-300
                                      px-4 py-3
                                      focus:ring-2 focus:ring-red-500
                                      focus:border-red-500
                                      outline-none transition"
                               required>

                    </div>

                    {{-- SUMBER --}}
                    <div>

                        <label class="block mb-2 text-sm font-semibold text-slate-700">
                            Sumber Bantuan
                        </label>

                        <input type="text"
                               name="sumber_bantuan"
                               value="{{ old('sumber_bantuan') }}"
                               placeholder="Contoh: BNPB, Donatur, PMI"
                               class="w-full rounded-xl border border-slate-300
                                      px-4 py-3
                                      focus:ring-2 focus:ring-red-500
                                      focus:border-red-500
                                      outline-none transition"
                               required>

                    </div>

                    {{-- TANGGAL --}}
                    <div>

                        <label class="block mb-2 text-sm font-semibold text-slate-700">
                            Tanggal Masuk
                        </label>

                        <input type="date"
                               name="tanggal_masuk"
                               value="{{ old('tanggal_masuk', now()->toDateString()) }}" max="{{ now()->toDateString() }}"
                               class="w-full rounded-xl border border-slate-300
                                      px-4 py-3
                                      focus:ring-2 focus:ring-red-500
                                      focus:border-red-500
                                      outline-none transition"
                               required>

                    </div>

                    {{-- KETERANGAN --}}
                    <div>

                        <label class="block mb-2 text-sm font-semibold text-slate-700">
                            Keterangan
                        </label>

                        <textarea name="keterangan"
                                  rows="4"
                                  placeholder="Tambahkan catatan tambahan..."
                                  class="w-full rounded-xl border border-slate-300
                                         px-4 py-3
                                         focus:ring-2 focus:ring-red-500
                                         focus:border-red-500
                                         outline-none transition"></textarea>

                    </div>

                    {{-- BUTTON --}}
                    <div class="pt-4 border-t border-slate-200">

                        <div class="flex items-center justify-end gap-3">

                            {{-- KEMBALI --}}
                            <a href="{{ route('logistik.index', $lokasi->id) }}"
                               class="px-5 py-3 rounded-xl
                                      border border-slate-300
                                      bg-white hover:bg-slate-100
                                      text-slate-700 font-semibold
                                      transition-all duration-200">

                                Kembali

                            </a>

                            {{-- SUBMIT --}}
                            <button type="submit"
                                    class="px-6 py-3 rounded-xl
                                           bg-red-600 hover:bg-red-700
                                           text-white font-semibold
                                           shadow-md hover:shadow-lg
                                           transition-all duration-200">

                                Simpan Barang Masuk

                            </button>

                        </div>

                    </div>

                </form>

            </div>

        </div>

    </div>

</div>

@endsection