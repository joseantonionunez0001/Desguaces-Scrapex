{{--
    Pie de formulario: botón Guardar y botón Cancelar.
    - cancel="url" muestra un enlace Cancelar; con :modal="true" el botón cierra el modal.
--}}
@props([
    'submit' => 'Guardar',
    'cancel' => null,
    'modal' => false,
    'variant' => 'primary',
])

<div {{ $attributes->merge(['class' => 'mt-5 flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-4']) }}>
    @if ($modal)
        <x-ui.button type="button" variant="outline" data-modal-close data-no-loading>Cancelar</x-ui.button>
    @elseif ($cancel)
        <x-ui.button :href="$cancel" variant="outline">Cancelar</x-ui.button>
    @endif
    <x-ui.button type="submit" :variant="$variant" icon="check">{{ $submit }}</x-ui.button>
</div>
