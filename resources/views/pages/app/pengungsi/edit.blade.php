@extends('layouts.dashboard')

@section('title', 'Edit Pengungsi')

@section('content')

<div class="bg-gradient-to-br from-slate-100 via-slate-50 to-white py-10">

    <div class="max-w-5xl mx-auto px-6">

        <!-- HEADER -->
        <div class="relative overflow-hidden rounded-[38px]
                    bg-gradient-to-br from-slate-900 via-slate-800 to-black
                    p-8 md:p-10 shadow-2xl">

            <div class="absolute -top-20 -right-20 w-72 h-72 bg-white/5 rounded-full blur-3xl"></div>

            <div class="relative z-10 flex items-center gap-6">

                <div class="w-20 h-20 rounded-[28px]
                            bg-white/10 border border-white/10
                            flex items-center justify-center">

                    <i class="fas fa-user-pen text-white text-3xl"></i>

                </div>

                <div>

                    <p class="text-slate-300 uppercase tracking-[0.2em] text-sm mb-3">
                        Manajemen Evakuasi
                    </p>

                    <h1 class="text-4xl font-black text-white">
                        Edit Data Pengungsi
                    </h1>

                    <p class="text-slate-400 mt-3">
                        Perbarui informasi data pengungsi secara realtime.
                    </p>

                </div>

            </div>

        </div>

        <!-- FORM -->
        <div class="mt-10 bg-white rounded-[38px]
                    border border-slate-200
                    shadow-sm overflow-hidden">

            <form action="{{ route('pengungsi.update', $pengungsi->id) }}"
                  method="POST"
                  enctype="multipart/form-data"
                  class="p-8">

                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-7">

                    <!-- FOTO -->
                    <div class="md:col-span-2">

                        <label class="block text-sm font-bold text-slate-700 mb-4">
                            Foto Pengungsi
                        </label>

                        <div class="border-2 border-dashed border-slate-200
                                    rounded-[28px]
                                    bg-slate-50
                                    p-8 text-center">

                            @if($pengungsi->foto)

                            <img src="{{ asset('storage/' . $pengungsi->foto) }}"
                                 id="preview-foto"
                                 class="w-48 h-48 object-cover rounded-[28px] mx-auto mb-6 shadow-xl">

                            @else

                            <img id="preview-foto"
                                 class="hidden w-48 h-48 object-cover rounded-[28px] mx-auto mb-6 shadow-xl">

                            @endif

                            <input type="file"
                                   name="foto"
                                   accept="image/*"
                                   onchange="previewImage(event)"
                                   class="w-full">

                        </div>

                    </div>

                    <!-- NAMA -->
                    <div>

                        <label class="block text-sm font-bold text-slate-700 mb-3">
                            Nama Lengkap
                        </label>

                        <input type="text"
                               name="nama"
                               value="{{ $pengungsi->nama }}"
                               required
                               class="w-full rounded-[22px]
                                      border border-slate-200
                                      px-5 py-4
                                      focus:ring-2 focus:ring-slate-900">

                    </div>

                    <!-- ASAL -->
                    <div>

                        <label class="block text-sm font-bold text-slate-700 mb-3">
                            Asal Daerah
                        </label>

                        <input type="text"
                               name="asal"
                               value="{{ $pengungsi->asal }}"
                               required
                               class="w-full rounded-[22px]
                                      border border-slate-200
                                      px-5 py-4
                                      focus:ring-2 focus:ring-slate-900">

                    </div>

                    <!-- TANGGAL LAHIR -->
                    <div>

                        <label class="block text-sm font-bold text-slate-700 mb-3">
                            Tanggal Lahir
                        </label>

                        <input type="date"
                               name="tanggal_lahir"
                               value="{{ $pengungsi->tanggal_lahir }}"
                               required
                               class="w-full rounded-[22px]
                                      border border-slate-200
                                      px-5 py-4
                                      focus:ring-2 focus:ring-slate-900">

                    </div>

                    <!-- NOMOR KK -->
                    <div>

                        <label class="block text-sm font-bold text-slate-700 mb-3">
                            Nomor Kartu Keluarga
                        </label>

                        <input type="text"
                               name="nomor_kk"
                               value="{{ $pengungsi->nomor_kk }}"
                               required
                               class="w-full rounded-[22px]
                                      border border-slate-200
                                      px-5 py-4
                                      focus:ring-2 focus:ring-slate-900">

                    </div>

                    <!-- USIA -->
                    <div>

                        <label class="block text-sm font-bold text-slate-700 mb-3">
                            Usia
                        </label>

                        <input type="number"
                               name="usia"
                               value="{{ $pengungsi->usia }}"
                               required
                               class="w-full rounded-[22px]
                                      border border-slate-200
                                      px-5 py-4
                                      focus:ring-2 focus:ring-slate-900">

                    </div>

                    <!-- JK -->
                    <div>

                        <label class="block text-sm font-bold text-slate-700 mb-3">
                            Jenis Kelamin
                        </label>

                        <select name="jenis_kelamin"
                                class="w-full rounded-[22px]
                                       border border-slate-200
                                       px-5 py-4
                                       focus:ring-2 focus:ring-slate-900">

                            <option value="Laki-laki"
                                {{ $pengungsi->jenis_kelamin == 'Laki-laki' ? 'selected' : '' }}>
                                Laki-laki
                            </option>

                            <option value="Perempuan"
                                {{ $pengungsi->jenis_kelamin == 'Perempuan' ? 'selected' : '' }}>
                                Perempuan
                            </option>

                        </select>

                    </div>

                    <!-- KONDISI -->
                    <div>

                        <label class="block text-sm font-bold text-slate-700 mb-3">
                            Kondisi Kesehatan
                        </label>

                        <input type="text"
                               name="kondisi_kesehatan"
                               value="{{ $pengungsi->kondisi_kesehatan }}"
                               class="w-full rounded-[22px]
                                      border border-slate-200
                                      px-5 py-4
                                      focus:ring-2 focus:ring-slate-900">

                    </div>

                    <!-- RENTAN -->
                    <div>

                        <label class="block text-sm font-bold text-slate-700 mb-3">
                            Kelompok Rentan
                        </label>

                        <select name="kelompok_rentan"
                                class="w-full rounded-[22px]
                                       border border-slate-200
                                       px-5 py-4
                                       focus:ring-2 focus:ring-slate-900">

                            @foreach([
                                'Tidak',
                                'Ibu Hamil',
                                'Lansia',
                                'Bayi',
                                'Disabilitas',
                                'Sakit'
                            ] as $item)

                            <option value="{{ $item }}"
                                {{ $pengungsi->kelompok_rentan == $item ? 'selected' : '' }}>

                                {{ $item }}

                            </option>

                            @endforeach

                        </select>

                    </div>

                    <!-- RIWAYAT -->
                    <div class="md:col-span-2">

                        <label class="block text-sm font-bold text-slate-700 mb-3">
                            Riwayat Penyakit
                        </label>

                        <textarea name="riwayat_penyakit"
                                  rows="5"
                                  class="w-full rounded-[24px]
                                         border border-slate-200
                                         px-5 py-4
                                         focus:ring-2 focus:ring-slate-900">{{ $pengungsi->riwayat_penyakit }}</textarea>

                    </div>

                </div>

                <!-- BUTTON -->
                <div class="mt-10 flex items-center justify-between">

                    <a href="{{ route('pengungsi.index', $pengungsi->lokasi_id) }}"
                       class="px-7 py-4 rounded-2xl
                              border border-slate-200
                              hover:bg-slate-100
                              text-slate-700 font-semibold transition">

                        ← Kembali

                    </a>

                    <button type="submit"
                            class="px-8 py-4 rounded-[22px]
                                   bg-slate-900 hover:bg-black
                                   text-white font-bold
                                   shadow-xl transition-all">

                        <i class="fas fa-floppy-disk mr-2"></i>

                        Update Data

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@endsection

@section('scripts')

<script>

function previewImage(event)
{
    const input = event.target;
    const preview = document.getElementById('preview-foto');

    if(input.files && input.files[0])
    {
        preview.src = URL.createObjectURL(input.files[0]);

        preview.classList.remove('hidden');
    }
}

</script>

@endsection