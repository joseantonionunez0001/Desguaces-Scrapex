<?php

use App\Http\Middleware\ExigeDesguaceAsignado;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Alias de Spatie Laravel Permission para proteger rutas:
        // middleware('permission:vehiculos.ver'), 'role:administrador', 'role_or_permission:...'
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            // Bloquea a quien tiene rol de desguace pero ninguna sede asignada.
            'desguace.asignado' => ExigeDesguaceAsignado::class,
        ]);

        // Toda la web queda protegida por defecto: un módulo nuevo no puede olvidarse de la comprobación.
        $middleware->appendToGroup('web', ExigeDesguaceAsignado::class);

        // Sede y permisos se comprueban ANTES de resolver los modelos de la URL: sin permiso se responde 403,
        // y una ficha de otra sede (oculta por el alcance) responde 404 solo a quien sí tiene permiso.
        foreach ([
            ExigeDesguaceAsignado::class,
            RoleMiddleware::class,
            PermissionMiddleware::class,
            RoleOrPermissionMiddleware::class,
        ] as $comprobacion) {
            $middleware->prependToPriorityList(
                before: SubstituteBindings::class,
                prepend: $comprobacion,
            );
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
