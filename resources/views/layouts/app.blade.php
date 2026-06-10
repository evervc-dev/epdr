<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? config('app.name') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
        <div class="min-h-screen">
            <header class="border-b border-slate-200 bg-white/90 backdrop-blur">
                <div class="mx-auto flex w-full max-w-6xl items-center justify-between gap-6 px-4 py-4 sm:px-6 lg:px-8">
                    <a href="{{ route('home') }}" class="text-lg font-semibold tracking-tight text-slate-900">
                        {{ config('app.name') }}
                    </a>

                    <nav class="flex flex-wrap items-center gap-3 text-sm font-medium text-slate-600">
                        <a href="{{ route('home') }}" class="rounded-full px-3 py-2 transition hover:bg-slate-100 hover:text-slate-900">
                            Home
                        </a>
                        <a href="{{ route('nosotros') }}" class="rounded-full px-3 py-2 transition hover:bg-slate-100 hover:text-slate-900">
                            Nosotros
                        </a>

                        @role('admin')
                            <a href="{{ route('usuarios') }}" class="rounded-full px-3 py-2 transition hover:bg-slate-100 hover:text-slate-900">
                                Usuarios
                            </a>
                        @endrole

                        @guest
                            <a href="{{ route('login') }}" class="rounded-full bg-slate-900 px-4 py-2 text-white transition hover:bg-slate-700">
                                Login
                            </a>
                        @else
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="rounded-full bg-rose-600 px-4 py-2 text-white transition hover:bg-rose-500">
                                    Cerrar sesión
                                </button>
                            </form>
                        @endguest
                    </nav>
                </div>
            </header>

            <main class="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
                {{ $slot }}
            </main>
        </div>

        @livewireScripts
    </body>
</html>
