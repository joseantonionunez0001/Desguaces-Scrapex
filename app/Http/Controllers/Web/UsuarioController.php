<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\GuardarUsuarioRequest;
use App\Models\Desguace;
use App\Models\User;
use App\Services\AdministracionDesguaces;
use App\Support\Acceso;
use Database\Seeders\RoleSeeder;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Cuentas de usuario.
 *
 * - Administrador de plataforma: consulta todas las cuentas y cambia la sede de gerentes,
 *   operarios y cuentas sin rol (los administradores se designan desde Desguaces).
 * - Administrador de desguace: crea, edita y elimina los gerentes y operarios de SU sede.
 *   La sede la asigna el servidor; las cuentas de otras sedes responden 404.
 */
class UsuarioController extends Controller
{
    /**
     * GET /admin/usuarios
     */
    public function index(Request $request): View
    {
        $actor = $request->user();
        $busqueda = $request->query('q');
        $busqueda = is_string($busqueda) ? trim($busqueda) : '';

        $rol = $request->query('rol');
        $rol = is_string($rol) && in_array($rol, RoleSeeder::ROLES, true) ? $rol : null;

        $estado = $request->query('estado');
        $estado = in_array($estado, ['con_rol', 'sin_rol'], true) ? $estado : null;

        $usuarios = Acceso::usuariosVisibles($actor)
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
            'roles' => $this->etiquetasDeRoles(RoleSeeder::ROLES),
            'puedeCrear' => $actor->can('usuarios.crear') && Acceso::rolesConcedibles($actor) !== [],
            'totales' => [
                'usuarios' => Acceso::usuariosVisibles($actor)->count(),
                'con_rol' => Acceso::usuariosVisibles($actor)->has('roles')->count(),
                'sin_rol' => Acceso::usuariosVisibles($actor)->doesntHave('roles')->count(),
            ],
        ]);
    }

    /**
     * GET /admin/usuarios/crear
     */
    public function create(Request $request): View
    {
        $actor = $request->user();
        abort_if(Acceso::rolesConcedibles($actor) === [], 403);

        return view('usuarios.form', [
            'usuario' => new User,
            'rolActual' => null,
            'roles' => $this->etiquetasDeRoles(Acceso::rolesConcedibles($actor)),
            'desguace' => $actor->desguace,
        ]);
    }

    /**
     * POST /admin/usuarios
     */
    public function store(GuardarUsuarioRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        $usuario = new User;
        $usuario->name = $datos['name'];
        $usuario->email = $datos['email'];
        // El cast "hashed" del modelo cifra la contraseña.
        $usuario->password = $datos['password'];
        // Sede asignada en el servidor: la del administrador autenticado.
        $usuario->id_desguace = Acceso::desguaceId($request->user());
        $usuario->save();
        $usuario->syncRoles([$datos['rol']]);

        return redirect()
            ->route('admin.usuarios.show', $usuario)
            ->with('success', "Cuenta de {$usuario->name} creada como {$datos['rol']}.");
    }

    /**
     * GET /admin/usuarios/{user}
     */
    public function show(Request $request, User $user): View
    {
        $actor = $request->user();
        $user = $this->visible($actor, $user);
        $user->load(['roles', 'desguace']);
        $puedeGestionar = Acceso::puedeGestionarEquipo($actor, $user);

        return view('usuarios.show', [
            'usuario' => $user,
            'rolActual' => $user->getRoleNames()->first(),
            'esPropio' => $user->is($actor),
            'puedeAsignar' => $puedeGestionar && $actor->can('usuarios.asignar_roles'),
            'puedeEditar' => $puedeGestionar && $actor->can('usuarios.editar'),
            'puedeEliminar' => $puedeGestionar && $actor->can('usuarios.eliminar'),
            'puedeCambiarSede' => $actor->can('usuarios.asignar_desguace')
                && ! $user->hasAnyRole([RoleSeeder::PLATAFORMA, RoleSeeder::ADMIN_DESGUACE]),
            'roles' => $this->etiquetasDeRoles(Acceso::rolesConcedibles($actor)),
            'desguaces' => $actor->can('usuarios.asignar_desguace')
                ? Desguace::query()->orderBy('nombre')->pluck('nombre', 'id_desguace')
                : collect(),
        ]);
    }

    /**
     * GET /admin/usuarios/{user}/editar
     */
    public function edit(Request $request, User $user): View
    {
        $actor = $request->user();
        $user = $this->gestionable($actor, $user);

        return view('usuarios.form', [
            'usuario' => $user,
            'rolActual' => $user->getRoleNames()->first(),
            'roles' => $this->etiquetasDeRoles(Acceso::rolesConcedibles($actor)),
            'desguace' => $actor->desguace,
        ]);
    }

    /**
     * PUT /admin/usuarios/{user}
     */
    public function update(GuardarUsuarioRequest $request, User $user): RedirectResponse
    {
        $user = $this->gestionable($request->user(), $user);
        $datos = $request->validated();

        $user->name = $datos['name'];
        $user->email = $datos['email'];
        if (filled($datos['password'] ?? null)) {
            $user->password = $datos['password'];
        }
        $user->save();
        $user->syncRoles([$datos['rol']]);

        return redirect()
            ->route('admin.usuarios.show', $user)
            ->with('success', "Cuenta de {$user->name} actualizada.");
    }

    /**
     * DELETE /admin/usuarios/{user}  (borrado lógico)
     */
    public function destroy(Request $request, User $user, AdministracionDesguaces $administracion): RedirectResponse
    {
        $user = $this->gestionable($request->user(), $user);

        $administracion->revocarAccesos($user);
        $user->delete();

        return redirect()
            ->route('admin.usuarios.index')
            ->with('success', "Cuenta de {$user->name} eliminada.");
    }

    /**
     * PUT /admin/usuarios/{user}/desguace
     * Procedimiento autorizado para cambiar la sede de una cuenta (solo administrador de plataforma).
     */
    public function asignarDesguace(Request $request, User $user, AdministracionDesguaces $administracion): RedirectResponse
    {
        $datos = $request->validate([
            'id_desguace' => ['required', 'integer', Rule::exists('desguaces', 'id_desguace')->whereNull('deleted_at')],
        ], [
            'id_desguace.required' => 'Selecciona el desguace.',
            'id_desguace.integer' => 'El desguace seleccionado no es válido.',
            'id_desguace.exists' => 'El desguace seleccionado no es válido.',
        ]);

        try {
            $administracion->asignarDesguace($user, Desguace::findOrFail($datos['id_desguace']));
        } catch (DomainException $excepcion) {
            return back()->with('error', $excepcion->getMessage());
        }

        return redirect()
            ->route('admin.usuarios.show', $user)
            ->with('success', "{$user->name} pasa a pertenecer al desguace seleccionado. Su acceso a la sede anterior queda revocado.");
    }

    /**
     * La cuenta debe estar dentro del alcance del usuario; si no, 404 (como si no existiera).
     */
    private function visible(User $actor, User $objetivo): User
    {
        return Acceso::usuariosVisibles($actor)->findOrFail($objetivo->getKey());
    }

    /**
     * La cuenta debe ser visible y gestionable por el administrador de desguace autenticado.
     */
    private function gestionable(User $actor, User $objetivo): User
    {
        $objetivo = $this->visible($actor, $objetivo);
        abort_unless(Acceso::puedeGestionarEquipo($actor, $objetivo), 403, 'No puedes gestionar esta cuenta.');

        return $objetivo;
    }

    /**
     * @param  list<string>  $roles
     * @return array<string, string>
     */
    private function etiquetasDeRoles(array $roles): array
    {
        $nombres = [
            RoleSeeder::PLATAFORMA => 'Administrador de plataforma',
            RoleSeeder::ADMIN_DESGUACE => 'Administrador de desguace',
            RoleSeeder::GERENTE => 'Gerente',
            RoleSeeder::OPERARIO => 'Operario',
            RoleSeeder::ADMINISTRADOR => 'Administrador (anterior, sin permisos)',
        ];

        return collect($roles)->mapWithKeys(fn (string $rol): array => [$rol => $nombres[$rol] ?? ucfirst($rol)])->all();
    }
}
