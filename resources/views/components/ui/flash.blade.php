{{-- Mensajes flash de sesión: status/success (verde), error (rojo), warning (ámbar), info (azul). --}}
@php
    $mensajes = [
        'success' => array_filter([session('success'), session('status')]),
        'error' => array_filter([session('error')]),
        'warning' => array_filter([session('warning')]),
        'info' => array_filter([session('info')]),
    ];
@endphp

@foreach ($mensajes as $tipo => $textos)
    @foreach ($textos as $texto)
        <x-ui.alert :type="$tipo" dismissible class="mb-4">{{ $texto }}</x-ui.alert>
    @endforeach
@endforeach
