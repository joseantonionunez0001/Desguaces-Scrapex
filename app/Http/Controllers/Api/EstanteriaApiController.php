<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estanteria;
use Illuminate\Http\Request;

class EstanteriaApiController extends Controller
{
    // GET /api/estanterias
    public function index()
    {
        $estanterias = Estanteria::with('pasillo')->get();

        return response()->json([
            'data' => $estanterias,
            'success' => 1
        ], 200);
    }

    // GET /api/estanterias/1
    public function show(string $id)
    {
        $estanteria = Estanteria::with('pasillo')->findOrFail($id);

        return response()->json([
            'data' => $estanteria,
            'success' => 1
        ], 200);
    }

    // POST /api/estanterias
    public function store(Request $request)
    {
        $datos = $request->validate([
            'id_pasillo' => 'required|exists:pasillos,id_pasillo',
            'numero_estanteria' => 'required|string|max:50',
        ]);

        $estanteria = new Estanteria();
        $estanteria->id_pasillo = $datos['id_pasillo'];
        $estanteria->numero_estanteria = $datos['numero_estanteria'];
        $estanteria->save();

        return response()->json([
            'data' => $estanteria,
            'success' => 1
        ], 201);
    }

    // PUT /api/estanterias/1
    public function update(Request $request, string $id)
    {
        $estanteria = Estanteria::findOrFail($id);

        $datos = $request->validate([
            'id_pasillo' => 'required|exists:pasillos,id_pasillo',
            'numero_estanteria' => 'required|string|max:50',
        ]);

        $estanteria->id_pasillo = $datos['id_pasillo'];
        $estanteria->numero_estanteria = $datos['numero_estanteria'];
        $estanteria->save();

        return response()->json([
            'data' => $estanteria,
            'success' => 1
        ], 200);
    }

    // DELETE /api/estanterias/1
    public function destroy(string $id)
    {
        $estanteria = Estanteria::findOrFail($id);
        $estanteria->delete();

        return response()->json([
            'data' => 'Estanteria eliminada',
            'success' => 1
        ], 200);
    }
}
