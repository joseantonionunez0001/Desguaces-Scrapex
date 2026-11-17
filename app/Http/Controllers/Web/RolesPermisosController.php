<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Pantalla 20: matriz de solo lectura de roles × permisos.
 *
 * Los datos se leen de Spatie (los mismos que aplican las rutas). Los permisos no se editan desde la web:
 * la matriz de referencia es RolePermissionSeeder.
 */
class RolesPermisosController extends Controller
{
    /** Acciones que forman las columnas de la matriz por recurso. */
    private const ACCIONES = PermissionSeeder::ACCIONES_CRUD;

    /** Nombres legibles de los recursos; los que no estén aquí se muestran con su nombre de permiso. */
    private const NOMBRES_RECURSOS = [
        'desguaces' => 'Desguaces',
        'modulos' => 'Módulos',
        'pasillos' => 'Pasillos',
        'estanterias' => 'Estanterías',
        'etiquetas' => 'Etiquetas',
        'patios' => 'Patios',
        'sectores' => 'Sectores',
        'filas' => 'Filas',
        'posiciones' => 'Posiciones',
        'niveles' => 'Niveles',
        'vehiculos' => 'Vehículos',
        'ubicaciones_vehiculo' => 'Ubicaciones de vehículos',
        'categorias' => 'Categorías',
        'tipo_piezas' => 'Tipos de pieza',
        'piezas' => 'Piezas',
        'movimientos' => 'Movimientos',
    ];

    public function index(): View
    {
        $roles = $this->rolesOrdenados();
        $permisos = Permission::query()->where('guard_name', 'web')->orderBy('name')->pluck('name');

        return view('admin.roles-permisos', [
            'roles' => $roles,
            'permisosPorRol' => $roles->mapWithKeys(
                fn (Role $rol): array => [$rol->name => $rol->permissions->pluck('name')->flip()->all()],
            )->all(),
            'acciones' => self::ACCIONES,
            'recursos' => $this->recursos($permisos),
            'permisosAdministrativos' => $this->permisosAdministrativos($permisos),
        ]);
    }

    /**
     * Los tres roles del sistema, en el orden de RoleSeeder (los que no existan en la BD se omiten).
     *
     * @return Collection<int, Role>
     */
    private function rolesOrdenados(): Collection
    {
        $orden = array_flip(RoleSeeder::ROLES);

        return Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', RoleSeeder::ROLES)
            ->with('permissions')
            ->withCount('users')
            ->get()
            ->sortBy(fn (Role $rol): int => $orden[$rol->name])
            ->values();
    }

    /**
     * Recursos con permisos CRUD (todo lo que no sea administrativo), en el orden del seeder.
     *
     * @param  Collection<int, string>  $permisos
     * @return list<array{recurso: string, nombre: string}>
     */
    private function recursos(Collection $permisos): array
    {
        $recursos = $permisos
            ->reject(fn (string $permiso): bool => $this->esAdministrativo($permiso))
            ->map(fn (string $permiso): string => Str::before($permiso, '.'))
            ->unique()
            ->sortBy(function (string $recurso): int {
                $posicion = array_search($recurso, PermissionSeeder::RECURSOS_CRUD, true);

                return $posicion === false ? PHP_INT_MAX : $posicion;
            })
            ->values();

        return $recursos
            ->map(fn (string $recurso): array => [
                'recurso' => $recurso,
                'nombre' => self::NOMBRES_RECURSOS[$recurso] ?? Str::headline($recurso),
            ])
            ->all();
    }

    /**
     * Bloque administrativo: usuarios.*, registro_sistema.* y cualquier permiso fuera del CRUD estándar.
     *
     * @param  Collection<int, string>  $permisos
     * @return list<string>
     */
    private function permisosAdministrativos(Collection $permisos): array
    {
        return $permisos
            ->filter(fn (string $permiso): bool => $this->esAdministrativo($permiso))
            ->values()
            ->all();
    }

    private function esAdministrativo(string $permiso): bool
    {
        $recurso = Str::before($permiso, '.');
        $accion = Str::after($permiso, '.');

        return in_array($permiso, PermissionSeeder::PERMISOS_ADMINISTRATIVOS, true)
            || in_array($recurso, ['usuarios', 'registro_sistema'], true)
            || ! in_array($accion, self::ACCIONES, true);
    }
}
