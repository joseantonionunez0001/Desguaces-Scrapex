<?php

namespace App\Http\Controllers;

use App\Models\Posicion;
use Illuminate\Http\Request;

class PosicionApiController extends Controller
{
    // GET /api/posiciones
    public function index()
    {
        $posiciones = Posicion::all();

        return response()->json([
            'data' => $posiciones,
            'success' => 1
        ], 200);
    }

    // GET /api/posiciones/1
    public function show(string $id)
    {
        $posicion = Posicion::findOrFail($id);

        return response()->json([
            'data' => $posicion,
            'success' => 1
        ], 200);
    }

    // POST /api/posiciones
    public function store(Request $request)
    {
        $datos = $request->validate([
            'fila_id' => 'required|integer|exists:filas,id',
            'numero_posicion' => 'required|string|max:20',
            'capacidad_maxima' => 'required|integer|min:1'
        ]);

        $posicion = new Posicion();
        $posicion->fila_id = $datos['fila_id'];
        $posicion->numero_posicion = $datos['numero_posicion'];
        $posicion->capacidad_maxima = $datos['capacidad_maxima'];
        $posicion->save();

        return response()->json([
            'data' => $posicion,
            'success' => 1
        ], 201);
    }

    // PUT/PATCH /api/posiciones/1
    // 'sometimes' permite enviar solo los campos que se quieran cambiar (PATCH).
    public function update(Request $request, string $id)
    {
        $posicion = Posicion::findOrFail($id);

        $datos = $request->validate([
            'fila_id' => 'sometimes|required|integer|exists:filas,id',
            'numero_posicion' => 'sometimes|required|string|max:20',
            'capacidad_maxima' => 'sometimes|required|integer|min:1'
        ]);

        $posicion->fill($datos);
        $posicion->save();

        return response()->json([
            'data' => $posicion,
            'success' => 1
        ], 200);
    }

    // DELETE /api/posiciones/1
    public function destroy(string $id)
    {
        $posicion = Posicion::findOrFail($id);
        $posicion->delete();

        return response()->json([
            'data' => 'Posicion eliminada',
            'success' => 1
        ], 200);
    }
}
