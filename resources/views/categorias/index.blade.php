@extends('layouts.app')

@section('title', 'Categorías')

@section('content')
    <x-ui.page-header
        title="Categorías"
        subtitle="Clasificación del inventario por desguace y módulo, con sus tipos de pieza."
        :breadcrumbs="[['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Categorías']]">
        @can('categorias.crear')
            <x-slot:actions>
                <x-ui.button icon="plus" data-modal-open="crear-categoria">Nueva categoría</x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <x-ui.filters columns="sm:grid-cols-2">
        <x-form.input name="q" label="Buscar" :value="$filtros['q']" :use-old="false" placeholder="Categoría, marca, modelo, tipo de pieza, fabricante o matrícula" />
        <x-form.select name="desguace" label="Desguace" :options="$desguaces" :value="$filtros['desguace']" placeholder="Todos" :use-old="false" />
    </x-ui.filters>

    <x-ui.card :padding="false">
        @if ($categorias->isEmpty())
            @if ($hayFiltros)
                <x-ui.empty-state title="Sin resultados" message="Ninguna categoría ni tipo de pieza coincide con la búsqueda o el filtro aplicados." icon="search">
                    <x-ui.button :href="route('categorias.index')" variant="outline">Limpiar filtros</x-ui.button>
                </x-ui.empty-state>
            @else
                <x-ui.empty-state title="Todavía no hay categorías" message="Crea la primera categoría para poder añadir tipos de pieza y movimientos." icon="tag">
                    @can('categorias.crear')
                        <x-ui.button icon="plus" data-modal-open="crear-categoria">Nueva categoría</x-ui.button>
                    @endcan
                </x-ui.empty-state>
            @endif
        @else
            <ul class="divide-y divide-slate-200" aria-label="Listado de categorías">
                @foreach ($categorias as $categoria)
                    <li>
                        {{-- Acordeones nativos (categoría → marca → modelo): se abren y cierran con teclado sin JavaScript. Con búsqueda activa se abren todos. --}}
                        <details class="group" @if ($filtros['q'] !== '') open @endif>
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
                                    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                                        <h3 class="text-sm font-semibold text-slate-900">Tipos de pieza por marca y modelo</h3>
                                        @can('tipo_piezas.crear')
                                            <x-ui.button variant="outline" size="sm" icon="plus" data-modal-open="crear-tipo-{{ $categoria->categoria_id }}"
                                                aria-label="Añadir un tipo de pieza a {{ $categoria->nombre }}">Añadir tipo</x-ui.button>
                                        @endcan
                                    </div>

                                    @php
                                        // Marca → modelo → tipos de pieza. Los tipos sin marca o modelo se agrupan aparte.
                                        $tiposPorMarca = $categoria->tiposPieza
                                            ->groupBy(fn ($tipo) => $tipo->marca ?: 'Sin marca')
                                            ->sortKeys()
                                            ->map(fn ($tiposDeMarca) => $tiposDeMarca
                                                ->groupBy(fn ($tipo) => $tipo->modelo ?: 'Sin modelo')
                                                ->sortKeys());
                                    @endphp

                                    @forelse ($tiposPorMarca as $marca => $modelos)
                                        <details class="group/marca mb-3 rounded-md border border-slate-200 bg-white last:mb-0" @if ($filtros['q'] !== '') open @endif>
                                            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 rounded-md px-4 py-3 font-semibold text-blue-950 transition hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 [&::-webkit-details-marker]:hidden">
                                                <span>{{ $marca }}</span>
                                                <span class="flex items-center gap-3 text-sm font-normal text-slate-500">
                                                    {{ $modelos->count() }} {{ $modelos->count() === 1 ? 'modelo' : 'modelos' }}
                                                    <x-ui.icon name="chevron-down" class="size-4 transition group-open/marca:rotate-180" />
                                                </span>
                                            </summary>

                                            <div class="space-y-2 border-t border-slate-200 p-3">
                                                @foreach ($modelos as $modelo => $tiposDelModelo)
                                                    <details class="group/modelo rounded-md border border-slate-100 bg-slate-50" @if ($filtros['q'] !== '') open @endif>
                                                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 rounded-md px-3 py-2 text-sm font-medium text-slate-800 transition hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 [&::-webkit-details-marker]:hidden">
                                                            <span>{{ $modelo }}</span>
                                                            <span class="flex items-center gap-3 text-xs font-normal text-slate-500">
                                                                {{ $tiposDelModelo->count() }} {{ $tiposDelModelo->count() === 1 ? 'tipo de pieza' : 'tipos de pieza' }}
                                                                <x-ui.icon name="chevron-down" class="size-4 transition group-open/modelo:rotate-180" />
                                                            </span>
                                                        </summary>

                                                        <ul class="grid gap-2 p-3 sm:grid-cols-2 xl:grid-cols-3" aria-label="Tipos de pieza de {{ $marca }} {{ $modelo }}">
                                                            @foreach ($tiposDelModelo as $tipoPieza)
                                                                <li class="flex items-center justify-between gap-3 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm">
                                                                    <span class="min-w-0">
                                                                        <span class="block truncate font-medium text-slate-900">{{ $tipoPieza->nombre }}</span>
                                                                        <span class="block truncate text-xs text-slate-500">{{ $tipoPieza->fabricante }}</span>
                                                                    </span>
                                                                    <span class="flex shrink-0 items-center gap-2">
                                                                        <span class="whitespace-nowrap text-xs tabular-nums text-slate-500">{{ $tipoPieza->piezas_count }} piezas</span>
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
                                                                    </span>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </details>
                                                @endforeach
                                            </div>
                                        </details>
                                    @empty
                                        <p class="text-sm text-slate-500">
                                            {{ $filtros['q'] !== '' ? 'Ningún tipo de esta categoría coincide con la búsqueda.' : 'Sin tipos de pieza.' }}
                                        </p>
                                    @endforelse
                                @endcan

                                @canany(['categorias.editar', 'categorias.eliminar'])
                                    <div class="mt-4 flex justify-end gap-2 border-t border-slate-200 pt-4">
                                        @can('categorias.editar')
                                            <x-ui.button variant="outline" size="sm" icon="pencil" data-modal-open="editar-categoria-{{ $categoria->categoria_id }}"
                                                aria-label="Editar la categoría {{ $categoria->nombre }}">Editar categoría</x-ui.button>
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

    @can('tipo_piezas.crear')
        @foreach ($categorias as $categoria)
            <x-ui.modal :id="'crear-tipo-'.$categoria->categoria_id" title="Nuevo tipo de pieza" :open="old('_modal') === 'crear-tipo-'.$categoria->categoria_id">
                @include('tipos-pieza._form', [
                    'modalId' => 'crear-tipo-'.$categoria->categoria_id,
                    'accion' => route('tipos-pieza.store'),
                    'tipoPieza' => null,
                    'categorias' => $opcionesCategorias,
                    'categoriasTexto' => $categoriasTexto,
                    'categoriaPreseleccionada' => $categoria->categoria_id,
                ])
            </x-ui.modal>
        @endforeach
    @endcan

    @can('tipo_piezas.editar')
        @foreach ($categorias as $categoria)
            @foreach ($categoria->tiposPieza as $tipoPieza)
                <x-ui.modal :id="'editar-tipo-pieza-'.$tipoPieza->id_pieza" title="Editar tipo de pieza" :open="old('_modal') === 'editar-tipo-pieza-'.$tipoPieza->id_pieza">
                    @include('tipos-pieza._form', [
                        'modalId' => 'editar-tipo-pieza-'.$tipoPieza->id_pieza,
                        'accion' => route('tipos-pieza.update', $tipoPieza),
                        'tipoPieza' => $tipoPieza,
                        'categorias' => $opcionesCategorias,
                        'categoriasTexto' => $categoriasTexto,
                    ])
                </x-ui.modal>
            @endforeach
        @endforeach
    @endcan
@endsection
