<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Posicion;
use Illuminate\Http\Request;

class PosicionApiController extends Controller
{
    // GET /api/posiciones
    public function index()
    {
        $posiciones = Posicion::with('fila')->get();

        return response()->json([
            'data' => $posiciones,
            'success' => 1
        ], 200);
    }

    // GET /api/posiciones/1
    public function show(string $id)
    {
        $posicion = Posicion::with('fila')->findOrFail($id);

        return response()->json([
            'data' => $posicion,
            'success' => 1
        ], 200);
    }

    // POST /api/posiciones
    public function store(Request $request)
    {
        $datos = $request->validate([
            'id_fila' => 'required|exists:filas,id_fila',
            'numero_posicion' => 'required|string|max:50',
            'capacidad_maxima' => 'required|integer|min:1',
        ]);

        $posicion = new Posicion();
        $posicion->id_fila = $datos['id_fila'];
        $posicion->numero_posicion = $datos['numero_posicion'];
        $posicion->capacidad_maxima = $datos['capacidad_maxima'];
        $posicion->save();

        return response()->json([
            'data' => $posicion,
            'success' => 1
        ], 201);
    }

    // PUT /api/posiciones/1
    public function update(Request $request, string $id)
    {
        $posicion = Posicion::findOrFail($id);

        $datos = $request->validate([
            'id_fila' => 'required|exists:filas,id_fila',
            'numero_posicion' => 'required|string|max:50',
            'capacidad_maxima' => 'required|integer|min:1',
        ]);

        $posicion->id_fila = $datos['id_fila'];
        $posicion->numero_posicion = $datos['numero_posicion'];
        $posicion->capacidad_maxima = $datos['capacidad_maxima'];
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
