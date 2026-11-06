<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UsuarioApiController extends Controller
{
    // GET /api/usuarios
    public function index()
    {
        $usuarios = Usuario::all();

        return response()->json([
            'data' => $usuarios,
            'success' => 1
        ], 200);
    }

    // GET /api/usuarios/1
    public function show(string $id)
    {
        $usuario = Usuario::findOrFail($id);

        return response()->json([
            'data' => $usuario,
            'success' => 1
        ], 200);
    }

    // POST /api/usuarios
    public function store(Request $request)
    {
        $datos = $request->validate([
            'rol_id' => 'required|integer|exists:roles,id',
            'desguace_id' => 'required|integer|exists:desguaces,id',
            'nombre' => 'required|string|max:255',
            'correo' => 'required|string|email|max:255|unique:usuarios,correo',
            'contrasena' => 'required|string|min:8|max:255'
        ]);

        $usuario = new Usuario($datos);
        $usuario->save();

        return response()->json([
            'data' => $usuario,
            'success' => 1
        ], 201);
    }

    // PUT/PATCH /api/usuarios/1
    // 'sometimes' permite enviar solo los campos que se quieran cambiar (PATCH).
    public function update(Request $request, string $id)
    {
        $usuario = Usuario::findOrFail($id);

        $datos = $request->validate([
            'rol_id' => 'sometimes|required|integer|exists:roles,id',
            'desguace_id' => 'sometimes|required|integer|exists:desguaces,id',
            'nombre' => 'sometimes|required|string|max:255',
            'correo' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('usuarios', 'correo')->ignore($usuario->id)
            ],
            'contrasena' => 'sometimes|required|string|min:8|max:255'
        ]);

        $usuario->fill($datos);
        $usuario->save();

        return response()->json([
            'data' => $usuario,
            'success' => 1
        ], 200);
    }

    // DELETE /api/usuarios/1
    public function destroy(string $id)
    {
        $usuario = Usuario::findOrFail($id);
        $usuario->delete();

        return response()->json([
            'data' => 'Usuario eliminado',
            'success' => 1
        ], 200);
    }
}
