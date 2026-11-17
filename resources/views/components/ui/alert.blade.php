@props([
    'type' => 'info',
    'dismissible' => false,
])

@php
    $estilos = [
        'success' => ['bg-emerald-50 border-emerald-300 text-emerald-900', 'check-circle'],
        'error' => ['bg-red-50 border-red-300 text-red-900', 'x-circle'],
        'warning' => ['bg-amber-50 border-amber-300 text-amber-900', 'warning'],
        'info' => ['bg-blue-50 border-blue-300 text-blue-900', 'info'],
    ];

    [$clases, $icono] = $estilos[$type] ?? $estilos['info'];
@endphp

<div role="alert" {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-lg border px-4 py-3 text-sm '.$clases]) }}>
    <x-ui.icon :name="$icono" class="mt-0.5 size-5 shrink-0" />
    <div class="min-w-0 flex-1">{{ $slot }}</div>
    @if ($dismissible)
        <button type="button" data-dismiss data-no-loading aria-label="Cerrar aviso" class="rounded p-0.5 opacity-70 hover:opacity-100">
            <x-ui.icon name="x" class="size-4" />
        </button>
    @endif
</div>
