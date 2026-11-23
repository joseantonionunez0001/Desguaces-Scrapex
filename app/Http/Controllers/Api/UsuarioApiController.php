<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Desguace;
use App\Models\User;
use App\Services\AdministracionDesguaces;
use App\Support\Acceso;
use Database\Seeders\RoleSeeder;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Cuentas de usuario.
 * - Administrador de plataforma: consulta cuentas y cambia la sede (no gestiona el equipo).
 * - Administrador de desguace: crea y gestiona gerentes y operarios de SU sede. La sede se asigna
 *   en el servidor, nunca se acepta desde el formulario.
 */
class UsuarioApiController extends Controller
{
    // GET /api/usuarios
    public function index(Request $request)
    {
        // El modelo User oculta password y remember_token, no se exponen aqui
        $usuarios = $this->consulta($request)->with(['desguace', 'roles'])->get();

        return response()->json([
            'data' => $usuarios,
            'success' => 1,
        ], 200);
    }

    // GET /api/usuarios/1
    public function show(Request $request, string $id)
    {
        $usuario = $this->consulta($request)->with(['desguace', 'roles'])->findOrFail($id);

        return response()->json([
            'data' => $usuario,
            'success' => 1,
        ], 200);
    }

    // POST /api/usuarios  Body: name, email, password, rol (gerente|operario)
    public function store(Request $request)
    {
        $datos = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'rol' => ['required', 'string', Rule::in(RoleSeeder::ROLES_GESTIONABLES)],
            // La sede no se elige: la fija el servidor segun el administrador autenticado.
            'id_desguace' => 'prohibited',
        ]);

        // El cast 'hashed' del modelo se encarga de hashear la contraseña
        $usuario = new User;
        $usuario->name = $datos['name'];
        $usuario->email = $datos['email'];
        $usuario->password = $datos['password'];
        $usuario->id_desguace = Acceso::desguaceId($request->user());
        $usuario->save();
        $usuario->assignRole($datos['rol']);

        return response()->json([
            'data' => $usuario->load(['desguace', 'roles']),
            'success' => 1,
        ], 201);
    }

    // PUT /api/usuarios/1
    public function update(Request $request, string $id)
    {
        $usuario = $this->consulta($request)->findOrFail($id);

        if ($respuesta = $this->rechazarSiNoGestionable($request, $usuario)) {
            return $respuesta;
        }

        $datos = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($usuario->getKey(), $usuario->getKeyName()),
            ],
            'password' => 'sometimes|required|string|min:8',
            'rol' => ['sometimes', 'required', 'string', Rule::in(RoleSeeder::ROLES_GESTIONABLES)],
            // Cambiar de sede a un usuario solo lo puede hacer la plataforma.
            'id_desguace' => 'prohibited',
        ]);

        if (array_key_exists('name', $datos)) {
            $usuario->name = $datos['name'];
        }
        if (array_key_exists('email', $datos)) {
            $usuario->email = $datos['email'];
        }
        if (array_key_exists('password', $datos)) {
            $usuario->password = $datos['password'];
        }
        $usuario->save();

        if (array_key_exists('rol', $datos)) {
            $usuario->syncRoles([$datos['rol']]);
        }

        return response()->json([
            'data' => $usuario->load(['desguace', 'roles']),
            'success' => 1,
        ], 200);
    }

    // DELETE /api/usuarios/1 (borrado lógico)
    public function destroy(Request $request, string $id): JsonResponse
    {
        $usuario = $this->consulta($request)->findOrFail($id);

        if ($respuesta = $this->rechazarSiNoGestionable($request, $usuario)) {
            return $respuesta;
        }

        $usuario->tokens()->delete();
        $usuario->delete();

        return response()->json([
            'data' => 'Usuario eliminado',
            'success' => 1,
        ], 200);
    }

    // PUT /api/usuarios/1/rol  Body: {"rol": "gerente"}  (asigna o cambia el rol)
    public function asignarRol(Request $request, string $id): JsonResponse
    {
        $datos = $request->validate([
            'rol' => ['required', 'string', Rule::in(RoleSeeder::ROLES_GESTIONABLES)],
        ]);

        $usuario = $this->consulta($request)->findOrFail($id);

        if ($respuesta = $this->rechazarSiNoGestionable($request, $usuario)) {
            return $respuesta;
        }

        // syncRoles deja al usuario con exactamente ese rol.
        $usuario->syncRoles([$datos['rol']]);

        return response()->json([
            'data' => $usuario->load(['desguace', 'roles']),
            'success' => 1,
        ], 200);
    }

    // DELETE /api/usuarios/1/roles/gerente  (retira un rol concreto)
    public function retirarRol(Request $request, string $id, string $rol): JsonResponse
    {
        if (! in_array($rol, RoleSeeder::ROLES_GESTIONABLES, true)) {
            return response()->json([
                'data' => 'Rol no válido',
                'success' => 0,
            ], 422);
        }

        $usuario = $this->consulta($request)->findOrFail($id);

        if ($respuesta = $this->rechazarSiNoGestionable($request, $usuario)) {
            return $respuesta;
        }

        $usuario->removeRole($rol);

        return response()->json([
            'data' => $usuario->load(['desguace', 'roles']),
            'success' => 1,
        ], 200);
    }

    // PUT /api/usuarios/1/desguace  Body: {"id_desguace": 2}
    // Procedimiento autorizado para cambiar la sede de una cuenta (solo administrador de plataforma).
    public function asignarDesguace(Request $request, string $id, AdministracionDesguaces $administracion): JsonResponse
    {
        $datos = $request->validate([
            'id_desguace' => ['required', 'integer', Rule::exists('desguaces', 'id_desguace')->whereNull('deleted_at')],
        ]);

        $usuario = User::findOrFail($id);
        $desguace = Desguace::findOrFail($datos['id_desguace']);

        try {
            $usuario = $administracion->asignarDesguace($usuario, $desguace);
        } catch (DomainException $e) {
            return response()->json([
                'data' => $e->getMessage(),
                'success' => 0,
            ], 422);
        }

        return response()->json([
            'data' => $usuario->load(['desguace', 'roles']),
            'success' => 1,
        ], 200);
    }

    // Cuentas visibles: la plataforma ve todas; un administrador de desguace, solo las de su sede.
    private function consulta(Request $request): Builder
    {
        $actor = $request->user();

        if ($actor->esAdministradorPlataforma()) {
            return User::query();
        }

        // Sin sede valida el middleware ya habria bloqueado la peticion; esto es una red de seguridad.
        $idDesguace = Acceso::desguaceId($actor);

        return $idDesguace === null
            ? User::query()->whereRaw('1 = 0')
            : User::query()->delDesguace($idDesguace);
    }

    // Un administrador de desguace solo gestiona gerentes, operarios y cuentas sin rol de su sede.
    private function rechazarSiNoGestionable(Request $request, User $objetivo): ?JsonResponse
    {
        $esAdministracion = $objetivo->hasAnyRole([
            RoleSeeder::PLATAFORMA,
            RoleSeeder::ADMIN_DESGUACE,
            RoleSeeder::ADMINISTRADOR,
        ]);

        if ($objetivo->is($request->user()) || $esAdministracion) {
            return response()->json([
                'data' => 'No puedes gestionar esta cuenta.',
                'success' => 0,
            ], 403);
        }

        return null;
    }
}
