@extends('layouts.app')

@section('title', 'Movimientos')

@section('content')
    <x-ui.page-header
        title="Movimientos"
        subtitle="Entradas, salidas y ajustes de inventario registrados por categoría."
        :breadcrumbs="[['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Movimientos']]">
        @can('movimientos.crear')
            <x-slot:actions>
                <x-ui.button :href="route('movimientos.create')" icon="plus">Registrar movimiento</x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <x-ui.filters columns="sm:grid-cols-2 lg:grid-cols-5">
        <x-form.input name="q" label="Buscar" :value="$filtros['q']" :use-old="false" placeholder="Tipo o categoría" />
        <x-form.select name="tipo" label="Tipo de movimiento" :options="$tipos" :value="$filtros['tipo']" placeholder="Todos" :use-old="false" />
        <x-form.select name="categoria" label="Categoría" :options="$categorias" :value="$filtros['categoria']" placeholder="Todas" :use-old="false" />
        <x-form.input name="desde" type="date" label="Desde" :value="$filtros['desde']" :use-old="false" />
        <x-form.input name="hasta" type="date" label="Hasta" :value="$filtros['hasta']" :use-old="false" />
    </x-ui.filters>

    <x-ui.card :padding="false">
        @if ($movimientos->isEmpty())
            @if ($hayFiltros)
                <x-ui.empty-state title="Sin resultados" message="Ningún movimiento coincide con la búsqueda o los filtros aplicados." icon="search">
                    <x-ui.button :href="route('movimientos.index')" variant="outline">Limpiar filtros</x-ui.button>
                </x-ui.empty-state>
            @else
                <x-ui.empty-state title="Todavía no hay movimientos" message="Cuando se registren entradas, salidas o ajustes aparecerán aquí." icon="arrows">
                    @can('movimientos.crear')
                        <x-ui.button :href="route('movimientos.create')" icon="plus">Registrar movimiento</x-ui.button>
                    @endcan
                </x-ui.empty-state>
            @endif
        @else
            <x-ui.table>
                <x-slot:head>
                    <tr>
                        <th scope="col">Fecha</th>
                        <th scope="col">Tipo de movimiento</th>
                        <th scope="col" class="text-right">Cantidad</th>
                        <th scope="col">Categoría</th>
                        <th scope="col" class="text-right">Acciones</th>
                    </tr>
                </x-slot:head>
                @foreach ($movimientos as $movimiento)
                    <tr>
                        <td class="whitespace-nowrap">{{ $movimiento->fecha->format('d/m/Y') }}</td>
                        <td><x-ui.badge tone="blue">{{ $movimiento->tipo_movimiento }}</x-ui.badge></td>
                        <td class="text-right font-semibold tabular-nums">{{ number_format($movimiento->cantidad, 0, ',', '.') }}</td>
                        <td>{{ $movimiento->categoria?->nombre ?? 'Categoría no disponible' }}</td>
                        <td>
                            <div class="flex justify-end gap-2">
                                <x-ui.button variant="outline" size="sm" icon="eye" data-modal-open="detalle-movimiento-{{ $movimiento->id_movimiento }}" aria-label="Ver detalle del movimiento {{ $movimiento->id_movimiento }}">
                                    Detalle
                                </x-ui.button>
                                @can('movimientos.eliminar')
                                    <x-ui.delete-button
                                        :action="route('movimientos.destroy', $movimiento)"
                                        message="¿Seguro que quieres eliminar este movimiento? Esta acción no se puede deshacer."
                                        label="Eliminar movimiento {{ $movimiento->id_movimiento }}"
                                        :icon-only="true" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
            <x-ui.pagination :paginator="$movimientos" />
        @endif
    </x-ui.card>

    @foreach ($movimientos as $movimiento)
        @include('movimientos._detalle', ['movimiento' => $movimiento])
    @endforeach
@endsection
