@extends('layouts.app')

@section('title', 'Historial de ubicaciones · '.$vehiculo->matricula)

@section('content')
    <x-ui.page-header title="Historial de ubicaciones" :subtitle="$vehiculo->matricula.' · '.$vehiculo->marca.' '.$vehiculo->modelo"
        :breadcrumbs="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Vehículos', 'url' => route('vehiculos.index')],
            ['label' => $vehiculo->matricula, 'url' => route('vehiculos.show', $vehiculo)],
            ['label' => 'Historial de ubicaciones'],
        ]">
        <x-slot:actions>
            <x-ui.button :href="route('vehiculos.show', $vehiculo)" variant="outline" icon="arrow-left">Volver a la ficha</x-ui.button>
            @if (Route::has('vehiculos.ubicaciones.asignar'))
                @can('ubicaciones_vehiculo.crear')
                    <x-ui.button :href="route('vehiculos.ubicaciones.asignar', $vehiculo)" icon="map-pin">Asignar ubicación</x-ui.button>
                @endcan
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card title="Vehículo" class="mb-6">
        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Matrícula</dt>
                <dd class="mt-1 text-sm font-semibold text-blue-950">{{ $vehiculo->matricula }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Marca y modelo</dt>
                <dd class="mt-1 text-sm">{{ $vehiculo->marca }} {{ $vehiculo->modelo }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Estado de tratamiento</dt>
                <dd class="mt-1 text-sm"><x-ui.badge tone="blue">{{ $vehiculo->estado_tratamiento }}</x-ui.badge></dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Desguace</dt>
                <dd class="mt-1 text-sm">{{ $vehiculo->desguace?->nombre ?? '—' }}</dd>
            </div>
        </dl>
    </x-ui.card>

    <x-ui.card title="Ubicación actual" class="mb-6">
        @if ($ubicacionActual)
            <p class="flex items-start gap-2 text-sm">
                <x-ui.icon name="map-pin" class="mt-0.5 size-5 shrink-0 text-blue-700" />
                <span>@include('vehiculos._ubicacion', ['ubicacion' => $ubicacionActual])</span>
            </p>
            <p class="mt-2 text-xs text-slate-500">Desde el {{ $ubicacionActual->fecha_inicio->format('d/m/Y') }}</p>
        @else
            <x-ui.alert type="warning">Este vehículo no tiene una ubicación vigente en este momento.</x-ui.alert>
        @endif
    </x-ui.card>

    <x-ui.card title="Historial" subtitle="De la ubicación más reciente a la más antigua. Solo lectura." :padding="false">
        @if ($ubicaciones->isEmpty())
            <x-ui.empty-state title="Sin historial de ubicaciones" message="Este vehículo todavía no ha tenido ninguna ubicación asignada." icon="map-pin" />
        @else
            <x-ui.table>
                <x-slot:head>
                    <tr>
                        <th scope="col">Fecha de inicio</th>
                        <th scope="col">Fecha de fin</th>
                        <th scope="col">Duración</th>
                        <th scope="col">Ubicación (patio › sector › fila › posición › nivel)</th>
                    </tr>
                </x-slot:head>
                @foreach ($ubicaciones as $ubicacion)
                    <tr>
                        <td class="whitespace-nowrap">{{ $ubicacion->fecha_inicio->format('d/m/Y') }}</td>
                        <td class="whitespace-nowrap">
                            @if ($ubicacion->fecha_fin)
                                {{ $ubicacion->fecha_fin->format('d/m/Y') }}
                            @else
                                <x-ui.badge tone="emerald">Vigente</x-ui.badge>
                            @endif
                        </td>
                        <td class="whitespace-nowrap">
                            {{ $ubicacion->duracion_dias }} {{ $ubicacion->duracion_dias === 1 ? 'día' : 'días' }}
                        </td>
                        <td>@include('vehiculos._ubicacion', ['ubicacion' => $ubicacion])</td>
                    </tr>
                @endforeach
            </x-ui.table>
            <x-ui.pagination :paginator="$ubicaciones" />
        @endif
    </x-ui.card>
@endsection
