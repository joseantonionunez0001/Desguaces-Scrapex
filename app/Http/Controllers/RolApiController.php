<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use Illuminate\Http\Request;

class RolApiController extends Controller
{
    // GET /api/roles
    public function index()
    {
        $roles = Rol::all();

        return response()->json([
            'data' => $roles,
            'success' => 1
        ], 200);
    }

    // GET /api/roles/1
    public function show(string $id)
    {
        $rol = Rol::findOrFail($id);

        return response()->json([
            'data' => $rol,
            'success' => 1
        ], 200);
    }

    // POST /api/roles
    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:255'
        ]);

        $rol = new Rol($datos);
        $rol->save();

        return response()->json([
            'data' => $rol,
            'success' => 1
        ], 201);
    }

    // PUT/PATCH /api/roles/1
    // 'sometimes' permite enviar solo los campos que se quieran cambiar (PATCH).
    public function update(Request $request, string $id)
    {
        $rol = Rol::findOrFail($id);

        $datos = $request->validate([
            'nombre' => 'sometimes|required|string|max:255'
        ]);

        $rol->fill($datos);
        $rol->save();

        return response()->json([
            'data' => $rol,
            'success' => 1
        ], 200);
    }

    // DELETE /api/roles/1
    public function destroy(string $id)
    {
        $rol = Rol::findOrFail($id);
        $rol->delete();

        return response()->json([
            'data' => 'Rol eliminado',
            'success' => 1
        ], 200);
    }
}
