@props([
    'title' => null,
    'subtitle' => null,
    'padding' => true,
])

<section {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-white shadow-sm']) }}>
    @if ($title || isset($actions))
        <header class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 px-5 py-3">
            <div>
                @if ($title)
                    <h2 class="text-base font-semibold text-blue-950">{{ $title }}</h2>
                @endif
                @if ($subtitle)
                    <p class="text-sm text-slate-500">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex flex-wrap gap-2">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    <div @class(['p-5' => $padding])>
        {{ $slot }}
    </div>

    @isset($footer)
        <footer class="rounded-b-xl border-t border-slate-200 bg-slate-50 px-5 py-3">{{ $footer }}</footer>
    @endisset
</section>
