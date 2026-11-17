{{--
    Disponibilidad de una pieza: disponible si stock > 0; aviso de stock bajo si stock <= stock mínimo.
    Variable: $pieza.
--}}
<span class="inline-flex flex-wrap items-center gap-1">
    @if ($pieza->stock > 0)
        <x-ui.badge tone="emerald">Disponible</x-ui.badge>
    @else
        <x-ui.badge tone="red">No disponible</x-ui.badge>
    @endif
    @if ($pieza->stock <= $pieza->stock_minimo)
        <x-ui.badge tone="amber">Stock bajo</x-ui.badge>
    @endif
</span>
