@extends('layouts.app')

@section('title', 'Categorías')

@section('content')
    <x-ui.page-header
        title="Categorías"
        subtitle="Clasificación del inventario de tu desguace. Los tipos se crean en «Tipos de pieza» y las piezas en «Piezas»."
        :breadcrumbs="[['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Categorías']]">
        @can('categorias.crear')
            <x-slot:actions>
                <x-ui.button icon="plus" data-modal-open="crear-categoria">Nueva categoría</x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <x-ui.filters columns="sm:grid-cols-2">
        <x-form.input name="q" label="Buscar" :value="$filtros['q']" :use-old="false" placeholder="Nombre de la categoría" maxlength="100" />
    </x-ui.filters>

    <x-ui.card :padding="false">
        @if ($categorias->isEmpty())
            @if ($hayFiltros)
                <x-ui.empty-state title="Sin resultados" message="Ninguna categoría coincide con la búsqueda." icon="search">
                    <x-ui.button :href="route('categorias.index')" variant="outline">Limpiar búsqueda</x-ui.button>
                </x-ui.empty-state>
            @else
                <x-ui.empty-state title="Todavía no hay categorías" message="Crea la primera categoría, por ejemplo «Electricidad», «Carrocería» o «Motor»." icon="tag">
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
                        <th scope="col" class="text-right">Tipos de pieza</th>
                        <th scope="col" class="text-right">Movimientos</th>
                        <th scope="col" class="text-right">Acciones</th>
                    </tr>
                </x-slot:head>
                @foreach ($categorias as $categoria)
                    <tr>
                        <td class="font-medium text-slate-900">{{ $categoria->nombre }}</td>
                        <td class="text-right tabular-nums">
                            @can('tipo_piezas.ver')
                                <a href="{{ route('tipos-pieza.index', ['categoria' => $categoria->categoria_id]) }}" class="hover:underline">{{ $categoria->tipos_pieza_count }}</a>
                            @else
                                {{ $categoria->tipos_pieza_count }}
                            @endcan
                        </td>
                        <td class="text-right tabular-nums">{{ $categoria->movimientos_count }}</td>
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
