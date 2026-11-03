<?php

namespace App\Http\Controllers;

use App\Models\Estanteria;
use Illuminate\Http\Request;

class EstanteriaApiController extends Controller
{
    // GET /api/estanterias
    public function index()
    {
        $estanterias = Estanteria::all();

        return response()->json([
            'data' => $estanterias,
            'success' => 1
        ], 200);
    }

    // GET /api/estanterias/1
    public function show(string $id)
    {
        $estanteria = Estanteria::findOrFail($id);

        return response()->json([
            'data' => $estanteria,
            'success' => 1
        ], 200);
    }

    // POST /api/estanterias
    public function store(Request $request)
    {
        $datos = $request->validate([
            'pasillo_id' => 'nullable|integer|min:1',
            'numero_estanteria' => 'required|integer|min:1'
        ]);

        $estanteria = new Estanteria();
        $estanteria->pasillo_id = $datos['pasillo_id'] ?? null;
        $estanteria->numero_estanteria = $datos['numero_estanteria'];
        $estanteria->save();

        return response()->json([
            'data' => $estanteria,
            'success' => 1
        ], 201);
    }

    // PUT/PATCH /api/estanterias/1
    public function update(Request $request, string $id)
    {
        $estanteria = Estanteria::findOrFail($id);

        $datos = $request->validate([
            'pasillo_id' => 'sometimes|nullable|integer|min:1',
            'numero_estanteria' => 'sometimes|required|integer|min:1'
        ]);

        $estanteria->fill($datos);
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

        // No se borra una estantería que todavía tiene etiquetas
        if ($estanteria->etiquetas()->exists()) {
            return response()->json([
                'data' => 'La estanteria tiene etiquetas asociadas y no se puede eliminar',
                'success' => 0
            ], 409);
        }

        $estanteria->delete();

        return response()->json([
            'data' => 'Estanteria eliminada',
            'success' => 1
        ], 200);
    }
}
