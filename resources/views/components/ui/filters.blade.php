{{--
    Barra de filtros GET. Los campos se pasan en el slot (usar <x-form.input>, <x-form.select> con :use-old="false"
    y value="{{ request('campo') }}"). "Limpiar" vuelve a la URL sin parámetros.
--}}
@props(['action' => null, 'columns' => 'sm:grid-cols-2 lg:grid-cols-4'])

@php($destino = $action ?? url()->current())

<form method="GET" action="{{ $destino }}" role="search" {{ $attributes->merge(['class' => 'mb-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm']) }}>
    <div class="grid gap-3 {{ $columns }}">
        {{ $slot }}
    </div>
    <div class="mt-3 flex flex-wrap gap-2">
        <x-ui.button type="submit" icon="search">Filtrar</x-ui.button>
        <x-ui.button :href="$destino" variant="outline">Limpiar</x-ui.button>
    </div>
</form>
