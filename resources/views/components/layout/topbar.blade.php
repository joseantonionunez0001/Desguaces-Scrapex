@php
    $usuario = auth()->user();
    $roles = $usuario->getRoleNames();
@endphp

<header class="sticky top-0 z-20 flex h-16 items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 sm:px-6">
    <div class="flex items-center gap-3">
        <button type="button" data-sidebar-toggle data-no-loading aria-controls="app-sidebar" aria-expanded="false" aria-label="Abrir o cerrar el menú"
            class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden">
            <x-ui.icon name="menu" class="size-6" />
        </button>
        <p class="hidden text-sm text-slate-500 sm:block">@yield('title', 'Panel')</p>
    </div>

    <details class="relative">
        <summary class="flex cursor-pointer list-none items-center gap-3 rounded-lg px-2 py-1.5 hover:bg-slate-100 [&::-webkit-details-marker]:hidden">
            <span class="flex size-9 items-center justify-center rounded-full bg-blue-900 text-sm font-semibold text-white" aria-hidden="true">
                {{ mb_strtoupper(mb_substr($usuario->name, 0, 1)) }}
            </span>
            <span class="hidden text-left sm:block">
                <span class="block text-sm font-semibold leading-tight text-slate-800">{{ $usuario->name }}</span>
                <span class="block text-xs leading-tight text-slate-500">{{ $roles->isEmpty() ? 'Sin rol asignado' : $roles->implode(', ') }}</span>
            </span>
            <x-ui.icon name="chevron-down" class="size-4 text-slate-500" />
        </summary>

        <div class="absolute right-0 mt-2 w-64 rounded-xl border border-slate-200 bg-white p-3 shadow-lg">
            <p class="truncate text-sm font-semibold text-slate-800">{{ $usuario->name }}</p>
            <p class="truncate text-xs text-slate-500">{{ $usuario->email }}</p>
            <div class="mt-2 flex flex-wrap gap-1">
                @forelse ($roles as $rol)
                    <x-ui.badge tone="blue">{{ $rol }}</x-ui.badge>
                @empty
                    <x-ui.badge tone="amber">Pendiente de autorización</x-ui.badge>
                @endforelse
            </div>
            <form method="POST" action="{{ route('logout') }}" class="mt-3 border-t border-slate-100 pt-3">
                @csrf
                <x-ui.button type="submit" variant="outline" size="sm" icon="logout" class="w-full" data-no-loading>Cerrar sesión</x-ui.button>
            </form>
        </div>
    </details>
</header>
