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
            @can('movimientos.crear')
                <x-ui.button :href="route('movimientos.create', ['pieza' => $pieza->id_pieza])" icon="arrows">Registrar movimiento</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    @if ($pieza->disponibilidad() !== \App\Models\Pieza::CON_STOCK)
        <x-ui.alert :type="$pieza->disponibilidad() === \App\Models\Pieza::AGOTADA ? 'error' : 'warning'" class="mb-6">
            @if ($pieza->disponibilidad() === \App\Models\Pieza::AGOTADA)
                <span class="font-semibold">Agotada:</span> no quedan unidades de esta pieza.
            @else
                <span class="font-semibold">Stock bajo:</span> quedan {{ $pieza->stock }} {{ $pieza->stock === 1 ? 'unidad' : 'unidades' }}
                y el mínimo es {{ $pieza->stock_minimo }}.
            @endif
        </x-ui.alert>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <x-ui.card title="Datos de la pieza" class="lg:col-span-2">
            <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nombre</dt>
                    <dd class="mt-1 text-sm font-semibold text-blue-950">{{ $pieza->nombre }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Vehículo</dt>
                    <dd class="mt-1 text-sm">
                        {{ $pieza->modeloVehiculo ? $pieza->modeloVehiculo->marca?->nombre.' '.$pieza->modeloVehiculo->nombre : 'Sin marca ni modelo registrados' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Estado físico</dt>
                    <dd class="mt-1 text-sm"><x-ui.badge tone="blue">{{ $pieza->estado }}</x-ui.badge></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Disponibilidad</dt>
                    <dd class="mt-1 text-sm">@include('piezas._disponibilidad', ['pieza' => $pieza])</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Precio</dt>
                    <dd class="mt-1 text-sm">{{ number_format((float) $pieza->precio, 2, ',', '.') }} €</dd>
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

        <x-ui.card title="Clasificación">
            <dl class="space-y-4">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Categoría</dt>
                    <dd class="mt-1 text-sm">{{ $pieza->tipoPieza?->categoriaRelacionada?->nombre ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tipo de pieza</dt>
                    <dd class="mt-1 text-sm">{{ $pieza->tipoPieza?->nombre ?? '—' }}</dd>
                </div>
                @if ($pieza->tipoPieza?->fabricante)
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fabricante</dt>
                        <dd class="mt-1 text-sm">{{ $pieza->tipoPieza->fabricante }}</dd>
                    </div>
                @endif
            </dl>
        </x-ui.card>
    </div>

    <x-ui.card title="Existencias por ubicación" subtitle="Unidades en cada hueco del almacén interior." class="mt-6" :padding="false">
        @if ($existencias->isEmpty())
            <x-ui.empty-state title="Sin existencias" message="No quedan unidades de esta pieza en ningún hueco." icon="archive" />
        @else
            <x-ui.table>
                <x-slot:head>
                    <tr>
                        <th scope="col">Ubicación</th>
                        <th scope="col" class="text-right">Unidades</th>
                    </tr>
                </x-slot:head>
                @foreach ($existencias as $existencia)
                    <tr>
                        <td>
                            @if ($existencia->estaPendienteDeUbicar())
                                <x-ui.badge tone="amber">Pendiente de ubicar</x-ui.badge>
                                <span class="ml-1 text-xs text-slate-500">Stock anterior sin hueco asignado. Usa «Mover pieza» para colocarlo.</span>
                            @else
                                {{ $existencia->etiqueta?->rutaCompleta() }}
                                @if ($existencia->etiqueta?->trashed())
                                    <span class="text-xs text-slate-500">(hueco archivado)</span>
                                @endif
                            @endif
                        </td>
                        <td class="text-right font-semibold tabular-nums">{{ $existencia->cantidad }}</td>
                    </tr>
                @endforeach
                <tr class="bg-slate-50">
                    <th scope="row" class="text-left">Total</th>
                    <td class="text-right font-bold tabular-nums">{{ $pieza->stock }}</td>
                </tr>
            </x-ui.table>
        @endif
    </x-ui.card>

    <x-ui.card title="Historial de movimientos" subtitle="Fecha, usuario, acción, cantidad y saldo antes y después." class="mt-6" :padding="false">
        @if ($historial->isEmpty())
            <x-ui.empty-state title="Sin movimientos" message="Esta pieza todavía no tiene movimientos registrados." icon="arrows" />
        @else
            <x-ui.table>
                <x-slot:head>
                    <tr>
                        <th scope="col">Fecha</th>
                        <th scope="col">Acción</th>
                        <th scope="col" class="text-right">Cantidad</th>
                        <th scope="col">Origen → destino</th>
                        <th scope="col" class="text-right">Saldo</th>
                        <th scope="col">Usuario</th>
                    </tr>
                </x-slot:head>
                @foreach ($historial as $movimiento)
                    @include('movimientos._fila', ['movimiento' => $movimiento, 'mostrarPieza' => false])
                @endforeach
            </x-ui.table>
            <x-ui.pagination :paginator="$historial" />
        @endif
    </x-ui.card>
@endsection
