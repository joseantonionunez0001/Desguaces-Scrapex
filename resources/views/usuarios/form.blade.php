@extends('layouts.app')

@php
    $esEdicion = $usuario->exists;
    $titulo = $esEdicion ? 'Editar cuenta' : 'Nueva cuenta del equipo';
@endphp

@section('title', $titulo)

@section('content')
    <x-ui.page-header
        :title="$titulo"
        subtitle="Gerentes y operarios de tu desguace."
        :breadcrumbs="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Usuarios', 'url' => route('admin.usuarios.index')],
            ['label' => $titulo],
        ]" />

    <x-ui.card class="max-w-2xl">
        <form method="POST" action="{{ $esEdicion ? route('admin.usuarios.update', $usuario) : route('admin.usuarios.store') }}" novalidate>
            @csrf
            @if ($esEdicion)
                @method('PUT')
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.input name="name" label="Nombre" :required="true" maxlength="255" :value="$usuario->name" class="sm:col-span-2" />
                <x-form.input name="email" type="email" label="Correo electrónico" :required="true" maxlength="255" :value="$usuario->email" class="sm:col-span-2" />
                <x-form.input name="password" type="password" :label="$esEdicion ? 'Nueva contraseña (opcional)' : 'Contraseña'"
                    :required="! $esEdicion" autocomplete="new-password" />
                <x-form.input name="password_confirmation" type="password" label="Repite la contraseña"
                    :required="! $esEdicion" autocomplete="new-password" />
                <x-form.select name="rol" label="Rol" :options="$roles" :value="$rolActual" placeholder="Selecciona un rol" :required="true" />
                <div>
                    <p class="erp-label">Desguace</p>
                    <p class="mt-2 text-sm font-medium text-slate-800">{{ $desguace?->nombre }}</p>
                    <p class="mt-1 text-xs text-slate-500">Scrapex lo asigna automáticamente con tu sede.</p>
                </div>
            </div>

            <x-form.actions :cancel="$esEdicion ? route('admin.usuarios.show', $usuario) : route('admin.usuarios.index')"
                :submit="$esEdicion ? 'Guardar cambios' : 'Crear cuenta'" />
        </form>
    </x-ui.card>
@endsection
