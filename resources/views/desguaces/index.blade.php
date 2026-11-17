@extends('layouts.app')

@section('title', 'Desguaces')

@section('content')
    <x-ui.page-header
        title="Desguaces"
        subtitle="Instalaciones del grupo, con sus vehículos, módulos y patios."
        :breadcrumbs="[['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Desguaces']]">
        @can('desguaces.crear')
            <x-slot:actions>
                <x-ui.button icon="plus" data-modal-open="crear-desguace">Nuevo desguace</x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <x-ui.filters columns="sm:grid-cols-2">
        <x-form.input name="q" label="Buscar" :value="$filtros['q']" :use-old="false" placeholder="Nombre, dirección, teléfono o ubicación" />
    </x-ui.filters>

    <x-ui.card :padding="false">
        @if ($desguaces->isEmpty())
            @if ($hayFiltros)
                <x-ui.empty-state title="Sin resultados" message="Ningún desguace coincide con la búsqueda." icon="search">
                    <x-ui.button :href="route('desguaces.index')" variant="outline">Limpiar búsqueda</x-ui.button>
                </x-ui.empty-state>
            @else
                <x-ui.empty-state title="Todavía no hay desguaces" message="Los desguaces registrados aparecerán aquí." icon="building">
                    @can('desguaces.crear')
                        <x-ui.button icon="plus" data-modal-open="crear-desguace">Nuevo desguace</x-ui.button>
                    @endcan
                </x-ui.empty-state>
            @endif
        @else
            <x-ui.table>
                <x-slot:head>
                    <tr>
                        <th scope="col">Nombre</th>
                        <th scope="col">Dirección</th>
                        <th scope="col">Teléfono</th>
                        <th scope="col">Ubicación</th>
                        <th scope="col" class="text-right">Vehículos</th>
                        <th scope="col" class="text-right">Módulos</th>
                        <th scope="col" class="text-right">Patios</th>
                        <th scope="col" class="text-right">Acciones</th>
                    </tr>
                </x-slot:head>
                @foreach ($desguaces as $desguace)
                    <tr>
                        <td class="font-medium text-slate-900">{{ $desguace->nombre }}</td>
                        <td>{{ $desguace->direccion }}</td>
                        <td class="whitespace-nowrap">{{ $desguace->numero_telefono }}</td>
                        <td>{{ $desguace->{'ubicación'} }}</td>
                        <td class="text-right tabular-nums">{{ $desguace->vehiculos_count }}</td>
                        <td class="text-right tabular-nums">{{ $desguace->modulos_count }}</td>
                        <td class="text-right tabular-nums">{{ $desguace->patios_count }}</td>
                        <td>
                            <div class="flex justify-end gap-2">
                                <x-ui.button variant="outline" size="sm" icon="eye" data-modal-open="detalle-desguace-{{ $desguace->id_desguace }}"
                                    aria-label="Ver detalle del desguace {{ $desguace->nombre }}">Detalle</x-ui.button>
                                @can('desguaces.editar')
                                    <x-ui.button variant="outline" size="sm" icon="pencil" data-modal-open="editar-desguace-{{ $desguace->id_desguace }}"
                                        aria-label="Editar el desguace {{ $desguace->nombre }}">Editar</x-ui.button>
                                @endcan
                                @can('desguaces.eliminar')
                                    <x-ui.delete-button
                                        :action="route('desguaces.destroy', $desguace)"
                                        message="¿Seguro que quieres eliminar el desguace «{{ $desguace->nombre }}»? Esta acción no se puede deshacer."
                                        label="Eliminar el desguace {{ $desguace->nombre }}"
                                        :icon-only="true" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
            <x-ui.pagination :paginator="$desguaces" />
        @endif
    </x-ui.card>

    @foreach ($desguaces as $desguace)
        @include('desguaces._detalle', ['desguace' => $desguace])
    @endforeach

    @can('desguaces.crear')
        <x-ui.modal id="crear-desguace" title="Nuevo desguace" :open="old('_modal') === 'crear-desguace'">
            @include('desguaces._form', [
                'modalId' => 'crear-desguace',
                'accion' => route('desguaces.store'),
                'desguace' => null,
            ])
        </x-ui.modal>
    @endcan

    @can('desguaces.editar')
        @foreach ($desguaces as $desguace)
            <x-ui.modal :id="'editar-desguace-'.$desguace->id_desguace" title="Editar desguace" :open="old('_modal') === 'editar-desguace-'.$desguace->id_desguace">
                @include('desguaces._form', [
                    'modalId' => 'editar-desguace-'.$desguace->id_desguace,
                    'accion' => route('desguaces.update', $desguace),
                    'desguace' => $desguace,
                ])
            </x-ui.modal>
        @endforeach
    @endcan
@endsection
