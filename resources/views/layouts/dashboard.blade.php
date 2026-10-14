<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') – GMLS Logeva</title>

    <!-- Tailwind CSS via CDN (sama seperti layouts/app) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <!-- Alpine.js (untuk buka/tutup sidebar di HP dan dropdown profil) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
    </style>

    @stack('styles')
</head>

<body class="bg-slate-100 antialiased text-slate-800" x-data="{ sidebarOpen: false }">

    <div class="min-h-screen flex flex-col">

        {{-- ============ TOPBAR (lebar penuh) ============ --}}
        @include('includes.topbar-user')

        <div class="flex flex-1">

            {{-- Latar gelap saat sidebar dibuka di HP --}}
            <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
                 class="fixed inset-0 bg-black/40 z-[1150] md:hidden"></div>

            {{-- ============ SIDEBAR ============ --}}
            <aside :class="{ '!translate-x-0': sidebarOpen }"
                   class="fixed md:sticky top-0 md:top-16 left-0 z-[1200] md:z-auto
                          h-screen md:h-[calc(100vh-4rem)] w-64 shrink-0
                          -translate-x-full md:translate-x-0 transition-transform duration-200">
                @include('includes.sidebar-user')
            </aside>

            {{-- ============ KONTEN ============ --}}
            <main class="flex-1 min-w-0 p-4 md:p-6">
                @yield('content')
            </main>

        </div>
    </div>

    @yield('scripts')
    @stack('scripts')
</body>

</html>