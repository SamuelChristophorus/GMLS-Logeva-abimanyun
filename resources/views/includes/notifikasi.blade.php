{{--
    Ikon lonceng + daftar notifikasi. Dipakai di topbar aplikasi publik & panel admin:
        @include('includes.notifikasi')
--}}
@auth
@php
    $belumDibaca = auth()->user()->unreadNotifications()->count();
    $daftarNotif = auth()->user()->notifications()->latest()->limit(10)->get();
    $warnaNotif  = [
        'green' => 'bg-green-100 text-green-600',
        'red'   => 'bg-red-100 text-red-600',
        'amber' => 'bg-amber-100 text-amber-600',
        'blue'  => 'bg-blue-100 text-blue-600',
        'slate' => 'bg-slate-100 text-slate-600',
    ];
@endphp

<div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
    <button type="button" @click="open = !open" :aria-expanded="open"
            class="relative w-10 h-10 rounded-full border border-slate-200 bg-white text-slate-500 hover:text-red-600 hover:border-red-200 flex items-center justify-center transition"
            title="Notifikasi" aria-label="Notifikasi{{ $belumDibaca ? ", $belumDibaca belum dibaca" : '' }}">
        <i class="{{ $belumDibaca ? 'fas' : 'far' }} fa-bell"></i>
        @if ($belumDibaca)
            <span class="absolute -top-1 -right-1 min-w-[1.25rem] h-5 px-1 rounded-full bg-red-600 text-white text-[10px] font-bold flex items-center justify-center ring-2 ring-white">
                {{ $belumDibaca > 9 ? '9+' : $belumDibaca }}
            </span>
        @endif
    </button>

    <div x-show="open" x-cloak x-transition.origin.top.right
         class="absolute right-0 mt-2 w-[min(22rem,calc(100vw-2rem))] bg-white rounded-2xl shadow-xl border border-slate-100 z-[1300] overflow-hidden">

        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
            <p class="font-bold text-slate-800">
                Notifikasi
                @if ($belumDibaca)
                    <span class="ml-1 px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-[11px]">{{ $belumDibaca }} baru</span>
                @endif
            </p>
            @if ($belumDibaca)
                <form method="POST" action="{{ route('notifikasi.baca-semua') }}">
                    @csrf
                    <button type="submit" class="text-xs font-semibold text-blue-600 hover:text-blue-700">Tandai semua dibaca</button>
                </form>
            @endif
        </div>

        <ul class="max-h-[60vh] overflow-y-auto divide-y divide-slate-100">
            @forelse ($daftarNotif as $n)
                @php $d = $n->data; @endphp
                <li>
                    <a href="{{ route('notifikasi.buka', $n->id) }}"
                       class="flex gap-3 px-4 py-3 hover:bg-slate-50 transition {{ $n->read_at ? '' : 'bg-red-50/40' }}">
                        <span class="w-9 h-9 shrink-0 rounded-xl flex items-center justify-center {{ $warnaNotif[$d['warna'] ?? 'blue'] ?? $warnaNotif['blue'] }}">
                            <i class="fas {{ $d['ikon'] ?? 'fa-bell' }} text-sm"></i>
                        </span>
                        <span class="flex-1 min-w-0">
                            <span class="flex items-start justify-between gap-2">
                                <span class="text-sm {{ $n->read_at ? 'font-semibold text-slate-700' : 'font-bold text-slate-900' }}">{{ $d['judul'] ?? 'Notifikasi' }}</span>
                                @unless ($n->read_at)
                                    <span class="mt-1.5 w-2 h-2 shrink-0 rounded-full bg-red-600" aria-label="belum dibaca"></span>
                                @endunless
                            </span>
                            <span class="block text-xs text-slate-500 mt-0.5 line-clamp-2">{{ $d['pesan'] ?? '' }}</span>
                            <span class="block text-[11px] text-slate-400 mt-1">{{ $n->created_at->diffForHumans() }}</span>
                        </span>
                    </a>
                </li>
            @empty
                <li class="px-4 py-10 text-center text-sm text-slate-400">
                    <i class="far fa-bell-slash text-2xl mb-2"></i>
                    <p>Belum ada notifikasi.</p>
                </li>
            @endforelse
        </ul>
    </div>
</div>
@endauth
