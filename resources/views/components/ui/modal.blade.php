{{--
    Modal basado en <dialog>. Se abre con <x-ui.button data-modal-open="{{ $id }}">.
    Pon el <form> completo (incluidos los botones, ver <x-form.actions modal>) dentro del slot.
    Con :open="true" se abre al cargar la página (p. ej. tras un error de validación).
--}}
@props([
    'id',
    'title',
    'size' => 'md',
    'open' => false,
])

@php
    $anchos = ['sm' => 'max-w-md', 'md' => 'max-w-lg', 'lg' => 'max-w-2xl', 'xl' => 'max-w-4xl'];
@endphp

<dialog id="{{ $id }}" aria-labelledby="{{ $id }}-titulo" @if ($open) data-open-on-load @endif
    class="erp-modal m-auto w-[calc(100%-2rem)] {{ $anchos[$size] ?? $anchos['md'] }} rounded-xl p-0 shadow-xl">
    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3">
        <h2 id="{{ $id }}-titulo" class="text-base font-semibold text-blue-950">{{ $title }}</h2>
        <button type="button" data-modal-close data-no-loading aria-label="Cerrar" class="rounded p-1 text-slate-500 hover:bg-slate-100">
            <x-ui.icon name="x" class="size-5" />
        </button>
    </div>
    <div class="max-h-[75vh] overflow-y-auto px-5 py-4">
        {{ $slot }}
    </div>
</dialog>
