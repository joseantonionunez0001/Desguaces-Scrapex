{{--
    Menú lateral. Solo muestra los accesos cuya ruta existe y para los que el usuario tiene permiso
    (ver config/navegacion.php y App\Support\Navegacion). Un usuario sin rol solo ve el Dashboard.
--}}
@php
    $grupos = \App\Support\Navegacion::para(auth()->user());
@endphp

<div data-sidebar-overlay class="fixed inset-0 z-30 hidden bg-slate-900/50 lg:hidden" aria-hidden="true"></div>

<aside id="app-sidebar" aria-label="Menú principal"
    class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-blue-950 text-blue-100 transition-transform duration-200 lg:static lg:translate-x-0">
    <div class="flex h-16 shrink-0 items-center gap-3 border-b border-white/10 px-5">
        <span class="flex size-9 items-center justify-center rounded-lg bg-blue-600 text-white">
            <x-ui.icon name="truck" class="size-5" />
        </span>
        <a href="{{ route('dashboard') }}" class="truncate text-base font-bold tracking-tight text-white">{{ config('navegacion.marca') }}</a>
    </div>

    <nav class="flex-1 space-y-5 overflow-y-auto px-3 py-4">
        @foreach ($grupos as $nombreGrupo => $elementos)
            <div>
                <p class="px-3 text-xs font-semibold uppercase tracking-wider text-blue-300/70">{{ $nombreGrupo }}</p>
                <ul class="mt-2 space-y-1">
                    @foreach ($elementos as $elemento)
                        <li>
                            <a href="{{ $elemento['url'] }}" @if ($elemento['activo']) aria-current="page" @endif
                                @class([
                                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                                    'bg-blue-600 text-white shadow-sm' => $elemento['activo'],
                                    'text-blue-100 hover:bg-white/10 hover:text-white' => ! $elemento['activo'],
                                ])>
                                <x-ui.icon :name="$elemento['icon']" class="size-5 shrink-0" />
                                <span class="truncate">{{ $elemento['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    <div class="shrink-0 border-t border-white/10 p-3">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" data-no-loading class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-blue-100 transition hover:bg-white/10 hover:text-white">
                <x-ui.icon name="logout" class="size-5" />
                Cerrar sesión
            </button>
        </form>
    </div>
</aside>
