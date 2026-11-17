{{--
    Formulario de categoría para los modales de alta y edición.
    Variables: $modalId, $accion, $categoria (null al crear), $desguaces, $modulos, $vehiculos.
--}}
@php
    $usarOld = old('_modal') === $modalId;
    $esEdicion = $categoria !== null;
@endphp
<form method="POST" action="{{ $accion }}" novalidate>
    @csrf
    @if ($esEdicion)
        @method('PUT')
    @endif
    <input type="hidden" name="_modal" value="{{ $modalId }}">

    <div class="grid gap-4">
        <x-form.input name="nombre" label="Nombre" :required="true" maxlength="255" :value="$categoria?->nombre" :use-old="$usarOld" />
        <x-form.select name="id_desguace" label="Desguace" :required="true" :options="$desguaces" :value="$categoria?->id_desguace" placeholder="Selecciona un desguace" :use-old="$usarOld" />
        <x-form.select name="id_modulo" label="Módulo" :required="true" :options="$modulos" :value="$categoria?->id_modulo" placeholder="Selecciona un módulo"
            hint="El módulo debe pertenecer al desguace elegido." :use-old="$usarOld" />
        <x-form.select name="id_vehiculo" label="Vehículo (opcional)" :options="$vehiculos" :value="$categoria?->id_vehiculo" placeholder="Sin vehículo" :use-old="$usarOld" />
    </div>

    <x-form.actions :modal="true" :submit="$esEdicion ? 'Guardar cambios' : 'Crear categoría'" />
</form>
