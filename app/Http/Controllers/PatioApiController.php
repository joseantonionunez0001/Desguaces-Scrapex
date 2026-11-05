<?php

namespace App\Http\Controllers;

use App\Models\Patio;
use Illuminate\Http\Request;

class PatioApiController extends Controller
{
    // GET /api/patios
    public function index()
    {
        $patios = Patio::all();

        return response()->json([
            'data' => $patios,
            'success' => 1
        ], 200);
    }

    // GET /api/patios/1
    public function show(string $id)
    {
        $patio = Patio::findOrFail($id);

        return response()->json([
            'data' => $patio,
            'success' => 1
        ], 200);
    }

    // POST /api/patios
    public function store(Request $request)
    {
        $datos = $request->validate([
            'desguace_id' => 'required|integer|exists:desguaces,id',
            'codigo' => 'required|string|max:50',
            'nombre' => 'required|string|max:255'
        ]);

        $patio = new Patio($datos);
        $patio->save();

        return response()->json([
            'data' => $patio,
            'success' => 1
        ], 201);
    }

    // PUT/PATCH /api/patios/1
    // 'sometimes' permite enviar solo los campos que se quieran cambiar (PATCH).
    public function update(Request $request, string $id)
    {
        $patio = Patio::findOrFail($id);

        $datos = $request->validate([
            'desguace_id' => 'sometimes|required|integer|exists:desguaces,id',
            'codigo' => 'sometimes|required|string|max:50',
            'nombre' => 'sometimes|required|string|max:255'
        ]);

        $patio->fill($datos);
        $patio->save();

        return response()->json([
            'data' => $patio,
            'success' => 1
        ], 200);
    }

    // DELETE /api/patios/1
    public function destroy(string $id)
    {
        $patio = Patio::findOrFail($id);
        $patio->delete();

        return response()->json([
            'data' => 'Patio eliminado',
            'success' => 1
        ], 200);
    }
}
