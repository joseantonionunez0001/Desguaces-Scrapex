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
            <ul class="divide-y divide-slate-200" aria-label="Listado de categorías">
                @foreach ($categorias as $categoria)
                    <li>
                        {{-- Acordeón nativo: se abre y se cierra con el teclado sin necesidad de JavaScript. --}}
                        <details class="group">
                            <summary class="flex cursor-pointer list-none items-center gap-4 bg-blue-50 px-5 py-4 transition hover:bg-blue-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 [&::-webkit-details-marker]:hidden">
                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold text-blue-700">{{ $categoria->nombre }}</p>
                                    <p class="truncate text-sm text-slate-600">
                                        {{ $categoria->desguace?->nombre ?? 'Sin desguace' }} · {{ $categoria->modulo?->nombre_modulo ?? 'Sin módulo' }} · {{ $categoria->vehiculo?->matricula ?? 'Sin vehículo' }}
                                    </p>
                                </div>
                                <p class="whitespace-nowrap text-sm tabular-nums text-slate-600">
                                    {{ $categoria->tipos_pieza_count }} tipos · {{ $categoria->movimientos_count }} movimientos
                                </p>
                                <x-ui.icon name="chevron-down" class="size-5 shrink-0 text-blue-700 transition group-open:rotate-180" />
                            </summary>

                            <div class="px-5 py-4">
                                @can('tipo_piezas.ver')
                                    <ul class="grid gap-x-8 gap-y-2 sm:grid-cols-2 lg:grid-cols-4" aria-label="Tipos de pieza de {{ $categoria->nombre }}">
                                        @forelse ($categoria->tiposPieza as $tipoPieza)
                                            <li class="flex items-baseline justify-between gap-3 text-sm">
                                                <span class="font-medium text-slate-900">
                                                    {{ $tipoPieza->nombre }}
                                                    @if ($tipoPieza->fabricante)
                                                        <span class="font-normal text-slate-500">· {{ $tipoPieza->fabricante }}</span>
                                                    @endif
                                                </span>
                                                <span class="whitespace-nowrap tabular-nums text-slate-500">{{ $tipoPieza->piezas_count }} piezas</span>
                                            </li>
                                        @empty
                                            <li class="col-span-full text-sm text-slate-500">Sin tipos de pieza</li>
                                        @endforelse
                                    </ul>
                                @endcan

                                @canany(['categorias.editar', 'categorias.eliminar'])
                                    <div class="mt-4 flex justify-end gap-2 border-t border-slate-200 pt-4">
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
                                @endcanany
                            </div>
                        </details>
                    </li>
                @endforeach
            </ul>
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
