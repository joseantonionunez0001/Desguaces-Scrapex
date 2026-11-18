{{--
    Formulario de tipo de pieza para los modales de alta y edición.
    Variables: $modalId, $accion, $tipoPieza (null al crear), $categorias, $categoriasTexto.
    El EER guarda la categoría de dos formas: "categoria" (texto) e "id_categoria" (clave foránea).
    $categoriaPreseleccionada es opcional: preselecciona la categoría al crear desde una categoría concreta.
--}}
@php
    $usarOld = old('_modal') === $modalId;
    $esEdicion = $tipoPieza !== null;
    $listaId = 'categorias-texto-'.$modalId;
@endphp
<form method="POST" action="{{ $accion }}" novalidate>
    @csrf
    @if ($esEdicion)
        @method('PUT')
    @endif
    <input type="hidden" name="_modal" value="{{ $modalId }}">

    <div class="grid gap-4">
        <x-form.input name="nombre" label="Nombre" :required="true" maxlength="255" :value="$tipoPieza?->nombre" :use-old="$usarOld" />
        <div class="grid gap-4 sm:grid-cols-2">
            <x-form.input name="marca" label="Marca" :required="true" maxlength="255" :value="$tipoPieza?->marca ?? ($marcaPreseleccionada ?? null)" :use-old="$usarOld" placeholder="Por ejemplo: Seat" />
            <x-form.input name="modelo" label="Modelo" :required="true" maxlength="255" :value="$tipoPieza?->modelo ?? ($modeloPreseleccionado ?? null)" :use-old="$usarOld" placeholder="Por ejemplo: Ibiza" />
        </div>
        <x-form.input name="fabricante" label="Fabricante" :required="true" maxlength="255" :value="$tipoPieza?->fabricante" :use-old="$usarOld" />
        <div>
            <x-form.input name="categoria" label="Categoría (texto)" :required="true" maxlength="255" :value="$tipoPieza?->categoria" :use-old="$usarOld"
                list="{{ $listaId }}" autocomplete="off" hint="Texto de clasificación del tipo de pieza (por ejemplo: Motor)." />
            <datalist id="{{ $listaId }}">
                @foreach ($categoriasTexto as $textoCategoria)
                    <option value="{{ $textoCategoria }}"></option>
                @endforeach
            </datalist>
        </div>
        <x-form.select name="id_categoria" label="Categoría relacionada" :required="true" :options="$categorias" :value="$tipoPieza?->id_categoria ?? ($categoriaPreseleccionada ?? null)"
            placeholder="Selecciona una categoría" :use-old="$usarOld" />
    </div>

    <x-form.actions :modal="true" :submit="$esEdicion ? 'Guardar cambios' : 'Crear tipo de pieza'" />
</form>
