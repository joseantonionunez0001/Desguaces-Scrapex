<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patio;
use Illuminate\Http\Request;

class PatioApiController extends Controller
{
    // GET /api/patios
    public function index()
    {
        $patios = Patio::with('desguace')->get();

        return response()->json([
            'data' => $patios,
            'success' => 1
        ], 200);
    }

    // GET /api/patios/1
    public function show(string $id)
    {
        $patio = Patio::with('desguace')->findOrFail($id);

        return response()->json([
            'data' => $patio,
            'success' => 1
        ], 200);
    }

    // POST /api/patios
    public function store(Request $request)
    {
        $datos = $request->validate([
            'id_desguace' => 'required|exists:desguaces,id_desguace',
            'codigo' => 'required|string|max:50',
            'nombre' => 'required|string|max:255',
        ]);

        $patio = new Patio();
        $patio->id_desguace = $datos['id_desguace'];
        $patio->codigo = $datos['codigo'];
        $patio->nombre = $datos['nombre'];
        $patio->save();

        return response()->json([
            'data' => $patio,
            'success' => 1
        ], 201);
    }

    // PUT /api/patios/1
    public function update(Request $request, string $id)
    {
        $patio = Patio::findOrFail($id);

        $datos = $request->validate([
            'id_desguace' => 'required|exists:desguaces,id_desguace',
            'codigo' => 'required|string|max:50',
            'nombre' => 'required|string|max:255',
        ]);

        $patio->id_desguace = $datos['id_desguace'];
        $patio->codigo = $datos['codigo'];
        $patio->nombre = $datos['nombre'];
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
