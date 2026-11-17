{{--
    Tabla responsive (desplazamiento horizontal en pantallas pequeñas).
    Uso: <x-ui.table><x-slot:head><tr><th>…</th></tr></x-slot:head> <tr>…</tr></x-ui.table>
--}}
<div class="overflow-x-auto">
    <table {{ $attributes->merge(['class' => 'erp-table']) }}>
        @isset($head)
            <thead>{{ $head }}</thead>
        @endisset
        <tbody>{{ $slot }}</tbody>
    </table>
</div>
