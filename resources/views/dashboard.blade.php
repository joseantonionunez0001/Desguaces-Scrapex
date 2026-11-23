@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <h2>Bienvenido, {{ $usuario->name }}</h2>
    <p><strong>Correo:</strong> {{ $usuario->email }}</p>

    @if ($sinRol)
        <div class="aviso">
            Tu cuenta está pendiente de autorización. Un administrador debe asignarte un rol
            antes de que puedas utilizar las funciones del desguace.
        </div>
    @else
        <p><strong>Rol:</strong>
            @foreach ($roles as $rol)
                <span class="etiqueta">{{ $rol }}</span>
            @endforeach
        </p>

        <h3>Accesos autorizados</h3>
        <p>La gestión del desguace se realiza a través de la API con un token de acceso.</p>
        <ul>
            @foreach ($accesos as $recurso => $permisos)
                <li><strong>{{ $recurso }}</strong>: {{ implode(', ', $permisos) }}</li>
            @endforeach
        </ul>
    @endif
@endsection
