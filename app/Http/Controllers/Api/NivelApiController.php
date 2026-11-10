<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Nivel;
use Illuminate\Http\Request;

class NivelApiController extends Controller
{
    // GET /api/niveles
    public function index()
    {
        $niveles = Nivel::with('posicion')->get();

        return response()->json([
            'data' => $niveles,
            'success' => 1
        ], 200);
    }

    // GET /api/niveles/1
    public function show(string $id)
    {
        $nivel = Nivel::with('posicion')->findOrFail($id);

        return response()->json([
            'data' => $nivel,
            'success' => 1
        ], 200);
    }

    // POST /api/niveles
    public function store(Request $request)
    {
        $datos = $request->validate([
            'id_posicion' => 'required|exists:posiciones,id_posicion',
            'numero_nivel' => 'required|string|max:50',
        ]);

        $nivel = new Nivel();
        $nivel->id_posicion = $datos['id_posicion'];
        $nivel->numero_nivel = $datos['numero_nivel'];
        $nivel->save();

        return response()->json([
            'data' => $nivel,
            'success' => 1
        ], 201);
    }

    // PUT /api/niveles/1
    public function update(Request $request, string $id)
    {
        $nivel = Nivel::findOrFail($id);

        $datos = $request->validate([
            'id_posicion' => 'required|exists:posiciones,id_posicion',
            'numero_nivel' => 'required|string|max:50',
        ]);

        $nivel->id_posicion = $datos['id_posicion'];
        $nivel->numero_nivel = $datos['numero_nivel'];
        $nivel->save();

        return response()->json([
            'data' => $nivel,
            'success' => 1
        ], 200);
    }

    // DELETE /api/niveles/1
    public function destroy(string $id)
    {
        $nivel = Nivel::findOrFail($id);
        $nivel->delete();

        return response()->json([
            'data' => 'Nivel eliminado',
            'success' => 1
        ], 200);
    }
}
