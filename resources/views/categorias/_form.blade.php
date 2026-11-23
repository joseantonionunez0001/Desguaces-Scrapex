{{--
    Formulario de categoría para los modales de alta y edición: solo el nombre.
    El desguace lo asigna el servidor con la sede del usuario.
    Variables: $modalId, $accion, $categoria (null al crear).
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
    <x-form.input name="nombre" label="Nombre" :required="true" maxlength="255" :value="$categoria?->nombre" :use-old="$usarOld"
        placeholder="Por ejemplo: Electricidad, Carrocería o Motor" />
    <x-form.actions :modal="true" :submit="$esEdicion ? 'Guardar cambios' : 'Crear categoría'" />
</form>
