@extends('layouts.app')

@section('title', 'Movimientos')

@section('content')
    <x-ui.page-header
        title="Movimientos"
        subtitle="Entradas, compras, ventas, bajas y traslados de cada pieza, con su saldo."
        :breadcrumbs="[['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Movimientos']]">
        @can('movimientos.crear')
            <x-slot:actions>
                <x-ui.button :href="route('movimientos.create')" icon="plus">Registrar movimiento</x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <x-ui.filters columns="sm:grid-cols-2 lg:grid-cols-4">
        <x-form.input name="q" label="Buscar" :value="$filtros['q']" :use-old="false" placeholder="Pieza o motivo" maxlength="100" />
        <x-form.select name="accion" label="Acción" :options="$acciones" :value="$filtros['accion']" placeholder="Todas" :use-old="false" />
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
                <x-ui.empty-state title="Todavía no hay movimientos" message="Cuando se registren entradas, ventas o traslados aparecerán aquí." icon="arrows">
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
                        <th scope="col">Acción</th>
                        <th scope="col">Pieza</th>
                        <th scope="col" class="text-right">Cantidad</th>
                        <th scope="col">Origen → destino</th>
                        <th scope="col" class="text-right">Saldo</th>
                        <th scope="col">Usuario</th>
                    </tr>
                </x-slot:head>
                @foreach ($movimientos as $movimiento)
                    @include('movimientos._fila', ['movimiento' => $movimiento, 'mostrarPieza' => true])
                @endforeach
            </x-ui.table>
            <x-ui.pagination :paginator="$movimientos" />
        @endif
    </x-ui.card>
@endsection
