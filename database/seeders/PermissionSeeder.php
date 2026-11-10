<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Recursos gestionables del desguace, en formato de permiso (sin guiones).
     * Cada uno tiene las acciones CRUD estándar.
     */
    public const RECURSOS_CRUD = [
        'desguaces',
        'modulos',
        'pasillos',
        'estanterias',
        'etiquetas',
        'patios',
        'sectores',
        'filas',
        'posiciones',
        'niveles',
        'vehiculos',
        'ubicaciones_vehiculo',
        'categorias',
        'tipo_piezas',
        'piezas',
        'movimientos',
    ];

    public const ACCIONES_CRUD = ['ver', 'crear', 'editar', 'eliminar'];

    /**
     * Permisos administrativos. Los usuarios y la auditoría NO tienen CRUD público.
     */
    public const PERMISOS_ADMINISTRATIVOS = [
        'usuarios.ver',
        'usuarios.crear',
        'usuarios.editar',
        'usuarios.eliminar',
        'usuarios.asignar_roles',
        'registro_sistema.ver',
        'registro_sistema.crear',
    ];

    public function run(): void
    {
        foreach (self::RECURSOS_CRUD as $recurso) {
            foreach (self::ACCIONES_CRUD as $accion) {
                Permission::firstOrCreate([
                    'name' => "{$recurso}.{$accion}",
                    'guard_name' => 'web',
                ]);
            }
        }

        foreach (self::PERMISOS_ADMINISTRATIVOS as $permiso) {
            Permission::firstOrCreate([
                'name' => $permiso,
                'guard_name' => 'web',
            ]);
        }
    }
}
