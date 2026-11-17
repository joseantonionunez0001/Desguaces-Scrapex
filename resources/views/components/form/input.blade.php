{{--
    Campo de texto con etiqueta, valor previo (old) y error de validación.
    Los atributos extra (maxlength, min, step, placeholder, autocomplete…) van al <input>;
    la clase "class" se aplica al contenedor (p. ej. class="sm:col-span-2").
    :use-old="false" ignora old() (filtros GET y formularios de edición en modales por fila).
--}}
@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'hint' => null,
    'required' => false,
    'useOld' => true,
])

@php
    $id = $attributes->get('id', 'campo-'.preg_replace('/[^A-Za-z0-9_-]/', '-', $name).'-'.substr(md5(uniqid('', true)), 0, 6));
    $valor = $type === 'password' ? null : ($useOld ? old($name, $value) : $value);
    $mensajeError = $useOld ? $errors->first($name) : null;
@endphp

<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="erp-label">
            {{ $label }}@if ($required)<span class="text-red-600" aria-hidden="true"> *</span>@endif
        </label>
    @endif

    <input id="{{ $id }}" type="{{ $type }}" name="{{ $name }}" value="{{ $valor }}"
        @required($required)
        @if ($mensajeError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->except(['class', 'id'])->merge(['class' => 'erp-input'.($mensajeError ? ' erp-input-error' : '')]) }}>

    @if ($hint && ! $mensajeError)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif
    @if ($mensajeError)
        <p id="{{ $id }}-error" class="mt-1 text-xs font-medium text-red-600">{{ $mensajeError }}</p>
    @endif
</div>
