{{--
    Navegación jerárquica del almacén (interior o exterior).

    Variables: $zona, $desguaces, $desguace, $migas (camino elegido), $consultaActual (camino como parámetros de URL),
    $tipoListado (definición del nivel mostrado), $padreId, $elementos (paginador) y $datosAdicionales
    (ocupación en el almacén exterior).
--}}
@php
    $rutaIndice = "almacen.{$zona}.index";
    $articulosFemeninos = ['Estantería', 'Fila', 'Posición'];
@endphp

@if ($desguaces->isEmpty())
    <x-ui.card :padding="false">
        <x-ui.empty-state title="No hay desguaces" message="Para gestionar el almacén tiene que existir al menos un desguace." icon="building">
            @if (Route::has('desguaces.index'))
                @can('desguaces.ver')
                    <x-ui.button :href="route('desguaces.index')" variant="outline">Ir a desguaces</x-ui.button>
                @endcan
            @endif
        </x-ui.empty-state>
    </x-ui.card>
@else
    <x-ui.filters :action="route($rutaIndice)" columns="sm:grid-cols-2 lg:grid-cols-3">
        <x-form.select
            name="desguace"
            label="Desguace"
            :options="$desguaces->pluck('nombre', 'id_desguace')"
            :value="$desguace?->id_desguace"
            placeholder="Elige un desguace"
            :use-old="false"
        />
    </x-ui.filters>

    @if ($desguace === null)
        <x-ui.card :padding="false">
            <x-ui.empty-state title="Elige un desguace" message="Selecciona un desguace para ver y gestionar su estructura de almacén." icon="map-pin" />
        </x-ui.card>
    @else
        <x-ui.card title="Camino seleccionado" subtitle="Pulsa un nivel para volver a él." class="mb-4">
            <nav aria-label="Camino seleccionado en el almacén">
                <ol class="flex flex-wrap items-center gap-2">
                    @foreach ($migas as $miga)
                        <li class="flex items-center gap-2">
                            @if (! $loop->first)
                                <x-ui.icon name="chevron-right" class="size-4 text-slate-400" />
                            @endif
                            <a href="{{ $miga['url'] }}"
                               @if ($loop->last && $elementos !== null) aria-current="page" @endif
                               class="block rounded-lg border px-3 py-1.5 text-sm hover:border-blue-400 hover:bg-blue-50 {{ $loop->last ? 'border-blue-300 bg-blue-50' : 'border-slate-200 bg-white' }}">
                                <span class="block text-xs text-slate-500">{{ $miga['etiqueta'] }}</span>
                                <span class="font-semibold text-blue-950">{{ $miga['texto'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ol>
            </nav>
        </x-ui.card>

        @php
            $ultimaMiga = end($migas);
            $de = in_array($ultimaMiga['etiqueta'], $articulosFemeninos, true) ? 'de la' : 'del';
            $tipo = $tipoListado['tipo'];
            $idModalCrear = 'crear-'.$tipo;
        @endphp

        <x-ui.card
            :title="$tipoListado['plural'].' '.$de.' '.mb_strtolower($ultimaMiga['etiqueta']).' «'.$ultimaMiga['texto'].'»'"
            :subtitle="$elementos->total().' '.($elementos->total() === 1 ? $tipoListado['singular'] : mb_strtolower($tipoListado['plural']))"
            :padding="false"
        >
            <x-slot:actions>
                @can($tipo.'.crear')
                    <x-ui.button icon="plus" :data-modal-open="$idModalCrear">Nuevo {{ $tipoListado['singular'] }}</x-ui.button>
                @endcan
            </x-slot:actions>

            @if ($elementos->isEmpty())
                <x-ui.empty-state
                    :title="'No hay '.mb_strtolower($tipoListado['plural']).' en este nivel'"
                    message="Todavía no se ha creado ningún elemento aquí."
                    icon="archive"
                >
                    @can($tipo.'.crear')
                        <x-ui.button icon="plus" :data-modal-open="$idModalCrear">Nuevo {{ $tipoListado['singular'] }}</x-ui.button>
                    @endcan
                </x-ui.empty-state>
            @else
                <x-ui.table>
                    <x-slot:head>
                        <tr>
                            @foreach ($tipoListado['campos'] as $configuracion)
                                <th scope="col">{{ $configuracion['etiqueta'] }}</th>
                            @endforeach
                            @if ($tipoListado['relacion_hijos'])
                                <th scope="col">{{ $tipoListado['etiqueta_hijos'] }}</th>
                            @endif
                            @if ($tipoListado['tipo'] === 'posiciones')
                                <th scope="col">Ocupación</th>
                            @elseif ($tipoListado['tipo'] === 'niveles')
                                <th scope="col">Estado</th>
                            @endif
                            <th scope="col" class="text-right">Acciones</th>
                        </tr>
                    </x-slot:head>

                    @foreach ($elementos as $elemento)
                        @php
                            $idElemento = $elemento->getKey();
                            $tituloElemento = \App\Services\EstructuraAlmacen::titulo($tipoListado['tipo'], $elemento);
                        @endphp
                        <tr>
                            @foreach ($tipoListado['campos'] as $campo => $configuracion)
                                <td @class(['font-medium text-slate-900' => $loop->first])>{{ $elemento->{$campo} }}</td>
                            @endforeach

                            @if ($tipoListado['relacion_hijos'])
                                <td>
                                    <x-ui.badge tone="blue">{{ $elemento->{$tipoListado['relacion_hijos'].'_count'} }}</x-ui.badge>
                                </td>
                            @endif

                            @if ($tipoListado['tipo'] === 'posiciones')
                                @php
                                    $ocupados = $datosAdicionales['ocupadosPorPosicion'][$idElemento] ?? 0;
                                    $capacidad = (int) $elemento->capacidad_maxima;
                                @endphp
                                <td>
                                    <x-ui.badge :tone="$ocupados >= $capacidad ? 'red' : ($ocupados > 0 ? 'amber' : 'emerald')">
                                        {{ $ocupados }} / {{ $capacidad }}
                                    </x-ui.badge>
                                    <span class="sr-only">vehículos ubicados de {{ $capacidad }} plazas</span>
                                </td>
                            @elseif ($tipoListado['tipo'] === 'niveles')
                                @php($ubicacionVigente = $datosAdicionales['ubicacionPorNivel'][$idElemento] ?? null)
                                <td>
                                    @if ($ubicacionVigente)
                                        <x-ui.badge tone="red">Ocupado</x-ui.badge>
                                        @if ($ubicacionVigente->vehiculo)
                                            @if (Route::has('vehiculos.show'))
                                                @can('vehiculos.ver')
                                                    <a href="{{ route('vehiculos.show', $ubicacionVigente->vehiculo) }}" class="ml-1 text-sm font-medium text-blue-700 hover:underline">{{ $ubicacionVigente->vehiculo->matricula }}</a>
                                                @else
                                                    <span class="ml-1 text-sm text-slate-600">{{ $ubicacionVigente->vehiculo->matricula }}</span>
                                                @endcan
                                            @else
                                                <span class="ml-1 text-sm text-slate-600">{{ $ubicacionVigente->vehiculo->matricula }}</span>
                                            @endif
                                        @endif
                                    @else
                                        <x-ui.badge tone="emerald">Libre</x-ui.badge>
                                    @endif
                                </td>
                            @endif

                            <td>
                                <div class="flex flex-wrap justify-end gap-1">
                                    @if ($tipoListado['clave'])
                                        <x-ui.button
                                            size="sm"
                                            variant="outline"
                                            icon="chevron-right"
                                            :href="route($rutaIndice, $consultaActual + [$tipoListado['clave'] => $idElemento])"
                                            :aria-label="'Abrir '.$tituloElemento"
                                        >Abrir</x-ui.button>
                                    @endif
                                    @can($tipo.'.editar')
                                        <x-ui.button
                                            size="sm"
                                            variant="outline"
                                            icon="pencil"
                                            :data-modal-open="'editar-'.$tipo.'-'.$idElemento"
                                            :aria-label="'Editar '.$tituloElemento"
                                        >Editar</x-ui.button>
                                    @endcan
                                    @can($tipo.'.eliminar')
                                        <x-ui.delete-button
                                            :action="route('almacen.'.$zona.'.'.$tipo.'.destroy', $idElemento)"
                                            :message="'¿Eliminar el '.$tipoListado['singular'].' «'.$tituloElemento.'»? Dejará de mostrarse en el almacén.'"
                                            :label="'Eliminar '.$tituloElemento"
                                            :icon-only="true"
                                        />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>
            @endif
        </x-ui.card>

        <div class="mt-4">
            <x-ui.pagination :paginator="$elementos" />
        </div>

        {{-- Modales de alta y edición (el servidor vuelve a comprobar los permisos). --}}
        @can($tipo.'.crear')
            <x-ui.modal :id="$idModalCrear" :title="'Nuevo '.$tipoListado['singular']" size="sm" :open="old('_modal') === $idModalCrear">
                @include('almacen.partials.formulario-elemento', [
                    'definicion' => $tipoListado,
                    'accion' => route("almacen.{$zona}.{$tipo}.store"),
                    'metodo' => 'POST',
                    'elemento' => null,
                    'idModal' => $idModalCrear,
                    'padreId' => $padreId,
                    'consultaActual' => $consultaActual,
                ])
            </x-ui.modal>
        @endcan

        @can($tipo.'.editar')
            @foreach ($elementos as $elemento)
                @php($idModalEditar = 'editar-'.$tipo.'-'.$elemento->getKey())
                <x-ui.modal :id="$idModalEditar" :title="'Editar '.$tipoListado['singular']" size="sm" :open="old('_modal') === $idModalEditar">
                    @include('almacen.partials.formulario-elemento', [
                        'definicion' => $tipoListado,
                        'accion' => route("almacen.{$zona}.{$tipo}.update", $elemento->getKey()),
                        'metodo' => 'PUT',
                        'elemento' => $elemento,
                        'idModal' => $idModalEditar,
                        'padreId' => $elemento->{$tipoListado['columna_padre']},
                        'consultaActual' => $consultaActual,
                    ])
                </x-ui.modal>
            @endforeach
        @endcan
    @endif
@endif
