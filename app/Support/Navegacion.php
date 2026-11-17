<?php

namespace App\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Route;

/**
 * Calcula las opciones de navegación visibles para un usuario.
 * Lo usan el menú lateral y los accesos rápidos del dashboard.
 */
class Navegacion
{
    /**
     * @return array<string, list<array{label: string, descripcion: string, route: string, icon: string, permission: ?string, active: list<string>, url: string, activo: bool}>>
     */
    public static function para(?Authenticatable $usuario): array
    {
        $grupos = [];

        foreach (config('navegacion.grupos', []) as $nombreGrupo => $elementos) {
            $visibles = [];

            foreach ($elementos as $elemento) {
                if (! Route::has($elemento['route'])) {
                    continue;
                }

                if ($elemento['permission'] !== null && ! $usuario?->can($elemento['permission'])) {
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
