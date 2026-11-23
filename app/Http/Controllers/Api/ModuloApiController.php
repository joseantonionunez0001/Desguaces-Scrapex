<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Modulo;
use Illuminate\Http\Request;

class ModuloApiController extends Controller
{
    // GET /api/modulos
    public function index()
    {
        $modulos = Modulo::with('desguace')->get();

        return response()->json([
            'data' => $modulos,
            'success' => 1
        ], 200);
    }

    // GET /api/modulos/1
    public function show(string $id)
    {
        $modulo = Modulo::with('desguace')->findOrFail($id);

        return response()->json([
            'data' => $modulo,
            'success' => 1
        ], 200);
    }

    // POST /api/modulos
    public function store(Request $request)
    {
        $datos = $request->validate([
            'id_desguace' => 'required|exists:desguaces,id_desguace',
            'nombre_modulo' => 'required|string|max:255',
        ]);

        $modulo = new Modulo();
        $modulo->id_desguace = $datos['id_desguace'];
        $modulo->nombre_modulo = $datos['nombre_modulo'];
        $modulo->save();

        return response()->json([
            'data' => $modulo,
            'success' => 1
        ], 201);
    }

    // PUT /api/modulos/1
    public function update(Request $request, string $id)
    {
        $modulo = Modulo::findOrFail($id);

        $datos = $request->validate([
            'id_desguace' => 'required|exists:desguaces,id_desguace',
            'nombre_modulo' => 'required|string|max:255',
        ]);

        $modulo->id_desguace = $datos['id_desguace'];
        $modulo->nombre_modulo = $datos['nombre_modulo'];
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
