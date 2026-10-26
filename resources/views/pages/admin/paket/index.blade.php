@extends('layouts.admin')

@section('title', 'Import Paket Data Posko')

@section('content')
<div class="relative z-10 container mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10 min-h-screen flex flex-col gap-8">

    <!-- Header -->
    <div class="bg-white/60 backdrop-blur-xl p-6 md:p-8 rounded-3xl border border-white/80 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-50 border border-blue-100/50 text-blue-600 text-xs font-bold tracking-wide uppercase mb-3">
            <i class="fas fa-file-archive opacity-70"></i> Aplikasi Offline
        </div>
        <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 tracking-tight">Import Paket Data Posko</h1>
        <p class="text-slate-500 mt-1 font-medium text-sm">
            Gabungkan data dari aplikasi Logeva Client (posko, pengungsi, barang masuk &amp; keluar) yang dibawa lewat USB.
        </p>
    </div>

    @if ($hasil)
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-sm text-emerald-800">
            <p class="font-bold mb-1"><i class="fas fa-check-circle mr-1"></i> Paket {{ $hasil['namaFile'] }} berhasil digabung</p>
            <p>
                {{ $hasil['posko'] }} posko ditambah/diperbarui ·
                {{ $hasil['pengungsi'] }} pengungsi ditambah/diperbarui ·
                {{ $hasil['dihapus'] }} pengungsi dihapus ·
                {{ $hasil['masuk'] }} barang masuk · {{ $hasil['keluar'] }} barang keluar
                @if ($hasil['barang'] > 0) · {{ $hasil['barang'] }} barang baru di Master Logistik @endif
            </p>
            @if (($hasil['selisih_stok'] ?? 0) > 0)
                <p class="mt-2 text-amber-700">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    {{ $hasil['selisih_stok'] }} barang stoknya berbeda dengan HP.
                    <a href="{{ route('admin.paket.selisih') }}" class="font-bold underline">Lihat selisih stok</a>
                </p>
            @endif
            <p class="mt-2 text-emerald-700">Hapus file paket dari flashdisk karena berisi data pribadi pengungsi.</p>
        </div>
    @endif

    <div class="grid lg:grid-cols-5 gap-6">
        <!-- Upload -->
        <div class="lg:col-span-3 bg-white rounded-3xl border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] p-6 md:p-8">
            <h2 class="text-lg font-extrabold text-slate-800 mb-1">Upload paket</h2>
            <p class="text-sm text-slate-500 mb-5">File <b>paket_&lt;nama-posko&gt;_&lt;tanggal&gt;.zip</b> dari menu Ekspor Paket Data Posko di aplikasi.</p>

            <form method="POST" action="{{ route('admin.paket.preview') }}" enctype="multipart/form-data">
                @csrf
                <input type="file" name="file" accept=".zip" required
                       class="block w-full text-sm text-slate-600 border border-slate-200 rounded-xl cursor-pointer
                              file:mr-4 file:py-2.5 file:px-4 file:border-0 file:bg-blue-50 file:text-blue-700 file:font-bold hover:file:bg-blue-100">
                <p class="text-xs text-slate-400 mt-2">Format .zip · maks. 10 MB</p>

                @error('file')
                    <p class="text-sm text-rose-600 mt-2">{{ $message }}</p>
                @enderror

                <div class="flex justify-end mt-6">
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold shadow-lg shadow-blue-500/30">
                        <i class="fas fa-eye text-sm"></i> Lihat Pratinjau
                    </button>
                </div>
            </form>
        </div>

        <!-- Penjelasan -->
        <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] p-6 md:p-8 text-sm text-slate-600">
            <h2 class="text-lg font-extrabold text-slate-800 mb-3">Cara penggabungan</h2>
            <ul class="space-y-2 list-disc list-inside">
                <li>Paket yang sama boleh diimpor berkali-kali, dan paket lama boleh datang setelah paket baru: <b>tidak ada data dobel</b>.</li>
                <li><b>Posko</b> dicocokkan dengan kode, lalu nama posko + desa. Posko baru perlu dipilihkan relawan penanggung jawab.</li>
                <li><b>Pengungsi</b> ditambah kalau baru, diperbarui kalau data paket lebih baru, dihapus kalau sudah dihapus di aplikasi.</li>
                <li><b>Barang masuk/keluar</b> yang sudah pernah diimpor dilewati. Stok bertambah/berkurang sesuai catatan baru.</li>
                <li>Stok di HP hanya dipakai untuk memberi peringatan kalau berbeda.</li>
            </ul>
        </div>
    </div>

    @if ($jumlahSelisih > 0)
        <a href="{{ route('admin.paket.selisih') }}" class="flex items-center justify-between gap-4 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800 hover:bg-amber-100 transition-colors">
            <span><i class="fas fa-balance-scale mr-1"></i> <b>{{ $jumlahSelisih }} barang</b> stoknya di HP posko berbeda dengan stok pusat (paket terbaru tiap posko).</span>
            <span class="font-bold whitespace-nowrap">Lihat selisih <i class="fas fa-arrow-right text-xs ml-1"></i></span>
        </a>
    @endif

    <!-- Riwayat import -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] p-6 md:p-8">
        <h2 class="text-lg font-extrabold text-slate-800 mb-1">Riwayat import paket</h2>
        <p class="text-sm text-slate-500 mb-5">Untuk menelusuri asal data: paket apa, dari HP mana, diimpor siapa dan kapan.</p>

        @if ($riwayat->isEmpty())
            <p class="text-sm text-slate-400 py-6 text-center">Belum ada paket yang digabung.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50/80">
                            <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100 rounded-tl-2xl">Diimpor</th>
                            <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">Posko</th>
                            <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">Paket</th>
                            <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100 rounded-tr-2xl">Data masuk</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($riwayat as $r)
                            @php $j = $r->jumlah; @endphp
                            <tr class="align-top">
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <p class="font-bold text-slate-700">{{ $r->created_at->format('d-m-Y H:i') }}</p>
                                    <p class="text-xs text-slate-500">oleh {{ $r->user->name ?? 'akun terhapus' }}</p>
                                </td>
                                <td class="py-3 px-4">
                                    @forelse ($r->lokasis as $l)
                                        <p class="font-semibold text-slate-700">{{ $l->nama_lokasi }}@if ($l->trashed()) <span class="text-xs text-slate-400">(dihapus)</span>@endif</p>
                                    @empty
                                        <p class="text-slate-400">-</p>
                                    @endforelse
                                </td>
                                <td class="py-3 px-4">
                                    <p class="font-semibold text-slate-700 break-all">{{ $r->nama_file }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ $r->labelPerangkat() }}@if ($r->diekspor_oleh) · {{ $r->diekspor_oleh }}@endif
                                    </p>
                                    @if ($r->paket_dibuat_pada)
                                        <p class="text-xs text-slate-400">dibuat {{ $r->paket_dibuat_pada->format('d-m-Y H:i') }} WIB</p>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-xs text-slate-600">
                                    {{ $j['pengungsi'] ?? 0 }} pengungsi · {{ $j['dihapus'] ?? 0 }} dihapus<br>
                                    {{ $j['masuk'] ?? 0 }} masuk · {{ $j['keluar'] ?? 0 }} keluar
                                    @if (($j['barang'] ?? 0) > 0)<br>{{ $j['barang'] }} barang baru @endif
                                    @if ($r->selisih_stok_count > 0)<br><span class="font-bold text-amber-600">{{ $r->selisih_stok_count }} selisih stok</span>@endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $riwayat->links() }}</div>
        @endif
    </div>
</div>
@endsection
