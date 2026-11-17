@extends('layouts.app')

@section('title', 'Asignar ubicación · '.$vehiculo->matricula)

@section('content')
    @php
        $hayFicha = Route::has('vehiculos.show');
        $hayHistorial = Route::has('vehiculos.ubicaciones.index');
        $enlaceAsignar = route('vehiculos.ubicaciones.asignar', $vehiculo);
        $migasDeVehiculo = [['label' => 'Inicio', 'url' => route('dashboard')]];
        if (Route::has('vehiculos.index')) {
            $migasDeVehiculo[] = ['label' => 'Vehículos', 'url' => route('vehiculos.index')];
        }
        if ($hayFicha) {
            $migasDeVehiculo[] = ['label' => $vehiculo->matricula, 'url' => route('vehiculos.show', $vehiculo)];
        }
        $migasDeVehiculo[] = ['label' => 'Asignar ubicación'];
    @endphp

    <x-ui.page-header
        title="Asignar ubicación"
        :subtitle="'Vehículo '.$vehiculo->matricula.' · '.$vehiculo->marca.' '.$vehiculo->modelo"
        :breadcrumbs="$migasDeVehiculo"
    >
        <x-slot:actions>
            @if ($hayHistorial)
                <x-ui.button :href="route('vehiculos.ubicaciones.index', $vehiculo)" variant="outline" icon="clock">Historial</x-ui.button>
            @endif
            @if ($hayFicha)
                <x-ui.button :href="route('vehiculos.show', $vehiculo)" variant="outline" icon="arrow-left">Volver a la ficha</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-1">
            <x-ui.card title="Vehículo">
                <dl class="grid grid-cols-3 gap-x-3 gap-y-2 text-sm">
                    <dt class="text-slate-500">Matrícula</dt>
                    <dd class="col-span-2 font-semibold text-slate-900">{{ $vehiculo->matricula }}</dd>
                    <dt class="text-slate-500">Vehículo</dt>
                    <dd class="col-span-2">{{ $vehiculo->marca }} {{ $vehiculo->modelo }}</dd>
                    <dt class="text-slate-500">Bastidor</dt>
                    <dd class="col-span-2 break-all">{{ $vehiculo->numero_bastidor }}</dd>
                    <dt class="text-slate-500">Desguace</dt>
                    <dd class="col-span-2">{{ $vehiculo->desguace?->nombre ?? 'Sin desguace' }}</dd>
                    <dt class="text-slate-500">Estado</dt>
                    <dd class="col-span-2">{{ $vehiculo->estado_tratamiento }}</dd>
                </dl>
            </x-ui.card>

            <x-ui.card title="Ubicación vigente">
                @if ($ubicacionVigente)
                    <p class="flex items-start gap-2 text-sm font-medium text-slate-900">
                        <x-ui.icon name="map-pin" class="mt-0.5 size-5 shrink-0 text-blue-700" />
                        <span>{{ \App\Services\OcupacionAlmacen::describirNivel($ubicacionVigente->nivel) }}</span>
                    </p>
                    <p class="mt-2 text-sm text-slate-500">Desde el {{ $ubicacionVigente->fecha_inicio->format('d/m/Y') }}</p>
                @else
                    <x-ui.alert type="info">Este vehículo no tiene ninguna ubicación vigente.</x-ui.alert>
                @endif
            </x-ui.card>
        </div>

        <div class="space-y-6 lg:col-span-2">
            <x-ui.card title="Nueva ubicación" subtitle="Elige el destino paso a paso y confirma las fechas.">
                @if ($errors->any())
                    <x-ui.alert type="error" class="mb-4">
                        <p class="font-semibold">No se ha guardado la ubicación. Revisa los datos:</p>
                        <ul class="mt-1 list-inside list-disc">
                            @foreach ($errors->all() as $mensaje)
                                <li>{{ $mensaje }}</li>
                            @endforeach
                        </ul>
                    </x-ui.alert>
                @endif

                @if ($ubicacionVigente)
                    <x-ui.alert type="warning" class="mb-4">
                        Es un traslado: al guardar, la ubicación vigente se cerrará el día anterior a la fecha de inicio
                        de la nueva. La ubicación anterior no se borra, se conserva en el historial.
                    </x-ui.alert>
                @endif

                @if ($cascada['patios']->isEmpty())
                    <x-ui.empty-state
                        title="El desguace del vehículo no tiene patios"
                        message="Crea antes un patio, con sus sectores, filas, posiciones y niveles, en el almacén exterior."
                        icon="map"
                    >
                        @if (Route::has('almacen.exterior.index'))
                            @can('patios.ver')
                                <x-ui.button :href="route('almacen.exterior.index', ['desguace' => $vehiculo->id_desguace])" variant="outline">Ir al almacén exterior</x-ui.button>
                            @endcan
                        @endif
                    </x-ui.empty-state>
                @else
                    {{-- Paso 1: destino en cascada (por GET, funciona sin JavaScript). --}}
                    <form method="GET" action="{{ $enlaceAsignar }}" id="form-cascada" aria-label="Elegir destino">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-form.select
                                name="patio" label="1. Patio" data-cascada
                                :options="$cascada['patios']->mapWithKeys(fn ($patio) => [$patio->id_patio => $patio->codigo.' · '.$patio->nombre])"
                                :value="$cascada['patio']?->id_patio"
                                placeholder="Elige un patio"
                                :use-old="false"
                            />
                            <x-form.select
                                name="sector" label="2. Sector" data-cascada
                                :options="$cascada['sectores']->mapWithKeys(fn ($sector) => [$sector->id_sector => $sector->codigo.' · '.$sector->tipo_zona])"
                                :value="$cascada['sector']?->id_sector"
                                :placeholder="$cascada['patio'] ? 'Elige un sector' : 'Elige antes un patio'"
                                :disabled="$cascada['patio'] === null"
                                :use-old="false"
                            />
                            <x-form.select
                                name="fila" label="3. Fila" data-cascada
                                :options="$cascada['filas']->mapWithKeys(fn ($fila) => [$fila->id_fila => 'Fila '.$fila->numero_fila])"
                                :value="$cascada['fila']?->id_fila"
                                :placeholder="$cascada['sector'] ? 'Elige una fila' : 'Elige antes un sector'"
                                :disabled="$cascada['sector'] === null"
                                :use-old="false"
                            />
                            <x-form.select
                                name="posicion" label="4. Posición" data-cascada
                                :options="$cascada['posiciones']->mapWithKeys(fn ($posicion) => [$posicion->id_posicion => 'Posición '.$posicion->numero_posicion.' ('.($cascada['ocupadosPorPosicion'][$posicion->id_posicion] ?? 0).'/'.$posicion->capacidad_maxima.' ocupados)'])"
                                :value="$cascada['posicion']?->id_posicion"
                                :placeholder="$cascada['fila'] ? 'Elige una posición' : 'Elige antes una fila'"
                                :disabled="$cascada['fila'] === null"
                                :use-old="false"
                            />
                        </div>
                        <div class="mt-3">
                            <x-ui.button type="submit" variant="outline" icon="search" data-no-loading>Continuar</x-ui.button>
                        </div>
                    </form>

                    {{-- Paso 2: nivel y fechas. --}}
                    @if ($cascada['posicion'])
                        @php
                            $ubicacionActualId = $ubicacionVigente?->id_nivel;
                            $opcionesDeNivel = $cascada['niveles']->mapWithKeys(function ($nivel) use ($cascada, $ubicacionActualId) {
                                $ocupante = $cascada['ubicacionPorNivel'][$nivel->id_nivel] ?? null;
                                $estado = match (true) {
                                    $nivel->id_nivel === $ubicacionActualId => 'ubicación actual del vehículo',
                                    $ocupante !== null => 'ocupado'.($ocupante->vehiculo ? ' por '.$ocupante->vehiculo->matricula : ''),
                                    default => 'libre',
                                };

                                return [$nivel->id_nivel => 'Nivel '.$nivel->numero_nivel.' · '.$estado];
                            });
                        @endphp
                        <form method="POST" action="{{ route('vehiculos.ubicaciones.store', $vehiculo) }}" class="mt-6 border-t border-slate-200 pt-5">
                            @csrf
                            <input type="hidden" name="patio" value="{{ $cascada['patio']->id_patio }}">
                            <input type="hidden" name="sector" value="{{ $cascada['sector']->id_sector }}">
                            <input type="hidden" name="fila" value="{{ $cascada['fila']->id_fila }}">
                            <input type="hidden" name="posicion" value="{{ $cascada['posicion']->id_posicion }}">

                            <p class="mb-4 text-sm text-slate-600">
                                Destino elegido:
                                <span class="font-semibold text-slate-900">Patio {{ $cascada['patio']->codigo }} · Sector {{ $cascada['sector']->codigo }} · Fila {{ $cascada['fila']->numero_fila }} · Posición {{ $cascada['posicion']->numero_posicion }}</span>
                            </p>

                            @if ($cascada['niveles']->isEmpty())
                                <x-ui.alert type="warning">Esta posición no tiene niveles. Crea uno en el almacén exterior para poder ubicar el vehículo.</x-ui.alert>
                            @else
                                <div class="grid gap-4 sm:grid-cols-3">
                                    <x-form.select
                                        name="id_nivel" label="5. Nivel" class="sm:col-span-3"
                                        :options="$opcionesDeNivel"
                                        placeholder="Elige un nivel"
                                        :required="true"
                                    />
                                    <x-form.input
                                        name="fecha_inicio" label="Fecha de inicio" type="date" class="sm:col-span-1"
                                        :value="now()->toDateString()" :required="true"
                                    />
                                    <x-form.input
                                        name="fecha_fin" label="Fecha de fin (opcional)" type="date" class="sm:col-span-1"
                                        hint="Déjala vacía si el vehículo sigue en esta ubicación."
                                    />
                                </div>

                                <x-form.actions submit="Guardar ubicación" :cancel="$enlaceAsignar" />
                            @endif
                        </form>
                    @endif
                @endif
            </x-ui.card>

            <x-ui.card title="Últimas ubicaciones" subtitle="Las 5 más recientes; el historial nunca se borra." :padding="false">
                @if ($historialReciente->isEmpty())
                    <x-ui.empty-state title="Sin historial" message="Este vehículo todavía no ha tenido ninguna ubicación." icon="clock" />
                @else
                    <x-ui.table>
                        <x-slot:head>
                            <tr>
                                <th scope="col">Ubicación</th>
                                <th scope="col">Desde</th>
                                <th scope="col">Hasta</th>
                            </tr>
                        </x-slot:head>
                        @foreach ($historialReciente as $ubicacion)
                            <tr>
                                <td>{{ \App\Services\OcupacionAlmacen::describirNivel($ubicacion->nivel) }}</td>
                                <td class="whitespace-nowrap">{{ $ubicacion->fecha_inicio->format('d/m/Y') }}</td>
                                <td class="whitespace-nowrap">
                                    @if ($ubicacion->fecha_fin)
                                        {{ $ubicacion->fecha_fin->format('d/m/Y') }}
                                    @else
                                        <x-ui.badge tone="emerald">Vigente</x-ui.badge>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </x-ui.table>
                @endif
            </x-ui.card>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Mejora opcional: al elegir una opción de la cascada se carga el siguiente paso sin pulsar «Continuar».
        document.querySelectorAll('#form-cascada select[data-cascada]').forEach((selector) => {
            selector.addEventListener('change', () => selector.form.submit());
        });
    </script>
@endpush
