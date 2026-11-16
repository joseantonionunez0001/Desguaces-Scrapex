<?php

namespace Tests\Support;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Laravel\Sanctum\Sanctum;

/**
 * Utilidades de autorización para las pruebas.
 * Los roles y permisos se crean con los mismos seeders que usa la aplicación.
 */
trait PreparaAutorizacion
{
    protected function prepararRoles(): void
    {
        $this->seed([
            PermissionSeeder::class,
            RoleSeeder::class,
            RolePermissionSeeder::class,
        ]);
    }

    protected function usuarioConRol(?string $rol = null): User
    {
        $usuario = User::factory()->create();

        if ($rol !== null) {
            $usuario->assignRole($rol);
        }

        return $usuario;
    }

    // Autentica las peticiones JSON siguientes con un token de Sanctum.
    protected function actuarComo(?string $rol = null): User
    {
        $usuario = $this->usuarioConRol($rol);
        Sanctum::actingAs($usuario, ['*']);

        return $usuario;
    }
}
