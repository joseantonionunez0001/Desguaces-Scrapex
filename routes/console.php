<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Designa administrador a una cuenta EXISTENTE. Solo se ejecuta en la consola del servidor,
// nunca desde la API, y no crea contraseñas ni cuentas nuevas.
Artisan::command('desguace:asignar-administrador {email : Correo de una cuenta ya registrada}', function () {
    if (! Role::where('name', RoleSeeder::ADMINISTRADOR)->where('guard_name', 'web')->exists()) {
        $this->error('El rol administrador no existe. Ejecuta antes: php artisan db:seed --class=RoleSeeder');

        return 1;
    }

    $usuario = User::where('email', $this->argument('email'))->first();

    if (! $usuario) {
        $this->error('No existe ninguna cuenta con ese correo. Regístrate primero en la aplicación.');

        return 1;
    }

    $usuario->assignRole(RoleSeeder::ADMINISTRADOR);

    $this->info("La cuenta {$usuario->email} ya tiene el rol administrador.");

    return 0;
})->purpose('Asigna el rol administrador a una cuenta registrada');
