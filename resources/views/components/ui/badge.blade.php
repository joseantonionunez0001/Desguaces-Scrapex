@props(['tone' => 'slate'])

@php
    $tonos = [
        'slate' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'blue' => 'bg-blue-50 text-blue-800 ring-blue-200',
        'emerald' => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        'amber' => 'bg-amber-50 text-amber-800 ring-amber-200',
        'red' => 'bg-red-50 text-red-800 ring-red-200',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset '.($tonos[$tone] ?? $tonos['slate'])]) }}>{{ $slot }}</span>
