@extends('layouts.app')

@section('title', 'Ficha de usuario')

@section('content')
    <x-ui.page-header
        :title="$usuario->name"
        subtitle="Datos de la cuenta y gestión de su rol."
        :breadcrumbs="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Usuarios', 'url' => route('admin.usuarios.index')],
            ['label' => $usuario->name],
        ]">
        <x-slot:actions>
            <x-ui.button :href="route('admin.usuarios.index')" variant="outline" icon="arrow-left">Volver al listado</x-ui.button>
            @if ($puedeEditar)
                <x-ui.button :href="route('admin.usuarios.edit', $usuario)" variant="outline" icon="pencil">Editar cuenta</x-ui.button>
            @endif
            @if ($puedeEliminar)
                <x-ui.delete-button
                    :action="route('admin.usuarios.destroy', $usuario)"
                    message="¿Seguro que quieres eliminar la cuenta de {{ $usuario->name }}?"
                    label="Eliminar la cuenta de {{ $usuario->name }}" />
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-ui.card title="Datos del usuario">
            <dl class="grid gap-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nombre</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">{{ $usuario->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Correo electrónico</dt>
                    <dd class="mt-0.5 break-all font-medium text-slate-900">{{ $usuario->email }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Desguace</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">{{ $usuario->desguace?->nombre ?? 'Sin desguace asignado' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fecha de registro</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">{{ $usuario->created_at?->format('d/m/Y H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Rol actual</dt>
                    <dd class="mt-1">
                        @if ($rolActual)
                            <x-ui.badge tone="blue">{{ $rolActual }}</x-ui.badge>
                        @else
                            <span class="text-slate-400">Ninguno</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Estado de asignación</dt>
                    <dd class="mt-1">
                        @if ($rolActual)
                            <x-ui.badge tone="emerald">Rol asignado</x-ui.badge>
                        @else
                            <x-ui.badge tone="amber">Pendiente de rol</x-ui.badge>
                        @endif
                    </dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card title="Asignación de rol" subtitle="Cada usuario tiene como máximo un rol.">
            @if ($esPropio)
                <x-ui.alert type="info">
                    Esta es tu propia cuenta. Por seguridad no puedes modificar tus propios privilegios:
                    otro administrador debe hacerlo.
                </x-ui.alert>
            @elseif (! $puedeAsignar)
                <x-ui.alert type="info">
                    No puedes cambiar el rol de esta cuenta. Los administradores de desguace solo gestionan
                    gerentes y operarios de su sede; los administradores se designan desde Desguaces.
                </x-ui.alert>
            @else
                <form method="POST" action="{{ route('admin.usuarios.rol.update', $usuario) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <x-form.select name="rol" label="Rol" :options="$roles" :value="$rolActual" placeholder="Selecciona un rol" required />

                    <x-form.actions :submit="$rolActual ? 'Cambiar rol' : 'Asignar rol'" class="mt-2" />
                </form>

                @if ($rolActual)
                    <div class="mt-5 rounded-lg border border-red-200 bg-red-50 p-4">
                        <p class="text-sm font-semibold text-red-900">Retirar el rol</p>
                        <p class="mt-1 text-sm text-red-800">
                            El usuario perderá el acceso a los módulos internos hasta que se le asigne otro rol.
                        </p>
                        <form method="POST" action="{{ route('admin.usuarios.rol.destroy', $usuario) }}" data-confirm="¿Retirar el rol «{{ $rolActual }}» a {{ $usuario->name }}?" class="mt-3">
                            @csrf
                            @method('DELETE')
                            <x-ui.button type="submit" variant="danger" size="sm" icon="trash">Retirar rol</x-ui.button>
                        </form>
                    </div>
                @endif
            @endif
        </x-ui.card>

        @if ($puedeCambiarSede)
            <x-ui.card title="Desguace asignado" subtitle="Procedimiento autorizado para cambiar la sede de la cuenta.">
                <form method="POST" action="{{ route('admin.usuarios.desguace.update', $usuario) }}" class="space-y-4"
                    data-confirm="¿Cambiar el desguace de {{ $usuario->name }}? Perderá el acceso a su sede actual y tendrá que volver a iniciar sesión.">
                    @csrf
                    @method('PUT')
                    <x-form.select name="id_desguace" label="Desguace" :options="$desguaces" :value="$usuario->id_desguace" placeholder="Selecciona un desguace" :required="true" />
                    <x-form.actions submit="Cambiar desguace" class="mt-2" />
                </form>
            </x-ui.card>
        @endif
    </div>
@endsection
