{{-- Modal con todos los datos reales de un movimiento (el modelo no guarda usuario responsable ni pieza). --}}
<x-ui.modal :id="'detalle-movimiento-'.$movimiento->id_movimiento" :title="'Movimiento #'.$movimiento->id_movimiento" size="md">
    <dl class="grid gap-4 text-sm sm:grid-cols-2">
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Identificador</dt>
            <dd class="mt-1 text-slate-800">{{ $movimiento->id_movimiento }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tipo de movimiento</dt>
            <dd class="mt-1 text-slate-800">{{ $movimiento->tipo_movimiento }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fecha</dt>
            <dd class="mt-1 text-slate-800">{{ $movimiento->fecha->format('d/m/Y') }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Cantidad</dt>
            <dd class="mt-1 text-slate-800">{{ number_format($movimiento->cantidad, 0, ',', '.') }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Categoría</dt>
            <dd class="mt-1 text-slate-800">{{ $movimiento->categoria?->nombre ?? 'No disponible' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Desguace de la categoría</dt>
            <dd class="mt-1 text-slate-800">{{ $movimiento->categoria?->desguace?->nombre ?? 'No disponible' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Módulo de la categoría</dt>
            <dd class="mt-1 text-slate-800">{{ $movimiento->categoria?->modulo?->nombre_modulo ?? 'No disponible' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Registrado el</dt>
            <dd class="mt-1 text-slate-800">{{ $movimiento->created_at?->format('d/m/Y H:i') ?? '—' }}</dd>
        </div>
    </dl>
    <div class="mt-5 flex justify-end border-t border-slate-100 pt-4">
        <x-ui.button type="button" variant="outline" data-modal-close data-no-loading>Cerrar</x-ui.button>
    </div>
</x-ui.modal>
