@extends('layouts.dashboard')

@section('title', 'Data Pengungsi')

@section('content')

<div class="bg-gradient-to-br from-slate-100 via-slate-50 to-white py-10">

    <div class="max-w-7xl mx-auto px-6">

        <!-- =======================
             HERO HEADER
        ======================== -->

        <div class="relative overflow-hidden rounded-[38px]
                    bg-gradient-to-br from-slate-900 via-slate-800 to-black
                    p-8 md:p-10 shadow-[0_25px_80px_rgba(15,23,42,0.35)]">

            <!-- Glow -->
            <div class="absolute -top-20 -right-20 w-72 h-72 bg-white/5 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-20 -left-20 w-72 h-72 bg-white/5 rounded-full blur-3xl"></div>

            <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8">

                <!-- LEFT -->
                <div class="flex items-start gap-5">

                    <div class="w-20 h-20 rounded-[28px]
                                bg-white/10 backdrop-blur-xl
                                border border-white/10
                                flex items-center justify-center
                                shadow-2xl">

                        <i class="fas fa-people-group text-white text-3xl"></i>

                    </div>

                    <div>

                        <div class="inline-flex items-center gap-2
                                    px-4 py-2 rounded-full
                                    bg-white/10 border border-white/10
                                    text-slate-300 text-xs font-semibold tracking-[0.2em] uppercase mb-4">

                            Evacuation Management

                        </div>

                        <h1 class="text-4xl md:text-5xl font-black text-white tracking-tight leading-tight">

                            Data Pengungsi

                        </h1>

                        <p class="text-slate-300 mt-4 max-w-2xl leading-relaxed">

                            Monitoring dan pengelolaan data pengungsi secara realtime
                            pada lokasi Tempat Evakuasi Akhir (TEA).

                        </p>

                    </div>

                </div>

                <!-- RIGHT -->
                <div class="flex flex-wrap gap-4">

                    <!-- TOTAL -->
                    <div class="bg-white/10 border border-white/10 backdrop-blur-xl
                                rounded-3xl px-7 py-5 min-w-[170px]">

                        <p class="text-slate-400 text-sm mb-2">
                            Total Pengungsi
                        </p>

                        <h2 class="text-4xl font-black text-white">
                            {{ $lokasi->pengungsi->count() }}
                        </h2>

                    </div>

                    <!-- LOKASI -->
                    <div class="bg-white/10 border border-white/10 backdrop-blur-xl
                                rounded-3xl px-7 py-5 min-w-[220px]">

                        <p class="text-slate-400 text-sm mb-2">
                            Lokasi TEA
                        </p>

                        <h2 class="text-xl font-bold text-white leading-tight">
                            {{ $lokasi->nama_lokasi }}
                        </h2>

                    </div>

                </div>

            </div>

        </div>

        <!-- =======================
             ALERT
        ======================== -->

        @if(session('success'))

        <div class="mt-8 bg-white border border-slate-200 rounded-3xl p-5 shadow-sm">

            <div class="flex items-center gap-4">

                <div class="w-12 h-12 rounded-2xl bg-slate-900 text-white flex items-center justify-center">

                    <i class="fas fa-check"></i>

                </div>

                <div>

                    <h3 class="font-bold text-slate-800">
                        Berhasil
                    </h3>

                    <p class="text-slate-500 text-sm mt-1">
                        {{ session('success') }}
                    </p>

                </div>

            </div>

        </div>

        @endif

        <!-- =======================
             FORM
        ======================== -->

        <div class="mt-10 bg-white rounded-[38px]
                    border border-slate-200
                    shadow-[0_10px_40px_rgba(15,23,42,0.06)]
                    overflow-hidden">

            <!-- HEADER -->
            <div class="px-8 py-7 border-b border-slate-200
                        bg-gradient-to-r from-slate-50 to-white">

                <div class="flex items-center gap-4">

                    <div class="w-16 h-16 rounded-[24px]
                                bg-slate-900
                                text-white
                                flex items-center justify-center
                                shadow-xl">

                        <i class="fas fa-user-plus text-2xl"></i>

                    </div>

                    <div>

                        <h2 class="text-3xl font-black text-slate-900 tracking-tight">
                            Input Pengungsi
                        </h2>

                        <p class="text-slate-500 mt-2">
                            Tambahkan data pengungsi baru secara manual.
                        </p>

                    </div>

                </div>

            </div>

            <!-- FORM -->
        <form action="{{ route('pengungsi.store', $lokasi->id) }}"
            method="POST"
            enctype="multipart/form-data"
            class="p-8">

            @csrf

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

                        <input type="file"
                            name="foto"
                            accept="image/*"
                            onchange="previewImage(event)"
                            class="w-full text-slate-600">

                        <img id="preview-foto"
                            class="hidden mt-6 w-44 h-44 rounded-[28px] object-cover mx-auto shadow-2xl">

                    </div>

                </div>

                <!-- NAMA -->
                <div>

                    <label class="block text-sm font-bold text-slate-700 mb-3">
                        Nama Lengkap
                    </label>

                    <input type="text"
                        name="nama"
                        required
                        placeholder="Masukkan nama pengungsi"
                        class="w-full rounded-[22px]
                                border border-slate-200
                                px-5 py-4
                                bg-white
                                focus:ring-2 focus:ring-slate-900
                                focus:border-slate-900">

        </div>

        <!-- ASAL -->
        <div>

            <label class="block text-sm font-bold text-slate-700 mb-3">
                Asal Daerah
            </label>

            <input type="text"
                   name="asal"
                   required
                   placeholder="Contoh: Desa Sukamaju"
                   class="w-full rounded-[22px]
                          border border-slate-200
                          px-5 py-4
                          bg-white
                          focus:ring-2 focus:ring-slate-900
                          focus:border-slate-900">

        </div>

        <!-- TANGGAL LAHIR -->
        <div>

            <label class="block text-sm font-bold text-slate-700 mb-3">
                Tanggal Lahir
            </label>

            <input type="date"
                   name="tanggal_lahir"
                   required
                   class="w-full rounded-[22px]
                          border border-slate-200
                          px-5 py-4
                          bg-white
                          focus:ring-2 focus:ring-slate-900
                          focus:border-slate-900">

        </div>

        <!-- NOMOR KK -->
        <div>

            <label class="block text-sm font-bold text-slate-700 mb-3">
                Nomor Kartu Keluarga
            </label>

            <input type="text"
                   name="nomor_kk"
                   required
                   placeholder="Masukkan nomor KK"
                   class="w-full rounded-[22px]
                          border border-slate-200
                          px-5 py-4
                          bg-white
                          focus:ring-2 focus:ring-slate-900
                          focus:border-slate-900">

        </div>

        <!-- USIA -->
        <div>

            <label class="block text-sm font-bold text-slate-700 mb-3">
                Usia
            </label>

            <input type="number"
                   name="usia"
                   required
                   placeholder="0"
                   class="w-full rounded-[22px]
                          border border-slate-200
                          px-5 py-4
                          bg-white
                          focus:ring-2 focus:ring-slate-900
                          focus:border-slate-900">

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
                           bg-white
                           focus:ring-2 focus:ring-slate-900">

                <option value="Laki-laki">Laki-laki</option>
                <option value="Perempuan">Perempuan</option>

            </select>

        </div>

        <!-- KONDISI -->
        <div>

            <label class="block text-sm font-bold text-slate-700 mb-3">
                Kondisi Kesehatan
            </label>

            <input type="text"
                   name="kondisi_kesehatan"
                   placeholder="Sehat / Demam / Luka"
                   class="w-full rounded-[22px]
                          border border-slate-200
                          px-5 py-4
                          bg-white
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
                            bg-white
                            focus:ring-2 focus:ring-slate-900">

                    <option value="Tidak">Tidak</option>
                    <option value="Ibu Hamil">Ibu Hamil</option>
                    <option value="Lansia">Lansia</option>
                    <option value="Bayi">Bayi</option>
                    <option value="Disabilitas">Disabilitas</option>
                    <option value="Sakit">Sakit</option>

                </select>

            </div>

        </div>

        <!-- BUTTON -->
        <div class="mt-10 flex justify-end">

            <button type="submit"
                    class="group relative overflow-hidden
                        px-9 py-4 rounded-[24px]
                        bg-gradient-to-br from-slate-900 via-slate-800 to-black
                        hover:from-slate-800 hover:to-black
                        text-white font-bold
                        shadow-[0_10px_35px_rgba(15,23,42,0.35)]
                        hover:shadow-[0_20px_50px_rgba(15,23,42,0.45)]
                        transition-all duration-500 hover:-translate-y-1">

                <span class="relative z-10 flex items-center gap-3">

                    <i class="fas fa-floppy-disk"></i>

                    Simpan Data Pengungsi

                </span>

            </button>

        </div>

    </form>

        </div>

        @php
            $totalPengungsi = $lokasi->pengungsi->count();
            $kapasitas = $lokasi->kapasitas_pengungsi;

            $persentase = $kapasitas > 0
                ? ($totalPengungsi / $kapasitas) * 100
                : 0;
        @endphp

        <!-- =======================
             LIST PENGUNGSI
        ======================== -->

        <div class="mt-12">

            <!-- HEADER -->
            <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-6 mb-8">

                <div>

                    <h2 class="text-3xl font-black text-slate-900 tracking-tight">
                        Daftar Pengungsi
                    </h2>

                    <p class="text-slate-500 mt-2">
                        Monitoring seluruh pengungsi pada lokasi TEA.
                    </p>

                </div>

                <!-- ACTION -->
                <div class="flex flex-wrap gap-3">

                    <!-- BACK -->
                    <a href="{{ route('lokasi.show', $lokasi->id) }}"
                       class="inline-flex items-center gap-2
                              px-6 py-4 rounded-2xl
                              bg-white border border-slate-200
                              hover:bg-slate-100
                              text-slate-700 font-bold
                              transition-all duration-300">

                        <i class="fas fa-arrow-left"></i>

                        Kembali

                    </a>


                    @if ($lokasi->bisaDikelolaOleh(auth()->user()))
                    <!-- TEMPLATE -->
                    <a href="{{ route('pengungsi.import.template', $lokasi->id) }}"
                       class="inline-flex items-center gap-2
                              px-6 py-4 rounded-2xl
                              bg-white border border-blue-200
                              hover:bg-blue-50
                              text-blue-700 font-bold
                              transition-all duration-300"
                       title="Unduh template Excel beserta petunjuk pengisian">

                        <i class="fas fa-file-arrow-down"></i>

                        Unduh Template

                    </a>

                    <!-- IMPORT: upload -> pratinjau -> konfirmasi -->
                    <form action="{{ route('pengungsi.import.preview', $lokasi->id) }}"
                        method="POST"
                        enctype="multipart/form-data">

                    @csrf

                    <label class="inline-flex items-center gap-2
                                px-6 py-4 rounded-2xl
                                bg-gradient-to-r from-blue-500 to-cyan-600
                                hover:from-blue-600 hover:to-cyan-700
                                text-white font-bold
                                shadow-lg shadow-blue-500/20
                                transition-all duration-300
                                cursor-pointer"
                           title="Data dicek dan ditampilkan dulu sebelum disimpan">

                        <i class="fas fa-file-import"></i>

                        Import Excel / CSV

                        <input type="file"
                            name="file"
                            accept=".csv,.xlsx,.xls"
                            onchange="this.form.submit()"
                            hidden>

                    </label>

                    </form>
                    @endif

                    <!-- EXPORT -->
                    <a href="{{ route('pengungsi.export', $lokasi->id) }}"
                       class="inline-flex items-center gap-2
                              px-6 py-4 rounded-2xl
                              bg-gradient-to-r from-emerald-500 to-green-600
                              hover:from-emerald-600 hover:to-green-700
                              text-white font-bold
                              shadow-lg shadow-emerald-500/20
                              transition-all duration-300">

                        <i class="fas fa-file-excel"></i>

                        Export Excel

                    </a>

                </div>

            </div>

                <!-- ERROR -->
                @if(session('error'))

                        <div class="mt-4">

                            <div class="bg-red-50 border border-red-200 rounded-2xl p-4">

                                <div class="flex items-center gap-3">

                                    <i class="fas fa-circle-exclamation text-red-500"></i>

                                    <span class="text-red-700 font-medium">
                                        {{ session('error') }}
                                    </span>

                                </div>

                            </div>

                        </div>

                        @endif

            <!-- KAPASITAS -->
            <div class="bg-white rounded-[32px]
                        border border-slate-200
                        shadow-[0_10px_40px_rgba(15,23,42,0.06)]
                        p-8 mb-10">

                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8">

                    <!-- LEFT -->
                    <div>

                        <div class="inline-flex items-center gap-2
                                    px-4 py-2 rounded-full
                                    bg-slate-100 text-slate-700
                                    text-xs font-bold uppercase tracking-widest mb-4">

                            Capacity Monitoring

                        </div>

                        <h3 class="text-3xl font-black text-slate-900">
                            Status Kapasitas Lokasi
                        </h3>

                        <p class="text-slate-500 mt-3 max-w-2xl leading-relaxed">
                            Sistem akan memonitor jumlah pengungsi secara realtime
                            berdasarkan kapasitas maksimal lokasi TEA.
                        </p>

                    </div>

                    <!-- RIGHT -->
                    <div class="text-left lg:text-right">

                        <h2 class="text-6xl font-black
                            {{ $persentase >= 100
                                ? 'text-red-600'
                                : ($persentase >= 80
                                    ? 'text-amber-500'
                                    : 'text-emerald-600') }}">

                            {{ number_format($persentase, 0) }}%

                        </h2>

                        <p class="text-slate-500 mt-2 text-lg">
                            {{ $totalPengungsi }} / {{ $kapasitas }} Orang
                        </p>

                    </div>

                </div>

                <!-- PROGRESS -->
                <div class="mt-8">

                    <div class="w-full h-6 bg-slate-100 rounded-full overflow-hidden">

                        <div class="h-full rounded-full transition-all duration-700
                            {{ $persentase >= 100
                                ? 'bg-red-600'
                                : ($persentase >= 80
                                    ? 'bg-amber-500'
                                    : 'bg-emerald-600') }}"
                            style="width: {{ min($persentase, 100) }}%">

                        </div>

                    </div>

                    <!-- STATUS -->
                    <div class="mt-5">

                        @if($persentase >= 100)

                            <div class="inline-flex items-center gap-3
                                        px-5 py-3 rounded-2xl
                                        bg-red-100 text-red-700 font-bold">

                                <i class="fas fa-triangle-exclamation"></i>

                                Lokasi Sudah Melebihi Kapasitas

                            </div>

                        @elseif($persentase >= 80)

                            <div class="inline-flex items-center gap-3
                                        px-5 py-3 rounded-2xl
                                        bg-amber-100 text-amber-700 font-bold">

                                <i class="fas fa-circle-exclamation"></i>

                                Kapasitas Lokasi Hampir Penuh

                            </div>

                        @else

                            <div class="inline-flex items-center gap-3
                                        px-5 py-3 rounded-2xl
                                        bg-emerald-100 text-emerald-700 font-bold">

                                <i class="fas fa-circle-check"></i>

                                Kapasitas Lokasi Masih Aman

                            </div>

                        @endif

                    </div>

                </div>

            </div>
            
            <!-- SEARCH + TOGGLE -->
            <div class="mt-8 flex flex-col sm:flex-row sm:items-center gap-3 rounded-2xl border border-slate-200 bg-white p-2 shadow-sm">

                <!-- JUDUL + JUMLAH -->
                <div class="flex items-center gap-3 px-3 py-1 shrink-0">
                    <div class="w-9 h-9 rounded-xl bg-red-50 text-red-600 flex items-center justify-center">
                        <i class="fas fa-users text-sm"></i>
                    </div>
                    <div class="leading-tight">
                        <p class="text-sm font-black text-slate-900">Daftar Pengungsi</p>
                        <p class="text-[11px] text-slate-500"><span id="jumlahTampil">{{ $lokasi->pengungsi->count() }}</span> dari {{ $lokasi->pengungsi->count() }} orang</p>
                    </div>
                </div>

                <!-- SEARCH -->
                <div class="relative flex-1">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="search"
                           id="searchPengungsi"
                           placeholder="Cari nama pengungsi..."
                           oninput="searchPengungsi()"
                           class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-50 border border-transparent text-sm
                                  placeholder:text-slate-400
                                  focus:outline-none focus:bg-white focus:border-red-300 focus:ring-2 focus:ring-red-500/15 transition">
                </div>

                <!-- BUTTON -->
                <button type="button"
                        onclick="togglePengungsi()"
                        id="btnPengungsi"
                        class="shrink-0 inline-flex items-center justify-center gap-2
                               px-5 py-2.5 rounded-xl
                               bg-red-600 hover:bg-red-700
                               text-white text-sm font-bold
                               shadow-md shadow-red-600/20 transition">
                    <i id="iconPengungsi" class="fas fa-chevron-down text-[10px]"></i>
                    <span id="textPengungsi">Lihat Daftar</span>
                </button>

            </div>

