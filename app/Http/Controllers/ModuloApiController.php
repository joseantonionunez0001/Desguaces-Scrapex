<?php

namespace App\Http\Controllers;

use App\Models\Modulo;
use Illuminate\Http\Request;

class ModuloApiController extends Controller
{
    // GET /api/modulos
    public function index()
    {
        $modulos = Modulo::all();

        return response()->json([
            'data' => $modulos,
            'success' => 1
        ], 200);
    }

    // GET /api/modulos/1
    public function show(string $id)
    {
        $modulo = Modulo::findOrFail($id);

        return response()->json([
            'data' => $modulo,
            'success' => 1
        ], 200);
    }

    // POST /api/modulos
    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre_modulo' => 'required|string|max:255',
            'desguace_id' => 'required|integer|exists:desguaces,id'
        ]);

        $modulo = new Modulo($datos);
        $modulo->save();

        return response()->json([
            'data' => $modulo,
            'success' => 1
        ], 201);
    }

    // PUT/PATCH /api/modulos/1
    // 'sometimes' permite enviar solo los campos que se quieran cambiar (PATCH).
    public function update(Request $request, string $id)
    {
        $modulo = Modulo::findOrFail($id);

        $datos = $request->validate([
            'nombre_modulo' => 'sometimes|required|string|max:255',
            'desguace_id' => 'sometimes|required|integer|exists:desguaces,id'
        ]);

        $modulo->fill($datos);
        $modulo->save();

        return response()->json([
            'data' => $modulo,
            'success' => 1
        ], 200);
    }

    // DELETE /api/modulos/1
    public function destroy(string $id)
    {
        $modulo = Modulo::findOrFail($id);
        $modulo->delete();

        return response()->json([
            'data' => 'Modulo eliminado',
            'success' => 1
        ], 200);
    }
}
