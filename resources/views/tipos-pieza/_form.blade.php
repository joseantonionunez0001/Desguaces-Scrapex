{{--
    Formulario de tipo de pieza para los modales de alta y edición.
    Variables: $modalId, $accion, $tipoPieza (null al crear), $categorias, $categoriasTexto.
    Opcionales: categoriaPreseleccionada, marcaPreseleccionada y modeloPreseleccionado. Si vienen con valor,
    la sección desde la que se abre el modal ya los define: el campo se oculta y se envía como campo oculto.
    El EER guarda la categoría de dos formas: "categoria" (texto) e "id_categoria" (clave foránea).
--}}
@php
    $usarOld = old('_modal') === $modalId;
    $esEdicion = $tipoPieza !== null;
    $listaId = 'categorias-texto-'.$modalId;
    // Valores que vienen de la sección desde la que se abre el modal. Si tienen valor, el campo se oculta.
    $fijos = array_filter([
        'id_categoria' => $categoriaPreseleccionada ?? null,
        'marca' => $marcaPreseleccionada ?? null,
        'modelo' => $modeloPreseleccionado ?? null,
        'categoria_texto' => $categoriaTextoPreseleccionado ?? null,
    ], fn ($valor) => $valor !== null);
    $marcaFija = $fijos['marca'] ?? null;
    $modeloFijo = $fijos['modelo'] ?? null;
@endphp
<form method="POST" action="{{ $accion }}" novalidate>
    @csrf
    @if ($esEdicion)
        @method('PUT')
    @endif
    <input type="hidden" name="_modal" value="{{ $modalId }}">

    <div class="grid gap-4">
        <x-form.input name="nombre" label="Nombre" :required="true" maxlength="255" :value="$tipoPieza?->nombre" :use-old="$usarOld" />

        @if ($marcaFija === null || $modeloFijo === null)
            <div class="grid gap-4 sm:grid-cols-2">
                @if ($marcaFija === null)
                    <x-form.input name="marca" label="Marca" :required="true" maxlength="255" :value="$tipoPieza?->marca" :use-old="$usarOld" placeholder="Por ejemplo: Seat" />
                @endif
                @if ($modeloFijo === null)
                    <x-form.input name="modelo" label="Modelo" :required="true" maxlength="255" :value="$tipoPieza?->modelo" :use-old="$usarOld" placeholder="Por ejemplo: Ibiza" />
                @endif
            </div>
        @endif

        <x-form.input name="fabricante" label="Fabricante" :required="true" maxlength="255" :value="$tipoPieza?->fabricante" :use-old="$usarOld" />

        @if (isset($fijos['categoria_texto']))
            <input type="hidden" name="categoria" value="{{ $fijos['categoria_texto'] }}">
        @else
        <div>
            <x-form.input name="categoria" label="Categoría (texto)" :required="true" maxlength="255"
                :value="$tipoPieza?->categoria" :use-old="$usarOld"
                list="{{ $listaId }}" autocomplete="off" hint="Texto de clasificación del tipo de pieza (por ejemplo: Motor)." />
            <datalist id="{{ $listaId }}">
                @foreach ($categoriasTexto as $textoCategoria)
                    <option value="{{ $textoCategoria }}"></option>
                @endforeach
            </datalist>
        </div>
        @endif

        @if (isset($fijos['id_categoria']))
            <input type="hidden" name="id_categoria" value="{{ $fijos['id_categoria'] }}">
        @else
            <x-form.select name="id_categoria" label="Categoría relacionada" :required="true" :options="$categorias" :value="$tipoPieza?->id_categoria"
                placeholder="Selecciona una categoría" :use-old="$usarOld" />
        @endif
    </div>

    {{-- Campos fijos de marca y modelo: se envían ocultos cuando vienen de la sección superior. --}}
    @if ($marcaFija !== null)
        <input type="hidden" name="marca" value="{{ $marcaFija }}">
    @endif
    @if ($modeloFijo !== null)
        <input type="hidden" name="modelo" value="{{ $modeloFijo }}">
    @endif

    <x-form.actions :modal="true" :submit="$esEdicion ? 'Guardar cambios' : 'Crear tipo de pieza'" />
</form>
