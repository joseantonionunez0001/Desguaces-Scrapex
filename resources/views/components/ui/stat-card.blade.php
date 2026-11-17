@props([
    'label',
    'value',
    'hint' => null,
    'icon' => 'cube',
    'tone' => 'blue',
    'href' => null,
])

@php
    $tonos = [
        'blue' => 'bg-blue-100 text-blue-700',
        'emerald' => 'bg-emerald-100 text-emerald-700',
        'amber' => 'bg-amber-100 text-amber-700',
        'red' => 'bg-red-100 text-red-700',
        'slate' => 'bg-slate-100 text-slate-600',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'flex items-start gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm']) }}>
    <span class="flex size-11 shrink-0 items-center justify-center rounded-lg {{ $tonos[$tone] ?? $tonos['blue'] }}">
        <x-ui.icon :name="$icon" class="size-6" />
    </span>
    <div class="min-w-0">
        <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
        <p class="mt-1 text-2xl font-bold text-blue-950">{{ $value }}</p>
        @if ($hint)
            <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
        @endif
        @if ($href)
            <a href="{{ $href }}" class="mt-2 inline-block text-xs font-semibold text-blue-700 hover:underline">Ver detalle</a>
        @endif
    </div>
</div>
