{{--
    Desplegable. options: array o colección clave => etiqueta (p. ej. $desguaces->pluck('nombre', 'id_desguace')).
    placeholder = null elimina la opción vacía.
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
            <option value="{{ $clave }}" @selected((string) $clave === $seleccionado)>{{ $etiqueta }}</option>
        @endforeach
    </select>

    @if ($hint && ! $mensajeError)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif
    @if ($mensajeError)
        <p id="{{ $id }}-error" class="mt-1 text-xs font-medium text-red-600">{{ $mensajeError }}</p>
    @endif
</div>
