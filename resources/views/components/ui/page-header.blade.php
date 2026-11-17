{{-- Cabecera de página: migas de pan, título, subtítulo y botones de acción (slot "actions"). --}}
@props([
    'title',
    'subtitle' => null,
    'breadcrumbs' => [],
])

<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
    <div class="min-w-0">
        <x-ui.breadcrumbs :items="$breadcrumbs" />
        <h1 class="text-2xl font-bold tracking-tight text-blue-950">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
