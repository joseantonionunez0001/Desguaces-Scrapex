<?php

namespace App\Http\Controllers;

use App\Models\TipoPieza;
use Illuminate\Http\Request;

class TipoPiezaApiController extends Controller
{
    // GET /api/tipo_piezas
    public function index()
    {
        $tipoPiezas = TipoPieza::all();

        return response()->json([
            'data' => $tipoPiezas,
            'success' => 1
        ], 200);
    }

    // GET /api/tipo_piezas/1
    public function show(string $id)
    {
        $tipoPieza = TipoPieza::findOrFail($id);

        return response()->json([
            'data' => $tipoPieza,
            'success' => 1
        ], 200);
    }

    // POST /api/tipo_piezas
    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'categoria' => 'required|string|max:255',
            'fabricante' => 'required|string|max:255'
        ]);

        $tipoPieza = new TipoPieza();
        $tipoPieza->nombre = $datos['nombre'];
        $tipoPieza->categoria = $datos['categoria'];
        $tipoPieza->fabricante = $datos['fabricante'];
        $tipoPieza->save();

        return response()->json([
            'data' => $tipoPieza,
            'success' => 1
        ], 201);
    }

    // PUT/PATCH /api/tipo_piezas/1
    // 'sometimes' permite enviar solo los campos que se quieran cambiar (PATCH).
    public function update(Request $request, string $id)
    {
        $tipoPieza = TipoPieza::findOrFail($id);

        $datos = $request->validate([
            'nombre' => 'sometimes|required|string|max:255',
            'categoria' => 'sometimes|required|string|max:255',
            'fabricante' => 'sometimes|required|string|max:255'
        ]);

        $tipoPieza->fill($datos);
        $tipoPieza->save();

        return response()->json([
            'data' => $tipoPieza,
            'success' => 1
        ], 200);
    }

    // DELETE /api/tipo_piezas/1
    public function destroy(string $id)
    {
        $tipoPieza = TipoPieza::findOrFail($id);
        $tipoPieza->delete();

        return response()->json([
            'data' => 'Tipo de pieza eliminado',
            'success' => 1
        ], 200);
    }
}
