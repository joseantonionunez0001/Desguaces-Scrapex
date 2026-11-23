@extends('layouts.guest')

@section('title', 'Register')

@section('content')
    <div class="flex flex-1 items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <x-ui.card>
                <h1 class="text-2xl font-bold text-blue-950">Crear cuenta</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Tu cuenta quedará pendiente de autorización hasta que un administrador te asigne un rol.
                </p>

                <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4" novalidate>
                    @csrf

                    <x-form.input name="name" label="Nombre" required autofocus autocomplete="name" maxlength="255" />
                    <x-form.input name="email" type="email" label="Correo electrónico" required autocomplete="username" maxlength="255" />
                    <x-form.input name="password" type="password" label="Contraseña" required autocomplete="new-password"
                        hint="Mínimo 8 caracteres." />
                    <x-form.input name="password_confirmation" type="password" label="Confirmar contraseña" required autocomplete="new-password" />

                    <x-ui.button type="submit" class="w-full" icon="user">Registrarse</x-ui.button>
                </form>
            </x-ui.card>

            <p class="mt-4 text-center text-sm text-slate-600">
                ¿Ya tienes cuenta?
                <a href="{{ route('login') }}" class="font-semibold text-blue-700 hover:underline">Inicia sesión</a>
            </p>
        </div>
    </div>
@endsection
