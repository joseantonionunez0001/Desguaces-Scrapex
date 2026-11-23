<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UsuarioApiController extends Controller
{
    // GET /api/usuarios
    public function index()
    {
        // El modelo User oculta password y remember_token, no se exponen aquí
        $usuarios = User::with(['desguace', 'roles'])->get();

        return response()->json([
            'data' => $usuarios,
            'success' => 1
        ], 200);
    }

    // GET /api/usuarios/1
    public function show(string $id)
    {
        $usuario = User::with(['desguace', 'roles'])->findOrFail($id);

        return response()->json([
            'data' => $usuario,
            'success' => 1
        ], 200);
    }

    // POST /api/usuarios
    public function store(Request $request)
    {
        // Los roles no se aceptan aquí: se asignan con asignarRol().
        $datos = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'id_desguace' => 'nullable|exists:desguaces,id_desguace',
        ]);

        // El cast 'hashed' del modelo se encarga de hashear la contraseña
        $usuario = new User();
        $usuario->name = $datos['name'];
        $usuario->email = $datos['email'];
        $usuario->password = $datos['password'];
        $usuario->id_desguace = $datos['id_desguace'] ?? null;
        $usuario->save();

        return response()->json([
            'data' => $usuario,
            'success' => 1
        ], 201);
    }

    // PUT /api/usuarios/1
    public function update(Request $request, string $id)
    {
        $usuario = User::findOrFail($id);

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
            'id_desguace' => 'sometimes|nullable|exists:desguaces,id_desguace',
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
        if (array_key_exists('id_desguace', $datos)) {
            $usuario->id_desguace = $datos['id_desguace'];
        }
        $usuario->save();

        return response()->json([
            'data' => $usuario,
            'success' => 1
        ], 200);
    }

    // DELETE /api/usuarios/1 (borrado lógico)
    public function destroy(Request $request, string $id): JsonResponse
    {
        $usuario = User::findOrFail($id);

        if ($usuario->is($request->user())) {
            return $this->prohibido('No puedes eliminar tu propia cuenta.');
        }

        $usuario->delete();

        return response()->json([
            'data' => 'Usuario eliminado',
            'success' => 1
        ], 200);
    }

    // PUT /api/usuarios/1/rol  Body: {"rol": "gerente"}  (asigna o cambia el rol)
    public function asignarRol(Request $request, string $id): JsonResponse
    {
        $datos = $request->validate([
            'rol' => ['required', 'string', Rule::in(RoleSeeder::ROLES)],
        ]);

        $usuario = User::findOrFail($id);

        if ($usuario->is($request->user())) {
            return $this->prohibido('No puedes modificar tus propios roles.');
        }

        // syncRoles deja al usuario con exactamente ese rol.
        $usuario->syncRoles([$datos['rol']]);

        return response()->json([
            'data' => $usuario->load(['desguace', 'roles']),
            'success' => 1
        ], 200);
    }

    // DELETE /api/usuarios/1/roles/gerente  (retira un rol concreto)
    public function retirarRol(Request $request, string $id, string $rol): JsonResponse
    {
        if (! in_array($rol, RoleSeeder::ROLES, true)) {
            return response()->json([
                'data' => 'Rol no válido',
                'success' => 0
            ], 422);
        }

        $usuario = User::findOrFail($id);

        if ($usuario->is($request->user())) {
            return $this->prohibido('No puedes modificar tus propios roles.');
        }

        // Evita dejar el sistema sin ningún administrador.
        if ($rol === RoleSeeder::ADMINISTRADOR
            && $usuario->hasRole($rol)
            && User::role(RoleSeeder::ADMINISTRADOR)->count() <= 1) {
            return response()->json([
                'data' => 'No se puede retirar el último administrador',
                'success' => 0
            ], 422);
        }

        $usuario->removeRole($rol);

        return response()->json([
            'data' => $usuario->load(['desguace', 'roles']),
            'success' => 1
        ], 200);
    }

    private function prohibido(string $mensaje): JsonResponse
    {
        return response()->json([
            'data' => $mensaje,
            'success' => 0
        ], 403);
    }
}
