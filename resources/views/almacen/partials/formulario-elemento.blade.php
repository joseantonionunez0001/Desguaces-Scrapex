{{--
    Formulario de alta o edición de un elemento del almacén (dentro de un modal).

    Variables: $definicion (EstructuraAlmacen), $accion (URL), $metodo ('POST' | 'PUT'), $elemento (null al crear),
    $idModal, $padreId y $consultaActual (camino mostrado, se envía como ruta[...] para validar el padre).
--}}
@php
    $usarOld = old('_modal') === $idModal;
    $columnaPadre = $definicion['columna_padre'];
@endphp
<form method="POST" action="{{ $accion }}">
    @csrf
    @if ($metodo !== 'POST')
        @method($metodo)
    @endif
    <input type="hidden" name="_modal" value="{{ $idModal }}">
    <input type="hidden" name="{{ $columnaPadre }}" value="{{ $padreId }}">
    @foreach ($consultaActual as $clave => $valor)
        <input type="hidden" name="ruta[{{ $clave }}]" value="{{ $valor }}">
    @endforeach

    @if ($usarOld && ($errors->has($columnaPadre) || $errors->has('ruta') || $errors->has('ruta.*')))
        <x-ui.alert type="error" class="mb-4">
            {{ $errors->first($columnaPadre) ?: 'La ruta mostrada no es válida. Vuelve a abrir el nivel e inténtalo de nuevo.' }}
        </x-ui.alert>
    @endif

    <div class="grid gap-4">
        @foreach ($definicion['campos'] as $campo => $configuracion)
            <x-form.input
                :name="$campo"
                :label="$configuracion['etiqueta']"
                :type="$configuracion['input']"
                :value="$elemento?->{$campo}"
                :required="true"
                :use-old="$usarOld"
                :maxlength="$configuracion['maxlength'] ?? null"
                :min="$configuracion['min'] ?? null"
            />
        @endforeach
    </div>

    <x-form.actions :modal="true" />
</form>
