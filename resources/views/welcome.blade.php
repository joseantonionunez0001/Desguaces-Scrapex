@extends('layouts.guest')

@section('title', 'Inicio')

@section('content')
    <section class="bg-gradient-to-br from-blue-950 via-blue-900 to-blue-700 text-white">
        <div class="mx-auto flex max-w-6xl flex-col items-start gap-6 px-4 py-16 sm:px-6 sm:py-24">
            <x-ui.badge tone="blue" class="bg-white/10! text-blue-100! ring-white/20!">Trabajo de Fin de Grado</x-ui.badge>
            <h1 class="max-w-3xl text-4xl font-bold tracking-tight sm:text-5xl">{{ config('navegacion.marca') }}</h1>
            <p class="max-w-2xl text-lg text-blue-100">
                Sistema integral de gestión de desguaces: controla los vehículos que llegan, el catálogo de piezas,
                los movimientos de inventario y la ubicación física de cada elemento en el almacén y en el patio.
            </p>

            <div class="flex flex-wrap gap-3">
                @auth
                    <x-ui.button :href="route('dashboard')" variant="secondary" size="lg" icon="home">Ir al dashboard</x-ui.button>
                @else
                    <x-ui.button :href="route('login')" variant="secondary" size="lg" icon="lock">Login</x-ui.button>
                    <x-ui.button :href="route('register')" variant="outline" size="lg" icon="user">Register</x-ui.button>
                @endauth
            </div>
        </div>
    </section>

    <section class="mx-auto w-full max-w-6xl px-4 py-12 sm:px-6">
        <h2 class="text-xl font-bold text-blue-950">Qué puedes hacer</h2>
        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-ui.card>
                <x-ui.icon name="truck" class="size-8 text-blue-700" />
                <h3 class="mt-3 font-semibold text-blue-950">Vehículos</h3>
                <p class="mt-1 text-sm text-slate-600">Registro de entrada, estado de tratamiento e historial de ubicaciones.</p>
            </x-ui.card>
            <x-ui.card>
                <x-ui.icon name="cog" class="size-8 text-blue-700" />
                <h3 class="mt-3 font-semibold text-blue-950">Piezas</h3>
                <p class="mt-1 text-sm text-slate-600">Catálogo por tipo y categoría con precio, stock y stock mínimo.</p>
            </x-ui.card>
            <x-ui.card>
                <x-ui.icon name="archive" class="size-8 text-blue-700" />
                <h3 class="mt-3 font-semibold text-blue-950">Almacén</h3>
                <p class="mt-1 text-sm text-slate-600">Estructura interior y exterior de cada desguace, nivel a nivel.</p>
            </x-ui.card>
            <x-ui.card>
                <x-ui.icon name="shield" class="size-8 text-blue-700" />
                <h3 class="mt-3 font-semibold text-blue-950">Roles y permisos</h3>
                <p class="mt-1 text-sm text-slate-600">Administrador, gerente y operario con permisos diferenciados.</p>
            </x-ui.card>
        </div>
    </section>
@endsection
