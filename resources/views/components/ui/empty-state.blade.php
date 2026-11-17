@props([
    'title' => 'No hay registros',
    'message' => null,
    'icon' => 'cube',
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-12 text-center']) }}>
    <span class="flex size-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
        <x-ui.icon :name="$icon" class="size-6" />
    </span>
    <h3 class="mt-3 text-sm font-semibold text-slate-800">{{ $title }}</h3>
    @if ($message)
        <p class="mt-1 max-w-md text-sm text-slate-500">{{ $message }}</p>
    @endif
    @if (trim((string) $slot) !== '')
        <div class="mt-4 flex flex-wrap justify-center gap-2">{{ $slot }}</div>
    @endif
</div>
