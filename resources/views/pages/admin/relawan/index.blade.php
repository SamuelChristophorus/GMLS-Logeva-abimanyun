@extends('layouts.admin')

@section('title', 'Data Relawan')

@section('content')
<div class="relative z-10 container mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10 min-h-screen flex flex-col gap-8">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white/60 backdrop-blur-xl p-6 md:p-8 rounded-3xl border border-white/80 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-50 border border-blue-100/50 text-blue-600 text-xs font-bold tracking-wide uppercase mb-3">
                <i class="fas fa-users opacity-70"></i>
                Personel & SDM
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 tracking-tight">
                Data Relawan Terdaftar
            </h1>
            <p class="text-slate-500 mt-1 font-medium text-sm">
                Kelola informasi akun dan status verifikasi relawan.
            </p>
        </div>

        <div>
            <a href="{{ route('admin.relawan.create') }}" class="inline-flex items-center justify-center gap-2 bg-gradient-to-tr from-blue-600 to-indigo-500 hover:from-blue-700 hover:to-indigo-600 text-white font-bold px-5 py-3 rounded-xl shadow-lg shadow-blue-500/30 transform transition-transform duration-300 hover:scale-[1.02] border border-blue-400/20">
                <i class="fas fa-user-plus text-sm"></i> Tambah Relawan
            </a>
        </div>
    </div>

    <div class="bg-white rounded-3xl border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden">
        <div class="p-6 md:p-8">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse" id="dataTable">
                    <thead>
                        <tr class="bg-slate-50/50">
                            <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100 rounded-tl-2xl w-16">No</th>
                            <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100 w-24">Profil</th>
                            <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">Informasi Relawan</th>
                            <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100 text-center w-40">Status</th>
                            <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100 rounded-tr-2xl text-center w-64">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100/80 bg-white">
                        @foreach ($relawans as $relawan)
                        <tr class="hover:bg-slate-50/50 transition-colors group">
                            <td class="py-4 px-6 text-sm font-semibold text-slate-500 align-middle">
                                {{ $loop->iteration }}
                            </td>

                            <td class="py-4 px-6 align-middle">
                                <div class="w-14 h-14 rounded-2xl overflow-hidden border-2 border-slate-100 shadow-sm p-0.5 bg-white shrink-0">
                                    <div class="w-full h-full rounded-xl overflow-hidden">
                                        <img src="{{ asset('storage/' . $relawan->avatar) }}" alt="Avatar Relawan" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                                    </div>
                                </div>
                            </td>

                            <td class="py-4 px-6 align-middle">
                                <div class="flex flex-col">
                                    <h4 class="text-sm font-extrabold text-slate-800 tracking-tight">
                                        {{ $relawan->user->name }}
                                    </h4>
                                    <div class="flex items-center gap-1.5 mt-1 text-slate-500">
                                        <i class="fas fa-envelope text-[10px] opacity-70"></i>
                                        <span class="text-xs font-medium">
                                            {{ $relawan->user->email }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <td class="py-4 px-6 align-middle text-center">
                                @if($relawan->user->status === 'pending')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold bg-yellow-50 text-yellow-600 border border-yellow-200">
                                        <i class="fas fa-clock mr-1.5 opacity-80"></i> Pending
                                    </span>
                                @elseif($relawan->user->status === 'approved')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-200">
                                        <i class="fas fa-check-circle mr-1.5 opacity-80"></i> Approved
                                    </span>
                                @elseif($relawan->user->status === 'rejected')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold bg-rose-50 text-rose-600 border border-rose-200">
                                        <i class="fas fa-times-circle mr-1.5 opacity-80"></i> Rejected
                                    </span>
                                @elseif($relawan->user->status === 'suspended')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                        <i class="fas fa-ban mr-1.5 opacity-80"></i> Suspended
                                    </span>
                                @endif
                            </td>

                            <td class="py-4 px-6 align-middle text-center">
                                <div class="flex items-center justify-center gap-2 flex-wrap opacity-80 group-hover:opacity-100 transition-opacity">

                                    @if($relawan->user->status === 'pending')
                                        <form action="{{ route('admin.relawan.accept', $relawan->id) }}" method="POST" class="inline-block m-0">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center hover:bg-emerald-600 hover:text-white transition-colors border border-emerald-100" title="Accept">
                                                <i class="fas fa-check text-xs"></i>
                                            </button>
                                        </form>

                                        <form action="{{ route('admin.relawan.reject', $relawan->id) }}" method="POST" class="inline-block m-0" onsubmit="return confirm('Tolak pendaftaran relawan ini?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center hover:bg-rose-600 hover:text-white transition-colors border border-rose-100" title="Reject">
                                                <i class="fas fa-times text-xs"></i>
                                            </button>
                                        </form>
                                    @endif

                                    @if($relawan->user->status === 'approved')
                                        <form action="{{ route('admin.relawan.suspend', $relawan->id) }}" method="POST" class="inline-block m-0" onsubmit="return confirm('Suspend akun relawan ini?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center hover:bg-slate-700 hover:text-white transition-colors border border-slate-200" title="Suspend">
                                                <i class="fas fa-ban text-xs"></i>
                                            </button>
                                        </form>
                                    @endif

                                    @if($relawan->user->status === 'rejected' || $relawan->user->status === 'suspended')
                                        <form action="{{ route('admin.relawan.accept', $relawan->id) }}" method="POST" class="inline-block m-0">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center hover:bg-emerald-600 hover:text-white transition-colors border border-emerald-100" title="Aktifkan Lagi">
                                                <i class="fas fa-user-check text-xs"></i>
                                            </button>
                                        </form>
                                    @endif

                                    <a href="{{ route('admin.relawan.show', $relawan->id) }}" class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-colors border border-blue-100" title="Profil Lengkap">
                                        <i class="fas fa-id-card text-xs"></i>
                                    </a>

                                    <a href="{{ route('admin.relawan.edit', $relawan->id) }}" class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center hover:bg-amber-500 hover:text-white transition-colors border border-amber-100" title="Edit Relawan">
                                        <i class="fas fa-edit text-xs"></i>
                                    </a>

                                    <form action="{{ route('admin.relawan.destroy', $relawan->id) }}" method="POST" class="inline-block m-0" onsubmit="return confirm('Anda yakin ingin mencabut akses dan menghapus relawan ini secara permanen?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center hover:bg-rose-600 hover:text-white transition-colors border border-rose-100" title="Hapus Dari Sistem">
                                            <i class="fas fa-user-times text-xs"></i>
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection