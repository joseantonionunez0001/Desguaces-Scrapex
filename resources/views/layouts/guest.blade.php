<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Bienvenido') · {{ config('navegacion.marca') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-800 antialiased">
    {{-- Plantilla de las páginas públicas: inicio, login y registro. --}}
    <div class="flex min-h-screen flex-col">
        <header class="bg-blue-950 text-white">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
                <a href="{{ route('home') }}" class="flex items-center gap-3 text-base font-bold tracking-tight">
                    <img src="{{ asset('images/logo.png') }}" alt="" class="h-10 w-auto rounded-md bg-white p-0.5">

                    {{ config('navegacion.marca') }}
                </a>
                <nav class="flex items-center gap-2" aria-label="Acceso">
                    @auth
                        <x-ui.button :href="route('dashboard')" variant="secondary" size="sm" icon="home">Ir al dashboard</x-ui.button>
                    @else
                        <a href="{{ route('login') }}" class="rounded-lg px-3 py-1.5 text-sm font-semibold text-blue-100 hover:bg-white/10">Login</a>
                        <a href="{{ route('register') }}" class="rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-blue-950 hover:bg-blue-50">Register</a>
                    @endauth
                </nav>
            </div>
        </header>

        <main class="flex flex-1 flex-col">
            @yield('content')
        </main>

        <footer class="border-t border-slate-200 bg-white px-6 py-4 text-center text-xs text-slate-500">
            {{ config('navegacion.marca') }} · Sistema de gestión de desguaces
        </footer>
    </div>
</body>
</html>
