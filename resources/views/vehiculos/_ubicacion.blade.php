{{--
    Jerarquía física de una ubicación: patio › sector › fila › posición › nivel.
    Variable: $ubicacion (UbicacionVehiculo con la jerarquía cargada, incluso elementos borrados lógicamente).
--}}
@php
    $nivel = $ubicacion->nivel;
    $posicion = $nivel?->posicion;
    $fila = $posicion?->fila;
    $sector = $fila?->sector;
    $patio = $sector?->patio;

    $tramos = array_values(array_filter([
        $patio ? ['elemento' => $patio, 'texto' => $patio->nombre.' ('.$patio->codigo.')'] : null,
        $sector ? ['elemento' => $sector, 'texto' => 'Sector '.$sector->codigo] : null,
        $fila ? ['elemento' => $fila, 'texto' => 'Fila '.$fila->numero_fila] : null,
        $posicion ? ['elemento' => $posicion, 'texto' => 'Posición '.$posicion->numero_posicion] : null,
        $nivel ? ['elemento' => $nivel, 'texto' => 'Nivel '.$nivel->numero_nivel] : null,
    ]));
@endphp
@if (count($tramos) === 0)
    <span class="text-slate-500">Ubicación no disponible</span>
@else
    <span class="inline-flex flex-wrap items-center gap-x-1 gap-y-0.5">
        @foreach ($tramos as $tramo)
            @unless ($loop->first)
                <x-ui.icon name="chevron-right" class="size-3 shrink-0 text-slate-400" />
            @endunless
            <span class="{{ $loop->last ? 'font-semibold text-blue-950' : '' }}">
                {{ $tramo['texto'] }}@if ($tramo['elemento']->trashed())<span class="ml-1 text-xs italic text-red-600">(eliminado)</span>@endif
            </span>
        @endforeach
    </span>
@endif
