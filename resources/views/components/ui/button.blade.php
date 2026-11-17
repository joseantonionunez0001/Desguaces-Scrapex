@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
    'icon' => null,
])

@php
    $variantes = [
        'primary' => 'bg-blue-900 text-white hover:bg-blue-800 focus-visible:outline-blue-900',
        'secondary' => 'bg-blue-600 text-white hover:bg-blue-500 focus-visible:outline-blue-600',
        'success' => 'bg-emerald-600 text-white hover:bg-emerald-500 focus-visible:outline-emerald-600',
        'danger' => 'bg-red-600 text-white hover:bg-red-500 focus-visible:outline-red-600',
        'warning' => 'bg-amber-500 text-white hover:bg-amber-400 focus-visible:outline-amber-500',
        'outline' => 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 focus-visible:outline-blue-600',
        'ghost' => 'text-slate-600 hover:bg-slate-100 focus-visible:outline-blue-600',
    ];

    $tamanos = [
        'sm' => 'px-2.5 py-1.5 text-xs',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-5 py-2.5 text-base',
    ];

    $clases = 'inline-flex items-center justify-center gap-2 rounded-lg font-semibold shadow-sm transition focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-50 '
        .($variantes[$variant] ?? $variantes['primary']).' '
        .($tamanos[$size] ?? $tamanos['md']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $clases]) }}>
        @if ($icon)
            <x-ui.icon :name="$icon" class="size-4" />
        @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $clases]) }}>
        @if ($icon)
            <x-ui.icon :name="$icon" class="size-4" />
        @endif
        {{ $slot }}
    </button>
@endif
