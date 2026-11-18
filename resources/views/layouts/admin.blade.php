<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Sistem Informasi Manajemen Logistik">
    <meta name="author" content="Tim GMLS">

    <title>@yield('title', 'Admin Panel')</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Nunito', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <!-- Font Awesome -->
    <link href="{{asset('assets/admin/vendor/fontawesome-free/css/all.min.css')}}" rel="stylesheet" type="text/css">
    
    <!-- Google Fonts - Nunito -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">

    <!-- Global CSS Enhancements -->
    <style>
        [x-cloak] { display: none !important; }
        
        /* Premium custom scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; border: 2px solid transparent; background-clip: padding-box; }
        ::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }
    </style>
    @stack('head')
</head>

<body class="bg-slate-50 font-sans text-slate-800 antialiased overflow-hidden selection:bg-blue-200">
    
    <!-- Ambient Master Background -->
    <div class="fixed inset-0 z-0 bg-slate-50 pointer-events-none overflow-hidden">
        <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-gradient-to-bl from-blue-200/40 via-indigo-100/20 to-transparent rounded-full blur-3xl opacity-60 transform translate-x-1/3 -translate-y-1/3"></div>
        <div class="absolute bottom-0 left-0 w-[600px] h-[600px] bg-gradient-to-tr from-emerald-100/30 via-teal-50/20 to-transparent rounded-full blur-3xl opacity-50 transform -translate-x-1/4 translate-y-1/4"></div>
    </div>

    <!-- Main App Layout Wrapper -->
    <div class="flex h-screen relative z-10 w-full" id="wrapper" x-data="{ sidebarOpen: false }">

        <!-- Mobile Sidebar Overlay -->
        <div x-show="sidebarOpen" 
             @click="sidebarOpen = false" 
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-40 md:hidden" 
             x-cloak></div>

        <!-- Sidebar Panel Container -->
        <div :class="{'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen}" 
             class="fixed md:static inset-y-0 left-0 z-50 transform transition-transform duration-300 ease-[cubic-bezier(0.4,0,0.2,1)] md:translate-x-0 max-w-xs md:max-w-none">
            @include('includes.sidebar')
        </div>

        <!-- Content Area Wrapper -->
        <div class="flex-1 flex flex-col min-w-0 bg-transparent" id="content-wrapper">
            
            <!-- Topbar Navigation Area -->
            @include('includes.topbar')

            <!-- Scrollable Content Viewport -->
            <div class="flex-1 overflow-x-hidden overflow-y-auto" id="content">
                <main class="min-h-full flex flex-col">
                    @include('sweetalert::alert')
                    @yield('content')
                    <div class="mt-auto"></div> <!-- Spacer for footer push -->
                </main>
                @include('includes.footer')
            </div>
            
        </div>
    </div>

    <!-- Core Scripts -->
    <script src="{{asset('assets/admin/vendor/jquery/jquery.min.js')}}"></script>
    
    <!-- Alpine.js (Lightweight interactive logic) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    {{-- Semua input tanggal tampil dd/mm/yyyy berbahasa Indonesia --}}
    @include('includes.tanggal-id')

    @yield('scripts')
    @stack('scripts')
</body>
</html>