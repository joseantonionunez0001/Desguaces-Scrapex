@extends('layouts.app')

@section('title', 'Vehículos')

@section('content')
    <x-ui.page-header title="Vehículos" subtitle="Vehículos recibidos, su estado de tratamiento y desguace de destino."
        :breadcrumbs="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Vehículos'],
        ]">
        <x-slot:actions>
            @can('vehiculos.crear')
                <x-ui.button :href="route('vehiculos.create')" icon="plus">Registrar vehículo</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.filters columns="sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
        <x-form.input name="q" label="Buscar" :value="$filtros['q'] ?? null" :use-old="false"
            placeholder="Matrícula, marca, modelo o bastidor" maxlength="100" class="sm:col-span-2 lg:col-span-1" />
        <x-form.select name="desguace" label="Desguace" :options="$desguaces" :value="$filtros['desguace'] ?? null"
            :use-old="false" placeholder="Todos" />
        <x-form.select name="estado" label="Estado de tratamiento" :options="$estados" :value="$filtros['estado'] ?? null"
            :use-old="false" placeholder="Todos" />
        <x-form.input name="desde" label="Llegada desde" type="date" :value="$filtros['desde'] ?? null" :use-old="false" />
        <x-form.input name="hasta" label="Llegada hasta" type="date" :value="$filtros['hasta'] ?? null" :use-old="false" />
    </x-ui.filters>

    <x-ui.card :padding="false">
        @if ($vehiculos->isEmpty())
            @if (count($filtros) > 0)
                <x-ui.empty-state title="Sin resultados" message="Ningún vehículo coincide con los filtros aplicados." icon="search">
                    <x-ui.button :href="route('vehiculos.index')" variant="outline">Limpiar filtros</x-ui.button>
                </x-ui.empty-state>
            @else
                <x-ui.empty-state title="No hay vehículos registrados" message="Cuando se registre el primer vehículo aparecerá aquí." icon="truck">
                    @can('vehiculos.crear')
                        <x-ui.button :href="route('vehiculos.create')" icon="plus">Registrar vehículo</x-ui.button>
                    @endcan
                </x-ui.empty-state>
            @endif
        @else
            <x-ui.table>
                <x-slot:head>
                    <tr>
                        <th scope="col">Matrícula</th>
                        <th scope="col">Marca</th>
                        <th scope="col">Modelo</th>
                        <th scope="col">Bastidor</th>
                        <th scope="col">Llegada</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Desguace</th>
                        <th scope="col" class="text-right">Acciones</th>
                    </tr>
                </x-slot:head>
                @foreach ($vehiculos as $vehiculo)
                    <tr>
                        <td class="font-semibold text-blue-950">
                            <a href="{{ route('vehiculos.show', $vehiculo) }}" class="hover:underline">{{ $vehiculo->matricula }}</a>
                        </td>
                        <td>{{ $vehiculo->marca }}</td>
                        <td>{{ $vehiculo->modelo }}</td>
                        <td class="font-mono text-xs">{{ $vehiculo->numero_bastidor }}</td>
                        <td class="whitespace-nowrap">{{ $vehiculo->fecha_llegada->format('d/m/Y') }}</td>
                        <td><x-ui.badge tone="blue">{{ $vehiculo->estado_tratamiento }}</x-ui.badge></td>
                        <td>{{ $vehiculo->desguace?->nombre ?? '—' }}</td>
                        <td>
                            <div class="flex justify-end gap-1.5">
                                @can('vehiculos.ver')
                                    <x-ui.button :href="route('vehiculos.show', $vehiculo)" variant="outline" size="sm" icon="eye"
                                        aria-label="Consultar {{ $vehiculo->matricula }}" title="Consultar" />
                                @endcan
                                @can('vehiculos.editar')
                                    <x-ui.button :href="route('vehiculos.edit', $vehiculo)" variant="secondary" size="sm" icon="pencil"
                                        aria-label="Editar {{ $vehiculo->matricula }}" title="Editar" />
                                @endcan
                                @can('vehiculos.eliminar')
                                    <x-ui.delete-button :action="route('vehiculos.destroy', $vehiculo)" :icon-only="true"
                                        label="Eliminar {{ $vehiculo->matricula }}"
                                        message="¿Eliminar el vehículo {{ $vehiculo->matricula }}? Esta acción no se puede deshacer." />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
            <x-ui.pagination :paginator="$vehiculos" />
        @endif
    </x-ui.card>
@endsection
