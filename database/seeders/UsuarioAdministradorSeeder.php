<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Crea o actualiza la cuenta de administrador de la aplicación.
 *
 * Requiere ADMIN_PASSWORD en el .env. Si no está definida, no hace nada.
 * No se ejecuta con DatabaseSeeder: hay que lanzarlo de forma explícita.
 */
class UsuarioAdministradorSeeder extends Seeder
{
    public function run(): void
    {
        $contrasena = config('administrador.password');

        if (blank($contrasena)) {
            $this->command?->warn('ADMIN_PASSWORD no está definida en el .env. No se ha creado el administrador.');

            return;
        }

        $usuario = User::firstOrCreate(
            ['email' => config('administrador.email')],
            ['name' => 'Javi Ruiz', 'password' => $contrasena],
        );

        $usuario->syncRoles([RoleSeeder::ADMINISTRADOR]);
    }
}
