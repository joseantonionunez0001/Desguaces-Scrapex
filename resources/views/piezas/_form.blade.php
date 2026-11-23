{{--
    Formulario de pieza (alta y edición).
    Variables: $pieza, $marcas, $modelos, $marcaDeCadaModelo, $categorias, $tiposPieza, $categoriaDeCadaTipo,
               $estados, $huecos, $action, $method ('POST' | 'PUT'), $submit, $cancel; en el alta, $claveEnvio.
    El modelo depende de la marca y el tipo de la categoría (desplegables dependientes; el servidor lo valida).
    En el alta se pide la cantidad que ha entrado y su ubicación inicial: se registran como la Entrada inicial.
    En la edición el stock no se modifica: se cambia registrando movimientos.
--}}
@php($esAlta = $method === 'POST')
<form method="POST" action="{{ $action }}" novalidate>
    @csrf
    @if (! $esAlta)
        @method($method)
    @endif
    @if ($esAlta)
        {{-- Clave única del envío: un doble clic o un reenvío no duplica la pieza ni su entrada. --}}
        <input type="hidden" name="clave_envio" value="{{ old('clave_envio', $claveEnvio) }}">
    @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <x-form.input name="nombre" label="Nombre" :value="$pieza->nombre" maxlength="255" :required="true" class="sm:col-span-2" />

        <x-form.select name="id_marca" label="Marca del vehículo" :options="$marcas" :value="$pieza->modeloVehiculo?->id_marca"
            placeholder="Selecciona una marca" :required="true" />
        <x-form.select name="id_modelo_vehiculo" label="Modelo del vehículo" :options="$modelos" :padres="$marcaDeCadaModelo"
            data-depende-de="id_marca" :value="$pieza->id_modelo_vehiculo" placeholder="Selecciona un modelo" :required="true"
            hint="Solo se muestran los modelos de la marca elegida." />

        <x-form.select name="id_categoria" label="Categoría" :options="$categorias" :value="$pieza->tipoPieza?->id_categoria"
            placeholder="Selecciona una categoría" :required="true" />
        <x-form.select name="id_tipo_pieza" label="Tipo de pieza" :options="$tiposPieza" :padres="$categoriaDeCadaTipo"
            data-depende-de="id_categoria" :value="$pieza->id_tipo_pieza" placeholder="Selecciona un tipo de pieza" :required="true"
            hint="Solo se muestran los tipos de la categoría elegida." />

        <x-form.select name="estado" label="Estado físico" :options="$estados" :value="$pieza->estado" placeholder="Selecciona el estado"
            :required="true" hint="Estado de la pieza, independiente de su disponibilidad." />
        <x-form.input name="precio" label="Precio (€)" type="number" :value="$pieza->precio" min="0" step="0.01" :required="true" />
        <x-form.input name="stock_minimo" label="Stock mínimo" type="number" :value="$pieza->stock_minimo" min="0" step="1"
            :required="true" hint="Con este saldo o menos se avisa de stock bajo." />

        @if ($esAlta)
            <x-form.input name="cantidad" label="Cantidad que ha entrado" type="number" min="1" step="1" inputmode="numeric" :required="true" />
            <x-form.select name="id_etiqueta" label="Ubicación inicial" :options="$huecos" placeholder="Selecciona el hueco" :required="true"
                class="sm:col-span-2" hint="Módulo · pasillo · estantería · etiqueta del almacén interior." />
        @else
            <div>
                <p class="erp-label">Stock</p>
                <p class="mt-2 text-sm font-semibold text-slate-800">{{ $pieza->stock }} unidades</p>
                <p class="mt-1 text-xs text-slate-500">Se modifica registrando movimientos desde la ficha de la pieza.</p>
            </div>
        @endif
    </div>

    @if ($esAlta && empty($huecos))
        <x-ui.alert type="warning" class="mt-4">
            Tu desguace todavía no tiene huecos en el almacén interior. Crea al menos una etiqueta en
            Almacén interior para poder registrar la ubicación inicial.
        </x-ui.alert>
    @endif

    <x-form.actions :submit="$submit" :cancel="$cancel" />
</form>
