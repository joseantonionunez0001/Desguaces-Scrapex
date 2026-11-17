{{--
    Formulario de desguace para los modales de alta y edición.
    Variables: $modalId, $accion, $desguace (null al crear).
--}}
@php
    $usarOld = old('_modal') === $modalId;
    $esEdicion = $desguace !== null;
@endphp
<form method="POST" action="{{ $accion }}" novalidate>
    @csrf
    @if ($esEdicion)
        @method('PUT')
    @endif
    <input type="hidden" name="_modal" value="{{ $modalId }}">

    <div class="grid gap-4 sm:grid-cols-2">
        <x-form.input name="nombre" label="Nombre" :required="true" maxlength="255" :value="$desguace?->nombre" :use-old="$usarOld" class="sm:col-span-2" />
        <x-form.input name="direccion" label="Dirección" :required="true" maxlength="255" :value="$desguace?->direccion" :use-old="$usarOld" class="sm:col-span-2" />
        <x-form.input name="numero_telefono" type="tel" label="Teléfono" :required="true" maxlength="20" :value="$desguace?->numero_telefono" :use-old="$usarOld" />
        <x-form.input name="ubicación" label="Ubicación" :required="true" maxlength="255" :value="$desguace?->{'ubicación'}" :use-old="$usarOld"
            hint="Zona o polígono donde se encuentra." />
    </div>

    <x-form.actions :modal="true" :submit="$esEdicion ? 'Guardar cambios' : 'Crear desguace'" />
</form>
