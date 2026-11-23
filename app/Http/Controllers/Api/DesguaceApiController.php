<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Desguace;
use App\Models\User;
use App\Services\AdministracionDesguaces;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DesguaceApiController extends Controller
{
    // GET /api/desguaces
    public function index()
    {
        $desguaces = Desguace::all();

        return response()->json([
            'data' => $desguaces,
            'success' => 1,
        ], 200);
    }

    // GET /api/desguaces/1
    public function show(string $id)
    {
        $desguace = Desguace::findOrFail($id);

        return response()->json([
            'data' => $desguace,
            'success' => 1,
        ], 200);
    }

    // POST /api/desguaces
    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'required|string|max:255',
            'numero_telefono' => 'required|string|max:20',
            'ubicación' => 'required|string|max:255',
        ]);

        $desguace = new Desguace;
        $desguace->nombre = $datos['nombre'];
        $desguace->direccion = $datos['direccion'];
        $desguace->numero_telefono = $datos['numero_telefono'];
        $desguace->ubicación = $datos['ubicación'];
        $desguace->save();

        return response()->json([
            'data' => $desguace,
            'success' => 1,
        ], 201);
    }

    // PUT /api/desguaces/1
    public function update(Request $request, string $id)
    {
        $desguace = Desguace::findOrFail($id);

        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'required|string|max:255',
            'numero_telefono' => 'required|string|max:20',
            'ubicación' => 'required|string|max:255',
        ]);

        $desguace->nombre = $datos['nombre'];
        $desguace->direccion = $datos['direccion'];
        $desguace->numero_telefono = $datos['numero_telefono'];
        $desguace->ubicación = $datos['ubicación'];
        $desguace->save();

        return response()->json([
            'data' => $desguace,
            'success' => 1,
        ], 200);
    }

    // DELETE /api/desguaces/1
    public function destroy(string $id)
    {
        $desguace = Desguace::findOrFail($id);
        $desguace->delete();

        return response()->json([
            'data' => 'Desguace eliminada',
            'success' => 1,
        ], 200);
    }

    // PUT /api/desguaces/1/administrador
    // Designa o sustituye al administrador de la sede. Dos formas de uso:
    //   {"id_usuario": 7}                       -> cuenta existente
    //   {"name": "...", "email": "...", "password": "..."}  -> alta de una cuenta nueva
    // El administrador anterior pierde el rol. Solo el administrador de plataforma puede hacerlo.
    public function asignarAdministrador(Request $request, string $id, AdministracionDesguaces $administracion)
    {
        $desguace = Desguace::findOrFail($id);

        $datos = $request->validate([
            'id_usuario' => ['nullable', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at'), 'prohibits:name,email,password'],
            'name' => ['required_without:id_usuario', 'string', 'max:255'],
            'email' => ['required_without:id_usuario', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required_without:id_usuario', 'string', 'min:8'],
        ]);

        try {
            $usuario = isset($datos['id_usuario'])
                ? $administracion->designarAdministrador($desguace, User::findOrFail($datos['id_usuario']))
                : $administracion->altaAdministrador($desguace, $datos);
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
}
