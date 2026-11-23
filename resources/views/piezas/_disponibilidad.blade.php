{{--
    Disponibilidad calculada a partir de las existencias (Pieza::disponibilidad()):
    Con stock (hay unidades por encima del mínimo), Stock bajo (hay unidades, pero el saldo es igual
    o inferior al mínimo) o Agotada (stock cero). Es independiente del estado físico.
    Variable: $pieza.
--}}
@php($disponibilidad = $pieza->disponibilidad())
@switch($disponibilidad)
    @case(\App\Models\Pieza::AGOTADA)
        <x-ui.badge tone="red">Agotada</x-ui.badge>
        @break
    @case(\App\Models\Pieza::STOCK_BAJO)
        <x-ui.badge tone="amber">Stock bajo</x-ui.badge>
        @break
    @default
        <x-ui.badge tone="emerald">Con stock</x-ui.badge>
@endswitch
