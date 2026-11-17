@extends('layouts.app')

@section('title', 'Roles y permisos')

@section('content')
    <x-ui.page-header
        title="Roles y permisos"
        subtitle="Qué puede hacer cada rol en la aplicación."
        :breadcrumbs="[['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Administración'], ['label' => 'Roles y permisos']]"
    >
        <x-slot:actions>
            @if (Route::has('admin.usuarios.index'))
                @can('usuarios.ver')
                    <x-ui.button :href="route('admin.usuarios.index')" variant="outline" icon="users">Gestionar usuarios</x-ui.button>
                @endcan
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.alert type="info" class="mb-6">
        Esta matriz es solo de consulta: los permisos no se editan desde la web. La referencia es
        <code class="rounded bg-blue-100 px-1">RolePermissionSeeder</code>; para cambiar lo que puede hacer un rol hay que
        modificar ese seeder y volver a ejecutarlo. Para cambiar el rol de una persona usa la gestión de usuarios.
    </x-ui.alert>

    @if ($roles->isEmpty())
        <x-ui.card :padding="false">
            <x-ui.empty-state
                title="Todavía no hay roles en la base de datos"
                message="Los roles (administrador, gerente y operario) se crean con los seeders de la aplicación. Ejecuta PermissionSeeder, RoleSeeder y RolePermissionSeeder."
                icon="shield"
            />
        </x-ui.card>
    @else
        @php
            $nombresDeRol = ['administrador' => 'Administrador', 'gerente' => 'Gerente', 'operario' => 'Operario'];
            $etiquetasDeAccion = ['ver' => 'Ver', 'crear' => 'Crear', 'editar' => 'Editar', 'eliminar' => 'Eliminar'];
        @endphp

        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($roles as $rol)
                <x-ui.stat-card
                    :label="$nombresDeRol[$rol->name] ?? ucfirst($rol->name)"
                    :value="$rol->users_count.' '.($rol->users_count === 1 ? 'usuario' : 'usuarios')"
                    :hint="$rol->permissions->count().' permisos asignados'"
                    icon="shield"
                />
            @endforeach
        </div>

        <x-ui.card title="Permisos por recurso" subtitle="✓ permitido · — no permitido" :padding="false" class="mb-6">
            <x-ui.table>
                <x-slot:head>
                    <tr>
                        <th scope="col" rowspan="2" class="align-bottom">Recurso</th>
                        @foreach ($roles as $rol)
                            <th scope="colgroup" colspan="{{ count($acciones) }}" class="border-l border-slate-200 text-center">
                                {{ $nombresDeRol[$rol->name] ?? ucfirst($rol->name) }}
                            </th>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach ($roles as $rol)
                            @foreach ($acciones as $accion)
                                <th scope="col" @class(['text-center', 'border-l border-slate-200' => $loop->first])>{{ $etiquetasDeAccion[$accion] ?? ucfirst($accion) }}</th>
                            @endforeach
                        @endforeach
                    </tr>
                </x-slot:head>

                @forelse ($recursos as $recurso)
                    <tr>
                        <th scope="row" class="whitespace-nowrap text-left font-medium text-slate-900">{{ $recurso['nombre'] }}</th>
                        @foreach ($roles as $rol)
                            @foreach ($acciones as $accion)
                                <td @class(['text-center', 'border-l border-slate-200' => $loop->first])>
                                    @include('admin.partials.marca-permiso', [
                                        'permitido' => isset($permisosPorRol[$rol->name][$recurso['recurso'].'.'.$accion]),
                                        'descripcion' => $etiquetasDeAccion[$accion].' '.mb_strtolower($recurso['nombre']).' ('.$rol->name.')',
                                    ])
                                </td>
                            @endforeach
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 1 + $roles->count() * count($acciones) }}" class="text-center text-slate-500">No hay permisos definidos.</td>
                    </tr>
                @endforelse
            </x-ui.table>
        </x-ui.card>

        <x-ui.card title="Permisos administrativos" subtitle="Usuarios y registro del sistema." :padding="false">
            <x-ui.table>
                <x-slot:head>
                    <tr>
                        <th scope="col">Permiso</th>
                        @foreach ($roles as $rol)
                            <th scope="col" class="text-center">{{ $nombresDeRol[$rol->name] ?? ucfirst($rol->name) }}</th>
                        @endforeach
                    </tr>
                </x-slot:head>

                @forelse ($permisosAdministrativos as $permiso)
                    <tr>
                        <th scope="row" class="whitespace-nowrap text-left font-mono text-xs font-medium text-slate-900">{{ $permiso }}</th>
                        @foreach ($roles as $rol)
                            <td class="text-center">
                                @include('admin.partials.marca-permiso', [
                                    'permitido' => isset($permisosPorRol[$rol->name][$permiso]),
                                    'descripcion' => $permiso.' ('.$rol->name.')',
                                ])
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 1 + $roles->count() }}" class="text-center text-slate-500">No hay permisos administrativos definidos.</td>
                    </tr>
                @endforelse
            </x-ui.table>
        </x-ui.card>
    @endif
@endsection
