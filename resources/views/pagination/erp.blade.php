@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Paginación" class="flex flex-wrap items-center justify-center gap-1">
        @if ($paginator->onFirstPage())
            <span class="cursor-not-allowed rounded-md border border-slate-200 bg-slate-50 px-3 py-1.5 text-slate-400" aria-disabled="true">Anterior</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-slate-700 hover:bg-slate-50">Anterior</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="px-2 text-slate-400">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page" class="rounded-md border border-blue-900 bg-blue-900 px-3 py-1.5 font-semibold text-white">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" aria-label="Ir a la página {{ $page }}" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-slate-700 hover:bg-slate-50">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-slate-700 hover:bg-slate-50">Siguiente</a>
        @else
            <span class="cursor-not-allowed rounded-md border border-slate-200 bg-slate-50 px-3 py-1.5 text-slate-400" aria-disabled="true">Siguiente</span>
        @endif
    </nav>
@endif
