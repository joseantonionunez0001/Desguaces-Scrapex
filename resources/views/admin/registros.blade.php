@extends('layouts.app')

@section('title', 'Registros del sistema')

@section('content')
    <x-ui.page-header
        title="Registros del sistema"
        subtitle="Consulta de los registros de actividad guardados por la aplicación."
        :breadcrumbs="[['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Administración'], ['label' => 'Registros del sistema']]"
    />

    <x-ui.alert type="info" class="mb-4">
        Cada registro solo guarda el usuario y la fecha y hora. No se almacena qué acción realizó ni ningún otro detalle.
        Esta pantalla es de solo consulta: los registros no se pueden modificar ni eliminar.
    </x-ui.alert>

    @if ($errors->any())
        <x-ui.alert type="error" class="mb-4">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $mensaje)
                    <li>{{ $mensaje }}</li>
                @endforeach
            </ul>
        </x-ui.alert>
    @endif

    <x-ui.filters>
        <x-form.input name="q" label="Buscar por nombre o correo" :value="request('q')" :use-old="false" maxlength="100" placeholder="Nombre o correo" />
        <x-form.select name="usuario" label="Usuario" :options="$usuarios" :value="request('usuario')" placeholder="Todos los usuarios" :use-old="false" />
        <x-form.input name="desde" label="Desde" type="date" :value="request('desde')" :use-old="false" />
        <x-form.input name="hasta" label="Hasta" type="date" :value="request('hasta')" :use-old="false" />
    </x-ui.filters>

    <x-ui.card :title="$registros->total().' '.($registros->total() === 1 ? 'registro' : 'registros')" subtitle="Del más reciente al más antiguo." :padding="false">
        @if ($registros->isEmpty())
            <x-ui.empty-state
                title="No hay registros que mostrar"
                :message="request()->hasAny(['q', 'usuario', 'desde', 'hasta']) ? 'Ningún registro coincide con los filtros aplicados.' : 'Todavía no se ha guardado ningún registro del sistema.'"
                icon="clipboard"
            />
        @else
            <x-ui.table>
                <x-slot:head>
                    <tr>
                        <th scope="col">Id</th>
                        <th scope="col">Fecha y hora</th>
                        <th scope="col">Usuario</th>
                    </tr>
                </x-slot:head>
                @foreach ($registros as $registro)
                    <tr>
                        <td class="whitespace-nowrap text-slate-500">{{ $registro->id_registro }}</td>
                        <td class="whitespace-nowrap">{{ $registro->fecha_hora->format('d/m/Y H:i:s') }}</td>
                        <td>
                            @if ($registro->usuario)
                                <span class="font-medium text-slate-900">{{ $registro->usuario->name }}</span>
                                @if ($registro->usuario->trashed())
                                    <x-ui.badge tone="amber" class="ml-1">Usuario eliminado</x-ui.badge>
                                @endif
                                <span class="block break-all text-xs text-slate-500">{{ $registro->usuario->email }}</span>
                            @else
                                <span class="text-slate-500">Usuario desconocido</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
        @endif
    </x-ui.card>

    <div class="mt-4">
        <x-ui.pagination :paginator="$registros" />
    </div>
@endsection
