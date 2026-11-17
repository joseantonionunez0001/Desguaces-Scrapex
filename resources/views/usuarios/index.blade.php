@extends('layouts.app')

@section('title', 'Gestión de usuarios')

@section('content')
    <x-ui.page-header
        title="Gestión de usuarios"
        subtitle="Cuentas registradas y rol asignado a cada una."
        :breadcrumbs="[['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Usuarios']]" />

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <x-ui.stat-card label="Usuarios registrados" :value="$totales['usuarios']" icon="users" tone="blue" />
        <x-ui.stat-card label="Con rol asignado" :value="$totales['con_rol']" icon="shield" tone="emerald" />
        <x-ui.stat-card label="Pendientes de rol" :value="$totales['sin_rol']" icon="clock" :tone="$totales['sin_rol'] > 0 ? 'amber' : 'slate'" />
    </div>

    <x-ui.filters columns="sm:grid-cols-2 lg:grid-cols-3">
        <x-form.input name="q" label="Buscar por nombre o correo" :value="$filtros['q']" :use-old="false" placeholder="Nombre o correo electrónico" maxlength="100" />
        <x-form.select name="rol" label="Rol" :options="$roles" :value="$filtros['rol']" placeholder="Todos los roles" :use-old="false" />
        <x-form.select name="estado" label="Estado de asignación" :value="$filtros['estado']" placeholder="Todos"
            :options="['con_rol' => 'Con rol asignado', 'sin_rol' => 'Pendiente de rol']" :use-old="false" />
    </x-ui.filters>

    <x-ui.card :padding="false">
        @if ($usuarios->isEmpty())
            <x-ui.empty-state title="No se han encontrado usuarios"
                message="Prueba con otra búsqueda o limpia los filtros." icon="users" />
        @else
            <x-ui.table>
                <x-slot:head>
                    <tr>
                        <th>Usuario</th>
                        <th>Rol actual</th>
                        <th>Estado</th>
                        <th>Desguace</th>
                        <th>Registro</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </x-slot:head>

                @foreach ($usuarios as $usuario)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-semibold text-blue-800" aria-hidden="true">
                                    {{ mb_strtoupper(mb_substr($usuario->name, 0, 1)) }}
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-slate-900">{{ $usuario->name }}</p>
                                    <p class="truncate text-xs text-slate-500">{{ $usuario->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td>
                            @forelse ($usuario->roles as $rol)
                                <x-ui.badge tone="blue">{{ $rol->name }}</x-ui.badge>
                            @empty
                                <span class="text-slate-400">—</span>
                            @endforelse
                        </td>
                        <td>
                            @if ($usuario->roles->isEmpty())
                                <x-ui.badge tone="amber">Pendiente de rol</x-ui.badge>
                            @else
                                <x-ui.badge tone="emerald">Rol asignado</x-ui.badge>
                            @endif
                        </td>
                        <td>{{ $usuario->desguace?->nombre ?? '—' }}</td>
                        <td class="whitespace-nowrap">{{ $usuario->created_at?->format('d/m/Y') }}</td>
                        <td class="text-right">
                            <x-ui.button :href="route('admin.usuarios.show', $usuario)" variant="outline" size="sm" icon="eye">Ver ficha</x-ui.button>
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>

            <x-ui.pagination :paginator="$usuarios" />
        @endif
    </x-ui.card>
@endsection
