<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Route;

/**
 * Calcula las opciones de navegación visibles para un usuario.
 * Lo usan el menú lateral y los accesos rápidos del dashboard.
 *
 * Solo es presentación: las rutas comprueban rol, permiso y sede en el servidor.
 */
class Navegacion
{
    /**
     * @return array<string, list<array{label: string, descripcion: string, route: string, icon: string, permission: ?string, active: list<string>, url: string, activo: bool}>>
     */
    public static function para(?Authenticatable $usuario): array
    {
        // Sin sede asignada solo se ofrece el dashboard, donde se muestra el aviso.
        $sinSede = $usuario instanceof User && Acceso::sinDesguaceAsignado($usuario);
        $grupos = [];

        foreach (config('navegacion.grupos', []) as $nombreGrupo => $elementos) {
            $visibles = [];

            foreach ($elementos as $elemento) {
                if (! Route::has($elemento['route'])) {
                    continue;
                }

                if ($elemento['permission'] !== null && ($sinSede || ! $usuario?->can($elemento['permission']))) {
                    continue;
                }

                $elemento['url'] = route($elemento['route']);
                $elemento['activo'] = request()->routeIs(...$elemento['active']);
                $visibles[] = $elemento;
            }

            if ($visibles !== []) {
                $grupos[$nombreGrupo] = $visibles;
            }
        }

        return $grupos;
    }
}
