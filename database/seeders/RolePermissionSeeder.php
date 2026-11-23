<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Matriz central de permisos por rol.
 *
 * Para cambiar qué puede hacer cada rol, solo hay que tocar esta clase.
 * Los controladores y las rutas no contienen ninguna lógica de roles.
 * syncPermissions() deja el rol con exactamente estos permisos, así que
 * el seeder puede ejecutarse varias veces sin duplicar nada.
 */
class RolePermissionSeeder extends Seeder
{
    // Recursos en los que el gerente puede crear y editar (no eliminar).
    private const GERENTE_CREA_EDITA = [
        'modulos',
        'pasillos',
        'estanterias',
        'etiquetas',
        'patios',
        'sectores',
        'filas',
        'posiciones',
        'niveles',
        'ubicaciones_vehiculo',
        'categorias',
        'tipo_piezas',
        'piezas',
        'vehiculos',
        'movimientos',
    ];

    public function run(): void
    {
        $crud = PermissionSeeder::RECURSOS_CRUD;

        // ADMINISTRADOR: todos los permisos definidos.
        Role::findByName(RoleSeeder::ADMINISTRADOR, 'web')
            ->syncPermissions(Permission::where('guard_name', 'web')->get());

        // GERENTE: consulta de todo el almacén y del desguace, altas y ediciones operativas.
        $permisosGerente = [];
        foreach ($crud as $recurso) {
            $permisosGerente[] = "{$recurso}.ver";
        }
        foreach (self::GERENTE_CREA_EDITA as $recurso) {
            $permisosGerente[] = "{$recurso}.crear";
            $permisosGerente[] = "{$recurso}.editar";
        }
        Role::findByName(RoleSeeder::GERENTE, 'web')
            ->syncPermissions($permisosGerente);

        // OPERARIO: solo consulta, y registro de movimientos operativos.
        $permisosOperario = [];
        foreach ($crud as $recurso) {
            $permisosOperario[] = "{$recurso}.ver";
        }
        $permisosOperario[] = 'movimientos.crear';
        Role::findByName(RoleSeeder::OPERARIO, 'web')
            ->syncPermissions($permisosOperario);
    }
}
