{{--
    Modal de consulta de un desguace con sus contadores y accesos a vehículos y almacenes.
    Los accesos pertenecen a otras ramas: solo se muestran si la ruta existe y el usuario tiene permiso.
--}}
<x-ui.modal :id="'detalle-desguace-'.$desguace->id_desguace" :title="$desguace->nombre" size="lg">
    <dl class="grid gap-4 text-sm sm:grid-cols-2">
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Dirección</dt>
            <dd class="mt-1 text-slate-800">{{ $desguace->direccion }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Teléfono</dt>
            <dd class="mt-1 text-slate-800">{{ $desguace->numero_telefono }}</dd>
        </div>
        <div class="sm:col-span-2">
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Ubicación</dt>
            <dd class="mt-1 text-slate-800">{{ $desguace->{'ubicación'} }}</dd>
        </div>
    </dl>

    <div class="mt-5 grid grid-cols-3 gap-3 text-center">
        <div class="rounded-lg bg-slate-50 p-3">
            <p class="text-2xl font-bold text-blue-950">{{ $desguace->vehiculos_count }}</p>
            <p class="text-xs text-slate-500">Vehículos</p>
        </div>
        <div class="rounded-lg bg-slate-50 p-3">
            <p class="text-2xl font-bold text-blue-950">{{ $desguace->modulos_count }}</p>
            <p class="text-xs text-slate-500">Módulos</p>
        </div>
        <div class="rounded-lg bg-slate-50 p-3">
            <p class="text-2xl font-bold text-blue-950">{{ $desguace->patios_count }}</p>
            <p class="text-xs text-slate-500">Patios</p>
        </div>
    </div>

    <div class="mt-5 flex flex-wrap gap-2 border-t border-slate-100 pt-4">
        @can('vehiculos.ver')
            @if (Route::has('vehiculos.index'))
                <x-ui.button :href="route('vehiculos.index', ['desguace' => $desguace->id_desguace])" variant="secondary" size="sm" icon="truck">Vehículos</x-ui.button>
            @endif
        @endcan
        @can('modulos.ver')
            @if (Route::has('almacen.interior.index'))
                <x-ui.button :href="route('almacen.interior.index', ['desguace' => $desguace->id_desguace])" variant="secondary" size="sm" icon="archive">Almacén interior</x-ui.button>
            @endif
        @endcan
        @can('patios.ver')
            @if (Route::has('almacen.exterior.index'))
                <x-ui.button :href="route('almacen.exterior.index', ['desguace' => $desguace->id_desguace])" variant="secondary" size="sm" icon="map">Almacén exterior</x-ui.button>
            @endif
        @endcan
        <x-ui.button type="button" variant="outline" size="sm" class="ml-auto" data-modal-close data-no-loading>Cerrar</x-ui.button>
    </div>
</x-ui.modal>
