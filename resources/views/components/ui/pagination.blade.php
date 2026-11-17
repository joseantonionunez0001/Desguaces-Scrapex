{{-- Paginación con contador. El paginador debe construirse con ->withQueryString() para conservar filtros. --}}
@props(['paginator'])

@if ($paginator->total() > 0)
    <div class="flex flex-col items-center justify-between gap-3 border-t border-slate-200 px-4 py-3 text-sm text-slate-600 sm:flex-row">
        <p>
            Mostrando <span class="font-semibold">{{ $paginator->firstItem() }}</span>
            a <span class="font-semibold">{{ $paginator->lastItem() }}</span>
            de <span class="font-semibold">{{ $paginator->total() }}</span> registros
        </p>
        {{ $paginator->onEachSide(1)->links('pagination.erp') }}
    </div>
@endif
