<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Acceso;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloquea a quien tiene un rol de desguace pero ninguna sede válida asignada.
 *
 * Se aplica a todas las rutas web (y a las de la API). Solo quedan fuera las pantallas
 * que necesita esa cuenta para ver el aviso o salir: inicio, dashboard y logout.
 */
class ExigeDesguaceAsignado
{
    /**
     * Rutas que una cuenta sin sede puede seguir usando.
     */
    private const RUTAS_PERMITIDAS = ['home', 'dashboard', 'logout', 'login', 'register'];

    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (! $usuario instanceof User
            || $request->routeIs(...self::RUTAS_PERMITIDAS)
            || ! Acceso::sinDesguaceAsignado($usuario)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'data' => Acceso::AVISO_SIN_DESGUACE,
                'success' => 0,
            ], 403);
        }

        abort(403, Acceso::AVISO_SIN_DESGUACE);
    }
}
