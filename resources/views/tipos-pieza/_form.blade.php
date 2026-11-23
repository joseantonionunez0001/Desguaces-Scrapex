{{--
    Formulario de tipo de pieza para los modales de alta y edición.
    Variables: $modalId, $accion, $tipoPieza (null al crear), $categorias; opcional $categoriaPreseleccionada.
    La marca y el modelo del vehículo se registran en cada pieza, no en el tipo.
--}}
@php
    $usarOld = old('_modal') === $modalId;
    $esEdicion = $tipoPieza !== null;
@endphp
<form method="POST" action="{{ $accion }}" novalidate>
    @csrf
    @if ($esEdicion)
        @method('PUT')
    @endif
    <input type="hidden" name="_modal" value="{{ $modalId }}">
    <div class="grid gap-4">
        <x-form.input name="nombre" label="Nombre" :required="true" maxlength="255" :value="$tipoPieza?->nombre" :use-old="$usarOld"
            placeholder="Por ejemplo: Alternador" />
        <x-form.select name="id_categoria" label="Categoría" :required="true" :options="$categorias"
            :value="$tipoPieza?->id_categoria ?? ($categoriaPreseleccionada ?? null)" placeholder="Selecciona una categoría" :use-old="$usarOld" />
        <x-form.input name="fabricante" label="Fabricante (opcional)" maxlength="255" :value="$tipoPieza?->fabricante" :use-old="$usarOld" />
    </div>
    <x-form.actions :modal="true" :submit="$esEdicion ? 'Guardar cambios' : 'Crear tipo de pieza'" />
</form>
