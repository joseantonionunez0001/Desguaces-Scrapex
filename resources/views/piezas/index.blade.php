@extends('layouts.app')

@section('title', 'Piezas')

@section('content')
    <x-ui.page-header title="Piezas" subtitle="Catálogo de piezas, precios y existencias."
        :breadcrumbs="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Piezas'],
        ]">
        <x-slot:actions>
            {{-- El alta registra también la Entrada inicial: hacen falta los dos permisos. --}}
            @if (auth()->user()->can('piezas.crear') && auth()->user()->can('movimientos.crear'))
                <x-ui.button :href="route('piezas.create')" icon="plus">Registrar pieza</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Filtros combinables. Se conservan al cambiar de página; «Limpiar» los quita todos. --}}
    <x-ui.filters columns="sm:grid-cols-2 lg:grid-cols-4">
        <x-form.input name="q" label="Buscar por nombre" :value="$filtros['q'] ?? null" :use-old="false"
            placeholder="Nombre de la pieza" maxlength="100" />
        <x-form.select name="marca" label="Marca" :options="$marcas" :value="$filtros['marca'] ?? null"
            :use-old="false" placeholder="Todas" />
        <x-form.select name="modelo" label="Modelo" :options="$modelos" :padres="$marcaDeCadaModelo" data-depende-de="marca"
            :value="$filtros['modelo'] ?? null" :use-old="false" placeholder="Todos" />
        <x-form.select name="categoria" label="Categoría" :options="$categorias" :value="$filtros['categoria'] ?? null"
            :use-old="false" placeholder="Todas" />
        <x-form.select name="tipo" label="Tipo de pieza" :options="$tiposPieza" :padres="$categoriaDeCadaTipo" data-depende-de="categoria"
            :value="$filtros['tipo'] ?? null" :use-old="false" placeholder="Todos" />
        <x-form.select name="estado" label="Estado físico" :options="$estados" :value="$filtros['estado'] ?? null"
            :use-old="false" placeholder="Todos" />
        <x-form.select name="disponibilidad" label="Disponibilidad" :options="$disponibilidades" :use-old="false" placeholder="Todas"
            :value="$filtros['disponibilidad'] ?? null" />
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
                        <th scope="col">Vehículo</th>
                        <th scope="col">Categoría · tipo</th>
                        <th scope="col" class="text-right">Precio</th>
                        <th scope="col" class="text-right">Stock</th>
                        <th scope="col" class="text-right">Mínimo</th>
                        <th scope="col">Estado físico</th>
                        <th scope="col">Disponibilidad</th>
                        <th scope="col" class="text-right">Acciones</th>
                    </tr>
                </x-slot:head>
                @foreach ($piezas as $pieza)
                    <tr>
                        <td class="font-semibold text-blue-950">
                            <a href="{{ route('piezas.show', $pieza) }}" class="hover:underline">{{ $pieza->nombre }}</a>
                        </td>
                        <td>
                            @if ($pieza->modeloVehiculo)
                                {{ $pieza->modeloVehiculo->marca?->nombre }} {{ $pieza->modeloVehiculo->nombre }}
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td>
                            {{ $pieza->tipoPieza?->categoriaRelacionada?->nombre ?? '—' }}
                            <span class="block text-xs text-slate-500">{{ $pieza->tipoPieza?->nombre }}</span>
                        </td>
                        <td class="whitespace-nowrap text-right">{{ number_format((float) $pieza->precio, 2, ',', '.') }} €</td>
                        <td class="text-right font-semibold tabular-nums">{{ $pieza->stock }}</td>
                        <td class="text-right tabular-nums">{{ $pieza->stock_minimo }}</td>
                        <td><x-ui.badge tone="blue">{{ $pieza->estado }}</x-ui.badge></td>
                        <td>@include('piezas._disponibilidad', ['pieza' => $pieza])</td>
                        <td>
                            <div class="flex justify-end gap-1.5">
                                <x-ui.button :href="route('piezas.show', $pieza)" variant="outline" size="sm" icon="eye"
                                    aria-label="Consultar {{ $pieza->nombre }}" title="Consultar" />
                                @can('piezas.editar')
                                    <x-ui.button :href="route('piezas.edit', $pieza)" variant="secondary" size="sm" icon="pencil"
                                        aria-label="Editar {{ $pieza->nombre }}" title="Editar" />
                                @endcan
                                @can('movimientos.crear')
                                    <x-ui.button :href="route('movimientos.create', ['pieza' => $pieza->id_pieza])" variant="secondary" size="sm" icon="arrows"
                                        aria-label="Registrar un movimiento de {{ $pieza->nombre }}" title="Registrar movimiento" />
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
