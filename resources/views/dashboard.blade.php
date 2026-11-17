@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <x-ui.page-header :title="'Bienvenido, '.$usuario->name" subtitle="Resumen de la actividad del desguace." />

    <x-ui.card class="mb-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <dl class="grid gap-3 text-sm sm:grid-cols-3 sm:gap-8">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Usuario</dt>
                    <dd class="mt-0.5 font-medium text-slate-800">{{ $usuario->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Correo electrónico</dt>
                    <dd class="mt-0.5 font-medium text-slate-800">{{ $usuario->email }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Rol asignado</dt>
                    <dd class="mt-0.5 flex flex-wrap gap-1">
                        @forelse ($roles as $rol)
                            <x-ui.badge tone="blue">{{ $rol }}</x-ui.badge>
                        @empty
                            <x-ui.badge tone="amber">Sin rol</x-ui.badge>
                        @endforelse
                    </dd>
                </div>
            </dl>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-ui.button type="submit" variant="outline" icon="logout" data-no-loading>Logout</x-ui.button>
            </form>
        </div>
    </x-ui.card>

    @if ($sinRol)
        <x-ui.alert type="warning">
            <p class="font-semibold">Cuenta pendiente de autorización</p>
            <p class="mt-1">
                Tu cuenta aún no tiene ningún rol. Un administrador debe asignártelo antes de que puedas
                utilizar las funciones del desguace. Mientras tanto no tienes acceso a los módulos internos.
            </p>
        </x-ui.alert>
    @else
        @if ($indicadores === [])
            <x-ui.alert type="info">Tu rol no incluye permisos de consulta de inventario, por lo que no hay indicadores que mostrar.</x-ui.alert>
        @else
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @isset($indicadores['vehiculos'])
                    <x-ui.stat-card label="Vehículos" :value="number_format($indicadores['vehiculos']['total'], 0, ',', '.')" icon="truck" tone="blue"
                        :hint="$indicadores['vehiculos']['ultimos_30_dias'].' llegados en los últimos 30 días'"
                        :href="Route::has('vehiculos.index') ? route('vehiculos.index') : null" />
                @endisset

                @isset($indicadores['piezas'])
                    <x-ui.stat-card label="Piezas" :value="number_format($indicadores['piezas']['total'], 0, ',', '.')" icon="cog" tone="emerald"
                        :hint="number_format($indicadores['piezas']['unidades'], 0, ',', '.').' unidades en stock'"
                        :href="Route::has('piezas.index') ? route('piezas.index') : null" />
                    <x-ui.stat-card label="Valor del inventario" :value="number_format($indicadores['piezas']['valor'], 2, ',', '.').' €'" icon="archive" tone="slate"
                        hint="Suma de precio × stock de todas las piezas" />
                    <x-ui.stat-card label="Piezas con stock bajo" :value="number_format($indicadores['piezas']['stock_bajo'], 0, ',', '.')" icon="warning"
                        :tone="$indicadores['piezas']['stock_bajo'] > 0 ? 'amber' : 'emerald'"
                        hint="Stock igual o inferior al stock mínimo" />
                @endisset
            </div>

            @isset($indicadores['movimientos'])
                <div class="mt-6 grid gap-6 lg:grid-cols-3">
                    <x-ui.card title="Movimientos por tipo" class="lg:col-span-1" :padding="false">
                        @if ($indicadores['movimientos']['por_tipo']->isEmpty())
                            <x-ui.empty-state title="Sin movimientos" message="Todavía no se ha registrado ningún movimiento." icon="arrows" />
                        @else
                            <x-ui.table>
                                <x-slot:head>
                                    <tr>
                                        <th>Tipo</th>
                                        <th class="text-right">Movimientos</th>
                                        <th class="text-right">Unidades</th>
                                    </tr>
                                </x-slot:head>
                                @foreach ($indicadores['movimientos']['por_tipo'] as $fila)
                                    <tr>
                                        <td class="font-medium">{{ $fila->tipo_movimiento }}</td>
                                        <td class="text-right">{{ $fila->total }}</td>
                                        <td class="text-right">{{ $fila->unidades }}</td>
                                    </tr>
                                @endforeach
                            </x-ui.table>
                        @endif
                        <x-slot:footer>
                            {{ $indicadores['movimientos']['total'] }} movimientos en total,
                            {{ $indicadores['movimientos']['ultimos_30_dias'] }} en los últimos 30 días.
                        </x-slot:footer>
                    </x-ui.card>

                    <x-ui.card title="Últimos movimientos" class="lg:col-span-2" :padding="false">
                        @if ($indicadores['movimientos']['recientes']->isEmpty())
                            <x-ui.empty-state title="Sin movimientos" message="Cuando se registren movimientos aparecerán aquí." icon="arrows" />
                        @else
                            <x-ui.table>
                                <x-slot:head>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Tipo</th>
                                        <th class="text-right">Cantidad</th>
                                        <th>Categoría</th>
                                    </tr>
                                </x-slot:head>
                                @foreach ($indicadores['movimientos']['recientes'] as $movimiento)
                                    <tr>
                                        <td>{{ $movimiento->fecha->format('d/m/Y') }}</td>
                                        <td>{{ $movimiento->tipo_movimiento }}</td>
                                        <td class="text-right">{{ $movimiento->cantidad }}</td>
                                        <td>{{ $movimiento->categoria?->nombre ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </x-ui.table>
                        @endif
                    </x-ui.card>
                </div>
            @endisset
        @endif

        <h2 class="mb-3 mt-8 text-lg font-semibold text-blue-950">Accesos rápidos</h2>
        @if ($accesos->isEmpty())
            <x-ui.alert type="info">Todavía no hay módulos disponibles para tu rol.</x-ui.alert>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($accesos as $acceso)
                    <a href="{{ $acceso['url'] }}" class="group flex items-start gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-blue-300 hover:shadow">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-700 group-hover:bg-blue-100">
                            <x-ui.icon :name="$acceso['icon']" class="size-5" />
                        </span>
                        <span>
                            <span class="block text-sm font-semibold text-blue-950">{{ $acceso['label'] }}</span>
                            <span class="block text-xs text-slate-500">{{ $acceso['descripcion'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    @endif
@endsection
