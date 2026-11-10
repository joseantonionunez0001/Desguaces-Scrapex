<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    // GET /dashboard
    public function __invoke(Request $request): View
    {
        $usuario = $request->user();

        $roles = $usuario->getRoleNames();

        // Los permisos se agrupan por recurso ("vehiculos.ver" -> "vehiculos"),
        // para mostrar solo los accesos que el usuario tiene realmente.
        $accesos = $usuario->getAllPermissions()
            ->pluck('name')
            ->groupBy(fn (string $permiso) => explode('.', $permiso)[0])
            ->map(fn ($permisos) => $permisos->values()->all())
            ->sortKeys();

        return view('dashboard', [
            'usuario' => $usuario,
            'roles' => $roles,
            'accesos' => $accesos,
            'sinRol' => $roles->isEmpty(),
        ]);
    }
}
