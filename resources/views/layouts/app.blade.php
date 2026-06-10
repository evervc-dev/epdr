<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? config('app.name') }}</title>

        <!-- Fonts & CSS/JS -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
        <style>
            [x-cloak] { display: none !important; }
        </style>
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-900 antialiased font-sans" x-data="{ sidebarOpen: false }">
        
        <!-- Global Toast Notifications -->
        <x-toast-notifications />

        <!-- Inactivity auto-logout form -->
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
            @csrf
        </form>

        <div class="flex min-h-screen">
            <!-- Sidebar for Mobile (Off-canvas) -->
            <div 
                x-show="sidebarOpen" 
                class="relative z-50 lg:hidden animate-fade-in" 
                role="dialog" 
                aria-modal="true"
                x-cloak
            >
                <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-xs"></div>
                <div class="fixed inset-0 flex">
                    <div 
                        class="relative mr-16 flex w-full max-w-xs flex-1 flex-col bg-slate-900 pb-4 pt-5"
                        @click.away="sidebarOpen = false"
                    >
                        <div class="absolute left-full top-0 flex w-16 justify-center pt-5">
                            <button @click="sidebarOpen = false" type="button" class="-m-2.5 p-2.5 text-white">
                                <span class="sr-only">Cerrar menú</span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <!-- Sidebar content mobile -->
                        @include('layouts.partials.sidebar-content')
                    </div>
                </div>
            </div>

            <!-- Static Sidebar for Desktop -->
            <div class="hidden lg:flex lg:w-72 lg:flex-col lg:fixed lg:inset-y-0 lg:z-40 bg-slate-900">
                @include('layouts.partials.sidebar-content')
            </div>

            <!-- Main area -->
            <div class="lg:pl-72 flex flex-col flex-1">
                <!-- Top Navbar -->
                <header class="sticky top-0 z-30 flex h-16 shrink-0 items-center justify-between gap-x-4 border-b border-slate-200 bg-white/80 backdrop-blur-md px-4 shadow-2xs sm:gap-x-6 sm:px-6 lg:px-8">
                    <!-- Toggle sidebar mobile button -->
                    <button @click="sidebarOpen = true" type="button" class="-m-2.5 p-2.5 text-slate-700 lg:hidden hover:bg-slate-50 rounded-xl transition">
                        <span class="sr-only">Abrir sidebar</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>

                    <!-- Breadcrumbs or App Title -->
                    <div class="text-sm font-semibold text-slate-600">
                        {{ config('app.name') }}
                    </div>

                    <!-- User actions & Profile -->
                    <div class="flex items-center gap-x-4 lg:gap-x-6">
                        <div class="flex items-center gap-2">
                            @auth
                                <div class="hidden sm:flex flex-col items-end mr-1">
                                    <span class="text-sm font-bold text-slate-900">{{ auth()->user()->name }}</span>
                                    @if(auth()->user()->roles->isNotEmpty())
                                        <x-badge-rol :rol="auth()->user()->roles->first()->name" />
                                    @endif
                                </div>
                                <div class="h-10 w-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold shadow-md shadow-indigo-100">
                                    {{ auth()->user()->initials() }}
                                </div>
                                <button 
                                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                                    class="ml-2 p-2.5 text-rose-600 hover:text-rose-700 hover:bg-rose-50 rounded-xl transition-all duration-150"
                                    title="Cerrar sesión"
                                >
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                                    </svg>
                                </button>
                            @endauth
                        </div>
                    </div>
                </header>

                <!-- Page Content -->
                <main class="py-8 px-4 sm:px-6 lg:px-8 flex-1 bg-slate-50/50">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @livewireScripts

        <!-- 15-Minute Inactivity Auto-logout Script -->
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                let inactivityTime = function () {
                    let time;
                    // Reset timer on any of these events
                    window.onload = resetTimer;
                    document.onmousemove = resetTimer;
                    document.onkeypress = resetTimer;
                    document.onclick = resetTimer;
                    document.onscroll = resetTimer;

                    function logout() {
                        // Notify user or logout immediately
                        console.log('Sesión expirada por inactividad. Cerrando sesión...');
                        document.getElementById('logout-form').submit();
                    }

                    function resetTimer() {
                        clearTimeout(time);
                        // 15 minutes = 900,000 milliseconds
                        time = setTimeout(logout, 900000); 
                    }
                };
                inactivityTime();
            });
        </script>
    </body>
</html>
