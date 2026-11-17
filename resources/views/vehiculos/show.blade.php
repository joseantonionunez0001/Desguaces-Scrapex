@extends('layouts.app')

@section('title', 'Vehículo '.$vehiculo->matricula)

@section('content')
    <x-ui.page-header :title="$vehiculo->matricula" :subtitle="$vehiculo->marca.' '.$vehiculo->modelo"
        :breadcrumbs="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Vehículos', 'url' => route('vehiculos.index')],
            ['label' => $vehiculo->matricula],
        ]">
        <x-slot:actions>
            <x-ui.button :href="route('vehiculos.index')" variant="outline" icon="arrow-left">Volver al listado</x-ui.button>
            @can('vehiculos.editar')
                <x-ui.button :href="route('vehiculos.edit', $vehiculo)" variant="secondary" icon="pencil">Editar</x-ui.button>
            @endcan
            @can('vehiculos.eliminar')
                <x-ui.delete-button :action="route('vehiculos.destroy', $vehiculo)" label="Eliminar"
                    message="¿Eliminar el vehículo {{ $vehiculo->matricula }}? Esta acción no se puede deshacer." />
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-ui.card title="Datos del vehículo" class="lg:col-span-2">
            <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Matrícula</dt>
                    <dd class="mt-1 text-sm font-semibold text-blue-950">{{ $vehiculo->matricula }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Número de bastidor</dt>
                    <dd class="mt-1 font-mono text-sm">{{ $vehiculo->numero_bastidor }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Marca</dt>
                    <dd class="mt-1 text-sm">{{ $vehiculo->marca }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Modelo</dt>
                    <dd class="mt-1 text-sm">{{ $vehiculo->modelo }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fecha de llegada</dt>
                    <dd class="mt-1 text-sm">{{ $vehiculo->fecha_llegada->format('d/m/Y') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Estado de tratamiento</dt>
                    <dd class="mt-1 text-sm"><x-ui.badge tone="blue">{{ $vehiculo->estado_tratamiento }}</x-ui.badge></dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Desguace</dt>
                    <dd class="mt-1 text-sm">{{ $vehiculo->desguace?->nombre ?? '—' }}</dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card title="Ubicación actual">
            @if ($ubicacionActual)
                <p class="flex items-start gap-2 text-sm">
                    <x-ui.icon name="map-pin" class="mt-0.5 size-5 shrink-0 text-blue-700" />
                    <span>@include('vehiculos._ubicacion', ['ubicacion' => $ubicacionActual])</span>
                </p>
                <p class="mt-3 text-xs text-slate-500">Desde el {{ $ubicacionActual->fecha_inicio->format('d/m/Y') }}</p>
            @else
                <x-ui.alert type="warning">Este vehículo no tiene una ubicación asignada actualmente.</x-ui.alert>
            @endif

            <div class="mt-4 flex flex-wrap gap-2">
                @can('ubicaciones_vehiculo.ver')
                    <x-ui.button :href="route('vehiculos.ubicaciones.index', $vehiculo)" variant="outline" size="sm" icon="clock">
                        Ver historial de ubicaciones
                    </x-ui.button>
                @endcan
                @if (Route::has('vehiculos.ubicaciones.asignar'))
                    @can('ubicaciones_vehiculo.crear')
                        <x-ui.button :href="route('vehiculos.ubicaciones.asignar', $vehiculo)" size="sm" icon="map-pin">
                            Asignar ubicación
                        </x-ui.button>
                    @endcan
                @endif
            </div>
        </x-ui.card>

        <x-ui.card title="Categorías asociadas" subtitle="Categorías vinculadas a este vehículo." class="lg:col-span-3">
            @if ($vehiculo->categorias->isEmpty())
                <p class="text-sm text-slate-500">Este vehículo no tiene categorías asociadas.</p>
            @else
                <ul class="flex flex-wrap gap-2">
                    @foreach ($vehiculo->categorias as $categoria)
                        <li><x-ui.badge tone="slate">{{ $categoria->nombre }}</x-ui.badge></li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>
    </div>
@endsection
