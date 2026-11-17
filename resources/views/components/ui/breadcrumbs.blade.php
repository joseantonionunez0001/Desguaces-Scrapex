{{-- items: lista de ['label' => string, 'url' => ?string]. El último elemento no lleva enlace. --}}
@props(['items' => []])

@if (count($items) > 0)
    <nav aria-label="Ruta de navegación" class="mb-1">
        <ol class="flex flex-wrap items-center gap-1 text-xs text-slate-500">
            @foreach ($items as $item)
                <li class="flex items-center gap-1">
                    @if (! $loop->first)
                        <x-ui.icon name="chevron-right" class="size-3" />
                    @endif
                    @if (! empty($item['url']) && ! $loop->last)
                        <a href="{{ $item['url'] }}" class="hover:text-blue-700 hover:underline">{{ $item['label'] }}</a>
                    @else
                        <span @if ($loop->last) aria-current="page" class="font-medium text-slate-700" @endif>{{ $item['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
