@extends('layouts.app')

@section('title', 'Piezas')

@section('content')
    <x-ui.page-header title="Piezas" subtitle="Catálogo de piezas, precios y existencias."
        :breadcrumbs="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Piezas'],
        ]">
        <x-slot:actions>
            @can('piezas.crear')
                <x-ui.button :href="route('piezas.create')" icon="plus">Registrar pieza</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.filters columns="sm:grid-cols-2 lg:grid-cols-4">
        <x-form.input name="q" label="Buscar por nombre" :value="$filtros['q'] ?? null" :use-old="false"
            placeholder="Nombre de la pieza" maxlength="100" />
        <x-form.select name="tipo" label="Tipo de pieza" :options="$tiposPieza" :value="$filtros['tipo'] ?? null"
            :use-old="false" placeholder="Todos" />
        <x-form.select name="estado" label="Estado" :options="$estados" :value="$filtros['estado'] ?? null"
            :use-old="false" placeholder="Todos" />
        <x-form.select name="disponibilidad" label="Disponibilidad" :use-old="false" placeholder="Todas"
            :value="$filtros['disponibilidad'] ?? null"
            :options="['disponible' => 'Disponible (stock > 0)', 'agotada' => 'Sin stock', 'stock_bajo' => 'Stock bajo (≤ mínimo)']" />
    </x-ui.filters>

    <x-ui.card :padding="false">
        @if ($piezas->isEmpty())
            @if (count($filtros) > 0)
                <x-ui.empty-state title="Sin resultados" message="Ninguna pieza coincide con los filtros aplicados." icon="search">
                    <x-ui.button :href="route('piezas.index')" variant="outline">Limpiar filtros</x-ui.button>
                </x-ui.empty-state>
            @else
                <x-ui.empty-state title="No hay piezas registradas" message="Cuando se registre la primera pieza aparecerá aquí." icon="cog">
                    @can('piezas.crear')
                        <x-ui.button :href="route('piezas.create')" icon="plus">Registrar pieza</x-ui.button>
                    @endcan
                </x-ui.empty-state>
            @endif
        @else
            <x-ui.table>
                <x-slot:head>
                    <tr>
                        <th scope="col">Nombre</th>
                        <th scope="col">Tipo de pieza</th>
                        <th scope="col" class="text-right">Precio</th>
                        <th scope="col" class="text-right">Stock</th>
                        <th scope="col" class="text-right">Stock mínimo</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Disponibilidad</th>
                        <th scope="col" class="text-right">Acciones</th>
                    </tr>
                </x-slot:head>
                @foreach ($piezas as $pieza)
                    <tr>
                        <td class="font-semibold text-blue-950">
                            <a href="{{ route('piezas.show', $pieza) }}" class="hover:underline">{{ $pieza->nombre }}</a>
                        </td>
                        <td>{{ $pieza->tipoPieza?->nombre ?? '—' }}</td>
                        <td class="whitespace-nowrap text-right">{{ number_format((float) $pieza->precio, 2, ',', '.') }} €</td>
                        <td class="text-right">{{ $pieza->stock }}</td>
                        <td class="text-right">{{ $pieza->stock_minimo }}</td>
                        <td><x-ui.badge tone="blue">{{ $pieza->estado }}</x-ui.badge></td>
                        <td>@include('piezas._disponibilidad', ['pieza' => $pieza])</td>
                        <td>
                            <div class="flex justify-end gap-1.5">
                                @can('piezas.ver')
                                    <x-ui.button :href="route('piezas.show', $pieza)" variant="outline" size="sm" icon="eye"
                                        aria-label="Consultar {{ $pieza->nombre }}" title="Consultar" />
                                @endcan
                                @can('piezas.editar')
                                    <x-ui.button :href="route('piezas.edit', $pieza)" variant="secondary" size="sm" icon="pencil"
                                        aria-label="Editar {{ $pieza->nombre }}" title="Editar" />
                                @endcan
                                @can('piezas.eliminar')
                                    <x-ui.delete-button :action="route('piezas.destroy', $pieza)" :icon-only="true"
                                        label="Eliminar {{ $pieza->nombre }}"
                                        message="¿Eliminar la pieza {{ $pieza->nombre }}? Esta acción no se puede deshacer." />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
            <x-ui.pagination :paginator="$piezas" />
        @endif
    </x-ui.card>
@endsection
