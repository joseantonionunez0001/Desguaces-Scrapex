<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    // GET /admin/usuarios
    public function index(Request $request): View
    {
        $busqueda = $request->query('q');
        $busqueda = is_string($busqueda) ? trim($busqueda) : '';

        $rol = $request->query('rol');
        $rol = is_string($rol) && in_array($rol, RoleSeeder::ROLES, true) ? $rol : null;

        $estado = $request->query('estado');
        $estado = in_array($estado, ['con_rol', 'sin_rol'], true) ? $estado : null;

        $usuarios = User::query()
            ->with(['roles', 'desguace'])
            ->when($busqueda !== '', function ($consulta) use ($busqueda) {
                $consulta->where(function ($consulta) use ($busqueda) {
                    $consulta->where('name', 'like', "%{$busqueda}%")
                        ->orWhere('email', 'like', "%{$busqueda}%");
                });
            })
            ->when($rol !== null, fn ($consulta) => $consulta->role($rol))
            ->when($estado === 'con_rol', fn ($consulta) => $consulta->has('roles'))
            ->when($estado === 'sin_rol', fn ($consulta) => $consulta->doesntHave('roles'))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('usuarios.index', [
            'usuarios' => $usuarios,
            'filtros' => ['q' => $busqueda, 'rol' => $rol, 'estado' => $estado],
            'roles' => array_combine(RoleSeeder::ROLES, array_map('ucfirst', RoleSeeder::ROLES)),
            'totales' => [
                'usuarios' => User::count(),
                'con_rol' => User::has('roles')->count(),
                'sin_rol' => User::doesntHave('roles')->count(),
            ],
        ]);
    }

    // GET /admin/usuarios/{user}
    public function show(Request $request, User $user): View
    {
        $user->load(['roles', 'desguace']);

        return view('usuarios.show', [
            'usuario' => $user,
            'rolActual' => $user->getRoleNames()->first(),
            'esPropio' => $user->is($request->user()),
            'puedeAsignar' => $request->user()->can('usuarios.asignar_roles'),
            'roles' => array_combine(RoleSeeder::ROLES, array_map('ucfirst', RoleSeeder::ROLES)),
        ]);
    }
}
