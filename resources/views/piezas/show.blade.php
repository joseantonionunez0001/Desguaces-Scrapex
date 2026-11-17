@extends('layouts.app')

@section('title', 'Pieza '.$pieza->nombre)

@section('content')
    <x-ui.page-header :title="$pieza->nombre" :subtitle="$pieza->tipoPieza?->nombre"
        :breadcrumbs="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Piezas', 'url' => route('piezas.index')],
            ['label' => $pieza->nombre],
        ]">
        <x-slot:actions>
            <x-ui.button :href="route('piezas.index')" variant="outline" icon="arrow-left">Volver al listado</x-ui.button>
            @can('piezas.editar')
                <x-ui.button :href="route('piezas.edit', $pieza)" variant="secondary" icon="pencil">Editar</x-ui.button>
            @endcan
            @can('piezas.eliminar')
                <x-ui.delete-button :action="route('piezas.destroy', $pieza)" label="Eliminar"
                    message="¿Eliminar la pieza {{ $pieza->nombre }}? Esta acción no se puede deshacer." />
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-ui.card title="Datos de la pieza" class="lg:col-span-2">
            <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nombre</dt>
                    <dd class="mt-1 text-sm font-semibold text-blue-950">{{ $pieza->nombre }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Estado</dt>
                    <dd class="mt-1 text-sm"><x-ui.badge tone="blue">{{ $pieza->estado }}</x-ui.badge></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Precio</dt>
                    <dd class="mt-1 text-sm">{{ number_format((float) $pieza->precio, 2, ',', '.') }} €</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Disponibilidad</dt>
                    <dd class="mt-1 text-sm">@include('piezas._disponibilidad', ['pieza' => $pieza])</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Stock</dt>
                    <dd class="mt-1 text-sm">{{ $pieza->stock }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Stock mínimo</dt>
                    <dd class="mt-1 text-sm">{{ $pieza->stock_minimo }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Registrada por</dt>
                    <dd class="mt-1 text-sm">{{ $pieza->usuario?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fecha de registro</dt>
                    <dd class="mt-1 text-sm">{{ $pieza->created_at?->format('d/m/Y') ?? '—' }}</dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card title="Tipo de pieza">
            @if ($pieza->tipoPieza)
                <dl class="space-y-4">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nombre</dt>
                        <dd class="mt-1 text-sm">{{ $pieza->tipoPieza->nombre }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fabricante</dt>
                        <dd class="mt-1 text-sm">{{ $pieza->tipoPieza->fabricante }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Categoría</dt>
                        <dd class="mt-1 text-sm">{{ $pieza->tipoPieza->categoriaRelacionada?->nombre ?? $pieza->tipoPieza->categoria }}</dd>
                    </div>
                </dl>
            @else
                <p class="text-sm text-slate-500">Esta pieza no tiene un tipo de pieza asociado.</p>
            @endif
        </x-ui.card>
    </div>
@endsection
