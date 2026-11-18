@extends('layouts.app')

@section('title', 'Categorías')

@section('content')
    <x-ui.page-header
        title="Categorías"
        subtitle="Clasificación del inventario por desguace y módulo."
        :breadcrumbs="[['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Categorías']]">
        @can('categorias.crear')
            <x-slot:actions>
                <x-ui.button icon="plus" data-modal-open="crear-categoria">Nueva categoría</x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <x-ui.filters columns="sm:grid-cols-2">
        <x-form.input name="q" label="Buscar" :value="$filtros['q']" :use-old="false" placeholder="Nombre, desguace, módulo o matrícula" />
        <x-form.select name="desguace" label="Desguace" :options="$desguaces" :value="$filtros['desguace']" placeholder="Todos" :use-old="false" />
    </x-ui.filters>

    <x-ui.card :padding="false">
        @if ($categorias->isEmpty())
            @if ($hayFiltros)
                <x-ui.empty-state title="Sin resultados" message="Ninguna categoría coincide con la búsqueda o el filtro aplicados." icon="search">
                    <x-ui.button :href="route('categorias.index')" variant="outline">Limpiar filtros</x-ui.button>
                </x-ui.empty-state>
            @else
                <x-ui.empty-state title="Todavía no hay categorías" message="Crea la primera categoría para poder clasificar tipos de pieza y movimientos." icon="tag">
                    @can('categorias.crear')
                        <x-ui.button icon="plus" data-modal-open="crear-categoria">Nueva categoría</x-ui.button>
                    @endcan
                </x-ui.empty-state>
            @endif
        @else
            <x-ui.table>
                <x-slot:head>
                    <tr>
                        <th scope="col">Nombre</th>
                        <th scope="col">Desguace</th>
                        <th scope="col">Módulo</th>
                        <th scope="col">Vehículo</th>
                        <th scope="col" class="text-right">Tipos de pieza</th>
                        <th scope="col" class="text-right">Movimientos</th>
                        @canany(['categorias.editar', 'categorias.eliminar'])
                            <th scope="col" class="text-right">Acciones</th>
                        @endcanany
                    </tr>
                </x-slot:head>
                @foreach ($categorias as $categoria)
                    <tr>
                        <td class="font-medium text-slate-900">{{ $categoria->nombre }}</td>
                        <td>{{ $categoria->desguace?->nombre ?? '—' }}</td>
                        <td>{{ $categoria->modulo?->nombre_modulo ?? '—' }}</td>
                        <td>{{ $categoria->vehiculo?->matricula ?? '—' }}</td>
                        <td class="text-right tabular-nums">{{ $categoria->tipos_pieza_count }}</td>
                        <td class="text-right tabular-nums">{{ $categoria->movimientos_count }}</td>
                        @canany(['categorias.editar', 'categorias.eliminar'])
                        <td>
                            <div class="flex justify-end gap-2">
                                @can('categorias.editar')
                                    <x-ui.button variant="outline" size="sm" icon="pencil" data-modal-open="editar-categoria-{{ $categoria->categoria_id }}"
                                        aria-label="Editar la categoría {{ $categoria->nombre }}">Editar</x-ui.button>
                                @endcan
                                @can('categorias.eliminar')
                                    <x-ui.delete-button
                                        :action="route('categorias.destroy', $categoria)"
                                        message="¿Seguro que quieres eliminar la categoría «{{ $categoria->nombre }}»? Esta acción no se puede deshacer."
                                        label="Eliminar la categoría {{ $categoria->nombre }}"
                                        :icon-only="true" />
                                @endcan
                            </div>
                        </td>
                        @endcanany
                    </tr>
                @endforeach
            </x-ui.table>
            <x-ui.pagination :paginator="$categorias" />
        @endif
    </x-ui.card>

    @can('categorias.crear')
        <x-ui.modal id="crear-categoria" title="Nueva categoría" :open="old('_modal') === 'crear-categoria'">
            @include('categorias._form', [
                'modalId' => 'crear-categoria',
                'accion' => route('categorias.store'),
                'categoria' => null,
            ])
        </x-ui.modal>
    @endcan

    @can('categorias.editar')
        @foreach ($categorias as $categoria)
            <x-ui.modal :id="'editar-categoria-'.$categoria->categoria_id" title="Editar categoría" :open="old('_modal') === 'editar-categoria-'.$categoria->categoria_id">
                @include('categorias._form', [
                    'modalId' => 'editar-categoria-'.$categoria->categoria_id,
                    'accion' => route('categorias.update', $categoria),
                    'categoria' => $categoria,
                ])
            </x-ui.modal>
        @endforeach
    @endcan
@endsection
