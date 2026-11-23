@extends('layouts.app')

@section('title', 'Login')

@section('content')
    <h2>Iniciar sesión</h2>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <label for="email">Correo electrónico</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
        @error('email')<div class="error">{{ $message }}</div>@enderror

        <label for="password">Contraseña</label>
        <input id="password" type="password" name="password" required>
        @error('password')<div class="error">{{ $message }}</div>@enderror

        <label>
            <input type="checkbox" name="remember" style="width:auto"> Recordarme
        </label>

        <button type="submit">Entrar</button>
    </form>
@endsection
