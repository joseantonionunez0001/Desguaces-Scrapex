{{--
    Fila del historial de movimientos (listado de Movimientos y ficha de la pieza).
    Variables: $movimiento, $mostrarPieza (bool).
    Los movimientos anteriores a esta versión se muestran como «Histórico», con su tipo original.
--}}
@php
    $tonos = ['entrada' => 'emerald', 'compra' => 'emerald', 'venta' => 'blue', 'baja' => 'red', 'traslado' => 'amber'];
    $origen = $movimiento->descripcionOrigen();
    $destino = $movimiento->descripcionDestino();
@endphp
<tr>
    <td class="whitespace-nowrap">
        {{ ($movimiento->created_at ?? $movimiento->fecha)?->format($movimiento->created_at ? 'd/m/Y H:i' : 'd/m/Y') }}
    </td>
    <td>
        @if ($movimiento->esHistorico())
            <x-ui.badge tone="slate">Histórico · {{ $movimiento->tipo_movimiento }}</x-ui.badge>
        @else
            <x-ui.badge :tone="$tonos[$movimiento->tipo_movimiento] ?? 'blue'">{{ $movimiento->nombreAccion() }}</x-ui.badge>
        @endif
    </td>
    @if ($mostrarPieza)
        <td>
            @if ($movimiento->pieza)
                @if ($movimiento->pieza->trashed())
                    {{ $movimiento->pieza->nombre }} <span class="text-xs text-slate-500">(archivada)</span>
                @else
                    <a href="{{ route('piezas.show', $movimiento->pieza) }}" class="font-medium text-blue-950 hover:underline">{{ $movimiento->pieza->nombre }}</a>
                @endif
            @else
                <span class="text-slate-500">{{ $movimiento->categoria?->nombre ?? 'Sin pieza' }}</span>
            @endif
        </td>
    @endif
    <td class="text-right font-semibold tabular-nums">{{ number_format($movimiento->cantidad, 0, ',', '.') }}</td>
    <td class="text-sm">
        @if ($origen || $destino)
            <span>{{ $origen ?? '—' }}</span>
            @if ($destino)
                <span aria-hidden="true">→</span><span class="sr-only">hacia</span>
                <span>{{ $destino }}</span>
            @endif
        @else
            <span class="text-slate-400">—</span>
        @endif
        @if ($movimiento->motivo)
            <p class="mt-1 text-xs text-slate-600"><span class="font-semibold">Motivo:</span> {{ $movimiento->motivo }}</p>
        @endif
    </td>
    <td class="whitespace-nowrap text-right tabular-nums">
        @if ($movimiento->saldo_anterior !== null)
            {{ $movimiento->saldo_anterior }} <span aria-hidden="true">→</span><span class="sr-only">pasa a</span> {{ $movimiento->saldo_posterior }}
        @else
            <span class="text-slate-400">—</span>
        @endif
    </td>
    <td>{{ $movimiento->usuario?->name ?? '—' }}</td>
</tr>
