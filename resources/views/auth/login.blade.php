@extends('layouts.guest')

@section('title', 'Login')

@section('content')
    <div class="flex flex-1 items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <x-ui.card>
                <h1 class="text-2xl font-bold text-blue-950">Iniciar sesión</h1>
                <p class="mt-1 text-sm text-slate-500">Accede con tu correo y tu contraseña.</p>

                <x-ui.flash />

                <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4" novalidate>
                    @csrf

                    <x-form.input name="email" type="email" label="Correo electrónico" required autofocus autocomplete="username" />
                    <x-form.input name="password" type="password" label="Contraseña" required autocomplete="current-password" />

                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="remember" value="1" class="size-4 rounded border-slate-300 text-blue-900 focus:ring-blue-600">
                        Recordarme en este equipo
                    </label>

                    <x-ui.button type="submit" class="w-full" icon="lock">Entrar</x-ui.button>
                </form>
            </x-ui.card>

            <p class="mt-4 text-center text-sm text-slate-600">
                ¿No tienes cuenta?
                <a href="{{ route('register') }}" class="font-semibold text-blue-700 hover:underline">Regístrate</a>
            </p>
        </div>
    </div>
@endsection
