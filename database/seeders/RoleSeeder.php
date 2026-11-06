<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Crea los roles base de la aplicación con spatie/laravel-permission.
     */
    public function run(): void
    {
        foreach (['admin', 'usuario'] as $roleName) {
            Role::findOrCreate($roleName, 'web');
        }
    }
}
