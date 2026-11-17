@extends('layouts.app')

@section('title', 'Tipos de pieza')

@section('content')
    <x-ui.page-header
        title="Tipos de pieza"
        subtitle="Tipos de pieza, fabricantes y categoría a la que pertenecen."
        :breadcrumbs="[['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Tipos de pieza']]">
        @can('tipo_piezas.crear')
            <x-slot:actions>
                <x-ui.button icon="plus" data-modal-open="crear-tipo-pieza">Nuevo tipo de pieza</x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <x-ui.filters columns="sm:grid-cols-2">
        <x-form.input name="q" label="Buscar" :value="$filtros['q']" :use-old="false" placeholder="Nombre, fabricante o categoría" />
        <x-form.select name="categoria" label="Categoría relacionada" :options="$categorias" :value="$filtros['categoria']" placeholder="Todas" :use-old="false" />
    </x-ui.filters>

    <x-ui.card :padding="false">
        @if ($tiposPieza->isEmpty())
            @if ($hayFiltros)
                <x-ui.empty-state title="Sin resultados" message="Ningún tipo de pieza coincide con la búsqueda o el filtro aplicados." icon="search">
                    <x-ui.button :href="route('tipos-pieza.index')" variant="outline">Limpiar filtros</x-ui.button>
                </x-ui.empty-state>
            @else
                <x-ui.empty-state title="Todavía no hay tipos de pieza" message="Crea el primer tipo de pieza para poder dar de alta piezas." icon="squares">
                    @can('tipo_piezas.crear')
                        <x-ui.button icon="plus" data-modal-open="crear-tipo-pieza">Nuevo tipo de pieza</x-ui.button>
                    @endcan
                </x-ui.empty-state>
            @endif
        @else
            <x-ui.table>
                <x-slot:head>
                    <tr>
                        <th scope="col">Nombre</th>
                        <th scope="col">Fabricante</th>
                        <th scope="col">Categoría (texto)</th>
                        <th scope="col">Categoría relacionada</th>
                        <th scope="col" class="text-right">Piezas</th>
                        <th scope="col" class="text-right">Acciones</th>
                    </tr>
                </x-slot:head>
                @foreach ($tiposPieza as $tipoPieza)
                    <tr>
                        <td class="font-medium text-slate-900">{{ $tipoPieza->nombre }}</td>
                        <td>{{ $tipoPieza->fabricante }}</td>
                        <td>{{ $tipoPieza->categoria }}</td>
                        <td>
                            @if ($tipoPieza->categoriaRelacionada)
                                {{ $tipoPieza->categoriaRelacionada->nombre }}
                                <span class="block text-xs text-slate-500">{{ $tipoPieza->categoriaRelacionada->desguace?->nombre }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-right tabular-nums">{{ $tipoPieza->piezas_count }}</td>
                        <td>
                            <div class="flex justify-end gap-2">
                                @can('tipo_piezas.editar')
                                    <x-ui.button variant="outline" size="sm" icon="pencil" data-modal-open="editar-tipo-pieza-{{ $tipoPieza->id_pieza }}"
                                        aria-label="Editar el tipo de pieza {{ $tipoPieza->nombre }}">Editar</x-ui.button>
                                @endcan
                                @can('tipo_piezas.eliminar')
                                    <x-ui.delete-button
                                        :action="route('tipos-pieza.destroy', $tipoPieza)"
                                        message="¿Seguro que quieres eliminar el tipo de pieza «{{ $tipoPieza->nombre }}»? Esta acción no se puede deshacer."
                                        label="Eliminar el tipo de pieza {{ $tipoPieza->nombre }}"
                                        :icon-only="true" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
            <x-ui.pagination :paginator="$tiposPieza" />
        @endif
    </x-ui.card>

    @can('tipo_piezas.crear')
        <x-ui.modal id="crear-tipo-pieza" title="Nuevo tipo de pieza" :open="old('_modal') === 'crear-tipo-pieza'">
            @include('tipos-pieza._form', [
                'modalId' => 'crear-tipo-pieza',
                'accion' => route('tipos-pieza.store'),
                'tipoPieza' => null,
            ])
        </x-ui.modal>
    @endcan

    @can('tipo_piezas.editar')
        @foreach ($tiposPieza as $tipoPieza)
            <x-ui.modal :id="'editar-tipo-pieza-'.$tipoPieza->id_pieza" title="Editar tipo de pieza" :open="old('_modal') === 'editar-tipo-pieza-'.$tipoPieza->id_pieza">
                @include('tipos-pieza._form', [
                    'modalId' => 'editar-tipo-pieza-'.$tipoPieza->id_pieza,
                    'accion' => route('tipos-pieza.update', $tipoPieza),
                    'tipoPieza' => $tipoPieza,
                ])
            </x-ui.modal>
        @endforeach
    @endcan
@endsection
