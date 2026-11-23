{{--
    Desplegable. options: array o colección clave => etiqueta (p. ej. $desguaces->pluck('nombre', 'id_desguace')).
    placeholder = null elimina la opción vacía.
    Desplegable dependiente: padres = [clave => valor del campo padre] y data-depende-de="nombre del campo padre".
    Al elegir un valor en el campo padre solo se muestran las opciones con ese padre (resources/js/app.js).
--}}
@props([
    'name',
    'options' => [],
    'label' => null,
    'value' => null,
    'placeholder' => 'Selecciona una opción',
    'hint' => null,
    'required' => false,
    'useOld' => true,
    'padres' => [],
])

@php
    $id = $attributes->get('id', 'campo-'.preg_replace('/[^A-Za-z0-9_-]/', '-', $name).'-'.substr(md5(uniqid('', true)), 0, 6));
    $seleccionado = (string) ($useOld ? old($name, $value) : $value);
    $mensajeError = $useOld ? $errors->first($name) : null;
@endphp

<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="erp-label">
            {{ $label }}@if ($required)<span class="text-red-600" aria-hidden="true"> *</span>@endif
        </label>
    @endif

    <select id="{{ $id }}" name="{{ $name }}"
        @required($required)
        @if ($mensajeError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->except(['class', 'id'])->merge(['class' => 'erp-input'.($mensajeError ? ' erp-input-error' : '')]) }}>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $clave => $etiqueta)
            <option value="{{ $clave }}" @selected((string) $clave === $seleccionado) @isset($padres[$clave]) data-padre="{{ $padres[$clave] }}" @endisset>{{ $etiqueta }}</option>
        @endforeach
    </select>

    @if ($hint && ! $mensajeError)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif
    @if ($mensajeError)
        <p id="{{ $id }}-error" class="mt-1 text-xs font-medium text-red-600">{{ $mensajeError }}</p>
    @endif
</div>
