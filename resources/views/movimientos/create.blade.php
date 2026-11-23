@extends('layouts.app')

@section('title', 'Registrar movimiento')

@section('content')
    <x-ui.page-header
        title="Registrar movimiento"
        :subtitle="$pieza ? 'Pieza: '.$pieza->nombre.' · stock actual '.$pieza->stock : 'Elige la pieza sobre la que se registra el movimiento.'"
        :breadcrumbs="[['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Movimientos', 'url' => route('movimientos.index')], ['label' => 'Registrar']]" />

    @if ($errors->any())
        <x-ui.alert type="error" class="mb-4">Revisa los campos marcados: hay datos que no son válidos.</x-ui.alert>
    @endif

    <x-ui.card class="max-w-3xl">
        @if ($pieza === null)
            @if (empty($piezas))
                <x-ui.empty-state title="No hay piezas" message="Para registrar un movimiento primero hay que dar de alta la pieza." icon="cog">
                    @can('piezas.crear')
                        <x-ui.button :href="route('piezas.create')" icon="plus">Registrar pieza</x-ui.button>
                    @endcan
                </x-ui.empty-state>
            @else
                {{-- Paso 1: elegir la pieza (por GET, sin JavaScript). --}}
                <form method="GET" action="{{ route('movimientos.create') }}">
                    <x-form.select name="pieza" label="Pieza" :required="true" :options="$piezas" placeholder="Selecciona una pieza" :use-old="false" />
                    <x-form.actions submit="Continuar" :cancel="route('movimientos.index')" />
                </form>
            @endif
        @else
            {{-- Paso 2: la acción y sus campos. Los bloques data-acciones se muestran según la acción elegida. --}}
            <form method="POST" action="{{ route('movimientos.store', $pieza) }}" novalidate>
                @csrf
                {{-- Clave única del envío: un doble clic o un reenvío no duplica el movimiento. --}}
                <input type="hidden" name="clave_envio" value="{{ old('clave_envio', $claveEnvio) }}">

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.select name="accion" label="Acción" :required="true" :options="$acciones" :value="$accion" placeholder="Selecciona una acción"
                        data-selector-accion class="sm:col-span-2" />

                    <div data-acciones="entrada compra venta baja traslado" class="sm:col-span-1">
                        <x-form.input name="cantidad" type="number" label="Cantidad" :required="true" min="1" step="1" inputmode="numeric" />
                    </div>

                    <div data-acciones="venta baja traslado">
                        @if (empty($origenes))
                            <p class="erp-label">Hueco de origen</p>
                            <p class="mt-2 text-sm text-amber-800">Esta pieza no tiene unidades en ningún hueco.</p>
                        @else
                            <x-form.select name="origen" label="Hueco de origen" :required="true" :options="$origenes" placeholder="Selecciona el hueco" />
                        @endif
                    </div>

                    <div data-acciones="entrada compra traslado" class="sm:col-span-2">
                        <x-form.select name="destino" label="Hueco de destino" :required="true" :options="$huecos" placeholder="Selecciona el hueco"
                            hint="Módulo · pasillo · estantería · etiqueta del almacén interior." />
                    </div>

                    <div data-acciones="baja" class="sm:col-span-2 space-y-3">
                        <x-form.textarea name="motivo" label="Motivo de la baja" :required="true" maxlength="500" rows="2"
                            hint="Por ejemplo: pieza dañada, extraviada o enviada a reciclaje." />
                        @if ($puedeRetirarFicha)
                            <label class="flex items-start gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="retirar_ficha" value="1" @checked(old('retirar_ficha')) class="mt-0.5">
                                <span>
                                    Retirar la ficha completa: se descuentan todas las unidades de todos los huecos y la pieza queda
                                    archivada con su historial. La cantidad indicada se ignora.
                                </span>
                            </label>
                        @endif
                    </div>
                </div>

                <x-form.actions submit="Registrar movimiento" :cancel="route('piezas.show', $pieza)" />
            </form>
        @endif
    </x-ui.card>
@endsection
