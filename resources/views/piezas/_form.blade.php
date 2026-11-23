{{--
    Formulario reutilizable de pieza (crear y editar).
    Variables: $pieza, $tiposPieza (id => "nombre (fabricante)"), $estados (valores existentes), $action, $method ('POST' | 'PUT'), $submit, $cancel.
    El usuario que registra la pieza es el autenticado: no es un campo del formulario.
--}}
<form method="POST" action="{{ $action }}">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <x-form.input name="nombre" label="Nombre" :value="$pieza->nombre" maxlength="255" required class="sm:col-span-2" />

        <x-form.select name="id_tipo_pieza" label="Tipo de pieza" :options="$tiposPieza" :value="$pieza->id_tipo_pieza"
            placeholder="Selecciona un tipo de pieza" required />

        <x-form.input name="estado" label="Estado" :value="$pieza->estado" maxlength="50" required list="estados-pieza"
            autocomplete="off" hint="Escribe uno nuevo o elige uno de los ya utilizados." />

        <x-form.input name="precio" label="Precio (€)" type="number" :value="$pieza->precio" min="0" step="0.01" required />

        <x-form.input name="stock" label="Stock" type="number" :value="$pieza->stock" min="0" step="1" required />

        <x-form.input name="stock_minimo" label="Stock mínimo" type="number" :value="$pieza->stock_minimo" min="0" step="1"
            required hint="Por debajo de este valor se avisa de stock bajo." />
    </div>

    <datalist id="estados-pieza">
        @foreach ($estados as $estado)
            <option value="{{ $estado }}"></option>
        @endforeach
    </datalist>

    <x-form.actions :submit="$submit" :cancel="$cancel" />
</form>
