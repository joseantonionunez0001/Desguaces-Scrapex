<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Roles del sistema. Se crean con Spatie Laravel Permission, no con una tabla propia.
     */
    public const ADMINISTRADOR = 'administrador';
    public const GERENTE = 'gerente';
    public const OPERARIO = 'operario';

    public const ROLES = [
        self::ADMINISTRADOR,
        self::GERENTE,
        self::OPERARIO,
    ];

    public function run(): void
    {
        foreach (self::ROLES as $nombre) {
            Role::firstOrCreate([
                'name' => $nombre,
                'guard_name' => 'web',
            ]);
        }
    }
}
