<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel') · {{ config('navegacion.marca') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-800 antialiased">
    {{--
        Plantilla de las páginas privadas (requieren sesión iniciada).
        Uso en cada vista:
            @extends('layouts.app')
            @section('title', 'Vehículos')
            @section('content') … @endsection
    --}}
    <a href="#contenido" class="sr-only focus:not-sr-only focus:absolute focus:left-2 focus:top-2 focus:z-50 focus:rounded focus:bg-white focus:px-3 focus:py-2">Saltar al contenido</a>

    <div class="min-h-screen lg:flex">
        <x-layout.sidebar />

        <div class="flex min-w-0 flex-1 flex-col">
            <x-layout.topbar />

            <main id="contenido" class="flex-1 p-4 sm:p-6 lg:p-8">
                <x-ui.flash />
                @yield('content')
            </main>

            <footer class="border-t border-slate-200 px-6 py-4 text-center text-xs text-slate-500">
                {{ config('navegacion.marca') }} · Sistema de gestión de desguaces
            </footer>
        </div>
    </div>

    @stack('scripts')
    @flasher_render
</body>
</html>
