@extends('layouts.app')

@section('title', 'Tipos de pieza')

@section('content')
    <x-ui.page-header
        title="Tipos de pieza"
        subtitle="Cada tipo pertenece a una categoría. Las piezas concretas se registran en «Piezas»."
        :breadcrumbs="[['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Tipos de pieza']]">
        @can('tipo_piezas.crear')
            <x-slot:actions>
                <x-ui.button icon="plus" data-modal-open="crear-tipo">Nuevo tipo de pieza</x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <x-ui.filters columns="sm:grid-cols-2">
        <x-form.input name="q" label="Buscar" :value="$filtros['q']" :use-old="false" placeholder="Nombre o fabricante" maxlength="100" />
        <x-form.select name="categoria" label="Categoría" :options="$categorias" :value="$filtros['categoria']" placeholder="Todas" :use-old="false" />
    </x-ui.filters>

    <x-ui.card :padding="false">
        @if ($tipos->isEmpty())
            @if ($hayFiltros)
                <x-ui.empty-state title="Sin resultados" message="Ningún tipo de pieza coincide con la búsqueda." icon="search">
                    <x-ui.button :href="route('tipos-pieza.index')" variant="outline">Limpiar filtros</x-ui.button>
                </x-ui.empty-state>
            @elseif (empty($categorias))
                <x-ui.empty-state title="Primero crea una categoría" message="Cada tipo de pieza pertenece a una categoría." icon="tag">
                    @can('categorias.ver')
                        <x-ui.button :href="route('categorias.index')" icon="tag">Ir a categorías</x-ui.button>
                    @endcan
                </x-ui.empty-state>
            @else
                <x-ui.empty-state title="Todavía no hay tipos de pieza" message="Crea el primero, por ejemplo «Alternador» en «Electricidad»." icon="cog">
                    @can('tipo_piezas.crear')
                        <x-ui.button icon="plus" data-modal-open="crear-tipo">Nuevo tipo de pieza</x-ui.button>
                    @endcan
                </x-ui.empty-state>
            @endif
        @else
            <x-ui.table>
                <x-slot:head>
                    <tr>
                        <th scope="col">Nombre</th>
                        <th scope="col">Categoría</th>
                        <th scope="col">Fabricante</th>
                        <th scope="col" class="text-right">Piezas</th>
                        <th scope="col" class="text-right">Acciones</th>
                    </tr>
                </x-slot:head>
                @foreach ($tipos as $tipo)
                    <tr>
                        <td class="font-medium text-slate-900">{{ $tipo->nombre }}</td>
                        <td>{{ $tipo->categoriaRelacionada?->nombre ?? '—' }}</td>
                        <td>{{ $tipo->fabricante ?: '—' }}</td>
                        <td class="text-right tabular-nums">
                            <a href="{{ route('piezas.index', ['categoria' => $tipo->id_categoria, 'tipo' => $tipo->id_pieza]) }}" class="hover:underline">{{ $tipo->piezas_count }}</a>
                        </td>
                        <td>
                            <div class="flex justify-end gap-2">
                                @can('tipo_piezas.editar')
                                    <x-ui.button variant="outline" size="sm" icon="pencil" data-modal-open="editar-tipo-pieza-{{ $tipo->id_pieza }}"
                                        aria-label="Editar el tipo de pieza {{ $tipo->nombre }}">Editar</x-ui.button>
                                @endcan
                                @can('tipo_piezas.eliminar')
                                    <x-ui.delete-button
                                        :action="route('tipos-pieza.destroy', $tipo)"
                                        message="¿Seguro que quieres eliminar el tipo de pieza «{{ $tipo->nombre }}»? Esta acción no se puede deshacer."
                                        label="Eliminar el tipo de pieza {{ $tipo->nombre }}"
                                        :icon-only="true" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
            <x-ui.pagination :paginator="$tipos" />
        @endif
    </x-ui.card>

    @can('tipo_piezas.crear')
        <x-ui.modal id="crear-tipo" title="Nuevo tipo de pieza" :open="old('_modal') === 'crear-tipo'">
            @include('tipos-pieza._form', [
                'modalId' => 'crear-tipo',
                'accion' => route('tipos-pieza.store'),
                'tipoPieza' => null,
                'categorias' => $categorias,
                'categoriaPreseleccionada' => $filtros['categoria'],
            ])
        </x-ui.modal>
    @endcan

    @can('tipo_piezas.editar')
        @foreach ($tipos as $tipo)
            <x-ui.modal :id="'editar-tipo-pieza-'.$tipo->id_pieza" title="Editar tipo de pieza" :open="old('_modal') === 'editar-tipo-pieza-'.$tipo->id_pieza">
                @include('tipos-pieza._form', [
                    'modalId' => 'editar-tipo-pieza-'.$tipo->id_pieza,
                    'accion' => route('tipos-pieza.update', $tipo),
                    'tipoPieza' => $tipo,
                    'categorias' => $categorias,
                ])
            </x-ui.modal>
        @endforeach
    @endcan
@endsection
