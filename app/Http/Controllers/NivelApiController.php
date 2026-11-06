<?php

namespace App\Http\Controllers;

use App\Models\Nivel;
use Illuminate\Http\Request;

class NivelApiController extends Controller
{
    // GET /api/niveles
    public function index()
    {
        $niveles = Nivel::all();

        return response()->json([
            'data' => $niveles,
            'success' => 1
        ], 200);
    }

    // GET /api/niveles/1
    public function show(string $id)
    {
        $nivel = Nivel::findOrFail($id);

        return response()->json([
            'data' => $nivel,
            'success' => 1
        ], 200);
    }

    // POST /api/niveles
    public function store(Request $request)
    {
        $datos = $request->validate([
            'posicion_id' => 'required|integer|exists:posiciones,id',
            'numero_nivel' => 'required|string|max:20'
        ]);

        $nivel = new Nivel();
        $nivel->posicion_id = $datos['posicion_id'];
        $nivel->numero_nivel = $datos['numero_nivel'];
        $nivel->save();

        return response()->json([
            'data' => $nivel,
            'success' => 1
        ], 201);
    }

    // PUT/PATCH /api/niveles/1
    // 'sometimes' permite enviar solo los campos que se quieran cambiar (PATCH).
    public function update(Request $request, string $id)
    {
        $nivel = Nivel::findOrFail($id);

        $datos = $request->validate([
            'posicion_id' => 'sometimes|required|integer|exists:posiciones,id',
            'numero_nivel' => 'sometimes|required|string|max:20'
        ]);

        $nivel->fill($datos);
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
