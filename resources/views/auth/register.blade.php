@extends('layouts.app')

@section('title', 'Register')

@section('content')
    <h2>Crear cuenta</h2>
    <p>Tu cuenta quedará pendiente de autorización hasta que un administrador te asigne un rol.</p>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <label for="name">Nombre</label>
        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus>
        @error('name')<div class="error">{{ $message }}</div>@enderror

        <label for="email">Correo electrónico</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required>
        @error('email')<div class="error">{{ $message }}</div>@enderror

        <label for="password">Contraseña</label>
        <input id="password" type="password" name="password" required>
        @error('password')<div class="error">{{ $message }}</div>@enderror

        <label for="password_confirmation">Confirmar contraseña</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required>

        <button type="submit">Registrarse</button>
    </form>
@endsection