<!-- CONTAINER -->
<div id="pengungsiContainer"
     class="hidden mt-6">

    <!-- GRID -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">

        @foreach($lokasi->pengungsi as $item)

        <div class="pengungsi-card bg-white rounded-2xl
                    border border-slate-200
                    shadow-sm hover:shadow-md
                    transition-all duration-300">

            <div class="p-3">

                <!-- TOP -->
                <div class="flex items-start gap-3">

                    <!-- FOTO -->
                    <div class="shrink-0">

                        @if($item->foto)

                        <img src="{{ asset('storage/' . $item->foto) }}"
                             class="w-16 h-16 rounded-xl object-cover border border-slate-200">

                        @else

                        <div class="w-16 h-16 rounded-xl bg-slate-100
                                    flex items-center justify-center border border-slate-200">

                            <i class="fas fa-user text-lg text-slate-300"></i>

                        </div>

                        @endif

                    </div>

                    <!-- CONTENT -->
                    <div class="flex-1 min-w-0">

                        <div class="flex items-start justify-between gap-2">

                            <div>

                                <h3 class="nama-pengungsi text-sm font-black text-slate-900 truncate">
                                    {{ $item->nama }}
                                </h3>

                                <p class="text-[11px] text-slate-500 truncate mt-0.5">
                                    {{ $item->asal }}
                                </p>

                            </div>

                            <!-- BADGE -->
                            <span class="px-2 py-1 rounded-full text-[8px] font-bold whitespace-nowrap
                            {{ $item->kelompok_rentan != 'Tidak'
                                ? 'bg-black text-white'
                                : 'bg-slate-100 text-slate-700' }}">

                                {{ $item->kelompok_rentan }}

                            </span>

                        </div>

                        <!-- INFO -->
                        <div class="mt-2 flex items-center gap-3">

                            <!-- USIA -->
                            <div class="flex items-center gap-1.5">

                                <i class="fas fa-user-clock text-[10px] text-slate-400"></i>

                                <p class="text-[11px] font-bold text-slate-700">
                                    {{ $item->usia }} Th
                                </p>

                            </div>

                            <!-- KONDISI -->
                            <div class="flex items-center gap-1.5 min-w-0">

                                <i class="fas fa-heart-pulse text-[10px] text-slate-400"></i>

                                <p class="text-[11px] font-bold text-slate-700 truncate">
                                    {{ $item->kondisi_kesehatan }}
                                </p>

                            </div>

                        </div>

                    </div>

                </div>

                <!-- ACTION -->
                <div class="mt-3 flex items-center gap-2">

                    <!-- SHOW -->
                    <a href="{{ route('pengungsi.show', $item->id) }}"
                       class="flex-1 inline-flex items-center justify-center gap-1.5
                              px-3 py-2 rounded-xl
                              bg-slate-900 hover:bg-black
                              text-white text-[11px] font-bold">

                        <i class="fas fa-eye text-[9px]"></i>

                        Lihat

                    </a>

                    <!-- EDIT -->
                    <a href="{{ route('pengungsi.edit', $item->id) }}"
                       class="w-9 h-9 rounded-xl
                              border border-slate-200
                              hover:bg-slate-100
                              flex items-center justify-center
                              text-slate-700">

                        <i class="fas fa-pen text-[10px]"></i>

                    </a>

                    <!-- DELETE -->
                    <form action="{{ route('pengungsi.destroy', $item->id) }}"
                          method="POST"
                          onsubmit="return confirm('Yakin ingin menghapus data ini?')">

                        @csrf
                        @method('DELETE')

                        <button type="submit"
                                class="w-9 h-9 rounded-xl
                                       border border-red-200
                                       hover:bg-red-500
                                       hover:text-white
                                       flex items-center justify-center
                                       text-red-500">

                            <i class="fas fa-trash text-[10px]"></i>

                        </button>

                    </form>

                </div>

            </div>

        </div>

        @endforeach

    </div>

    <!-- KOSONG -->
    <div id="pengungsiKosong"
         class="{{ $lokasi->pengungsi->isEmpty() ? '' : 'hidden' }} rounded-2xl border border-dashed border-slate-200 bg-white py-10 text-center">
        <i class="fas fa-user-slash text-2xl text-slate-300"></i>
        <p id="pengungsiKosongTeks" class="mt-2 text-sm font-semibold text-slate-500">
            {{ $lokasi->pengungsi->isEmpty() ? 'Belum ada pengungsi yang terdaftar di posko ini.' : '' }}
        </p>
    </div>

