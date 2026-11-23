<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\AdministracionDesguaces;
use DomainException;
use Illuminate\Database\Seeder;

/**
 * Crea o actualiza la cuenta de administrador de plataforma de la aplicación.
 *
 * Requiere ADMIN_PASSWORD en el .env. Si no está definida, no hace nada.
 * No se ejecuta con DatabaseSeeder: hay que lanzarlo de forma explícita, porque concede
 * el rol de administrador de plataforma (ver DOCUMENTACION_ACCESO_POR_DESGUACE.md).
 * La cuenta no recibe ningún desguace: la plataforma no accede al inventario.
 */
class UsuarioAdministradorSeeder extends Seeder
{
    public function run(AdministracionDesguaces $administracion): void
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

        try {
            $administracion->habilitarPlataforma($usuario);
        } catch (DomainException $excepcion) {
            $this->command?->warn($excepcion->getMessage());
        }
    }
}
