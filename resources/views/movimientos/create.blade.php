@extends('layouts.app')

@section('title', 'Registrar movimiento')

@section('content')
    <x-ui.page-header
        title="Registrar movimiento"
        subtitle="Anota una entrada, salida o ajuste de una categoría."
        :breadcrumbs="[['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Movimientos', 'url' => route('movimientos.index')], ['label' => 'Registrar']]" />

    @if ($errors->any())
        <x-ui.alert type="error" class="mb-4">Revisa los campos marcados: hay datos que no son válidos.</x-ui.alert>
    @endif

    <x-ui.card class="max-w-3xl">
        @if (empty($categorias))
            <x-ui.empty-state title="No hay categorías" message="Para registrar un movimiento necesitas al menos una categoría." icon="tag">
                @can('categorias.crear')
                    @if (Route::has('categorias.index'))
                        <x-ui.button :href="route('categorias.index')" icon="plus">Ir a categorías</x-ui.button>
                    @endif
                @endcan
            </x-ui.empty-state>
        @else
            <form method="POST" action="{{ route('movimientos.store') }}" novalidate>
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-form.input name="tipo_movimiento" label="Tipo de movimiento" :required="true" maxlength="50" list="tipos-movimiento"
                            placeholder="Por ejemplo: Entrada" hint="Escribe un tipo nuevo o elige uno de los ya utilizados." autocomplete="off" />
                        <datalist id="tipos-movimiento">
                            @foreach ($tipos as $tipo)
                                <option value="{{ $tipo }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                    <x-form.input name="fecha" type="date" label="Fecha" :required="true" :value="now()->toDateString()" />
                    <x-form.input name="cantidad" type="number" label="Cantidad" :required="true" min="1" step="1" inputmode="numeric" />
                    <x-form.select name="id_categoria" label="Categoría" :required="true" :options="$categorias" placeholder="Selecciona una categoría" />
                </div>

                <x-form.actions submit="Guardar movimiento" :cancel="route('movimientos.index')" />
            </form>
        @endif
    </x-ui.card>
@endsection