</div>

        </div>{{-- /mt-12 --}}

    </div>{{-- /max-w-7xl --}}

</div>

<script>

function togglePengungsi()
{
    const container = document.getElementById('pengungsiContainer');
    const text = document.getElementById('textPengungsi');

    const icon = document.getElementById('iconPengungsi');

    container.classList.toggle('hidden');

    const tertutup = container.classList.contains('hidden');
    text.innerHTML = tertutup ? 'Lihat Daftar' : 'Sembunyikan';
    icon.classList.toggle('rotate-180', !tertutup);
}

function searchPengungsi()
{
    const input = document.getElementById('searchPengungsi').value.trim().toLowerCase();
    const cards = document.querySelectorAll('.pengungsi-card');
    const container = document.getElementById('pengungsiContainer');
    let tampil = 0;

    // Mengetik di kolom cari otomatis membuka daftar
    if (input && container.classList.contains('hidden')) togglePengungsi();

    cards.forEach(card => {
        const nama = card.querySelector('.nama-pengungsi').innerText.toLowerCase();
        const cocok = nama.includes(input);
        card.classList.toggle('hidden', !cocok);
        if (cocok) tampil++;
    });

    document.getElementById('jumlahTampil').textContent = tampil;

    if (cards.length) {
        document.getElementById('pengungsiKosong').classList.toggle('hidden', tampil > 0);
        document.getElementById('pengungsiKosongTeks').textContent = 'Tidak ada pengungsi bernama "' + input + '".';
    }
}

</script>
@endsection