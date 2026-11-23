<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Matriz central de permisos por rol.
 *
 * Para cambiar que puede hacer cada rol, solo hay que tocar esta clase.
 * El ALCANCE por desguace no se define aqui: lo aplica el servidor en cada
 * consulta (ver App\Models\Scopes\AlcanceDesguaceScope).
 * syncPermissions() deja el rol con exactamente estos permisos, asi que
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

    // Lo único que ve un operario: listados y fichas de vehículos (con su historial de ubicaciones) y piezas.
    // Sin acceso a módulos de gestión (desguaces, categorías, almacén, movimientos, usuarios).
    private const OPERARIO_CONSULTA = [
        'vehiculos',
        'ubicaciones_vehiculo',
        'piezas',
    ];

    public function run(): void
    {
        $crud = PermissionSeeder::RECURSOS_CRUD;

        // PLATAFORMA: configura desguaces y administradores. Sin permisos de inventario.
        Role::findByName(RoleSeeder::PLATAFORMA, 'web')->syncPermissions([
            'desguaces.ver',
            'desguaces.crear',
            'desguaces.editar',
            'desguaces.eliminar',
            'desguaces.asignar_administrador',
            'usuarios.ver',
            'usuarios.asignar_desguace',
        ]);

        // ADMIN_DESGUACE: todo el inventario de su sede y su equipo (gerentes y operarios).
        // La sede (desguaces) solo se consulta: la edita la plataforma.
        $permisosAdminDesguace = [];
        foreach ($crud as $recurso) {
            $permisosAdminDesguace[] = "{$recurso}.ver";
            if ($recurso === 'desguaces') {
                continue;
            }
            $permisosAdminDesguace[] = "{$recurso}.crear";
            $permisosAdminDesguace[] = "{$recurso}.editar";
            $permisosAdminDesguace[] = "{$recurso}.eliminar";
        }
        array_push(
            $permisosAdminDesguace,
            'usuarios.ver',
            'usuarios.crear',
            'usuarios.editar',
            'usuarios.eliminar',
            'usuarios.asignar_roles',
            'registro_sistema.ver',
            'registro_sistema.crear',
        );
        Role::findByName(RoleSeeder::ADMIN_DESGUACE, 'web')->syncPermissions($permisosAdminDesguace);

        // GERENTE: consulta de todo el almacen de su sede, altas y ediciones operativas.
        $permisosGerente = [];
        foreach ($crud as $recurso) {
            $permisosGerente[] = "{$recurso}.ver";
        }
        foreach (self::GERENTE_CREA_EDITA as $recurso) {
            $permisosGerente[] = "{$recurso}.crear";
            $permisosGerente[] = "{$recurso}.editar";
        }
        Role::findByName(RoleSeeder::GERENTE, 'web')->syncPermissions($permisosGerente);

        // OPERARIO: solo lectura de vehiculos y piezas. Ninguna escritura.
        $permisosOperario = [];
        foreach (self::OPERARIO_CONSULTA as $recurso) {
            $permisosOperario[] = "{$recurso}.ver";
        }
        Role::findByName(RoleSeeder::OPERARIO, 'web')->syncPermissions($permisosOperario);

        // Rol anterior "administrador": se conserva sin permisos hasta que se reasigne a mano.
        Role::findByName(RoleSeeder::ADMINISTRADOR, 'web')->syncPermissions([]);
    }
}
