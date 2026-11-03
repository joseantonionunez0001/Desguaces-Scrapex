<?php

namespace App\Http\Controllers;

use App\Models\Fila;
use Illuminate\Http\Request;

class FilaApiController extends Controller
{
    // GET /api/filas
    public function index()
    {
        $filas = Fila::all();

        return response()->json([
            'data' => $filas,
            'success' => 1
        ], 200);
    }

    // GET /api/filas/1
    public function show(string $id)
    {
        $fila = Fila::findOrFail($id);

        return response()->json([
            'data' => $fila,
            'success' => 1
        ], 200);
    }

    // POST /api/filas
    public function store(Request $request)
    {
        $datos = $request->validate([
            'sector_id' => 'required|integer|min:1',
            'numero_fila' => 'required|string|max:20'
        ]);

        $fila = new Fila();
        $fila->sector_id = $datos['sector_id'];
        $fila->numero_fila = $datos['numero_fila'];
        $fila->save();

        return response()->json([
            'data' => $fila,
            'success' => 1
        ], 201);
    }

    // PUT/PATCH /api/filas/1
    // 'sometimes' permite enviar solo los campos que se quieran cambiar (PATCH).
    public function update(Request $request, string $id)
    {
        $fila = Fila::findOrFail($id);

        $datos = $request->validate([
            'sector_id' => 'sometimes|required|integer|min:1',
            'numero_fila' => 'sometimes|required|string|max:20'
        ]);

        $fila->fill($datos);
        $fila->save();

        return response()->json([
            'data' => $fila,
            'success' => 1
        ], 200);
    }

    // DELETE /api/filas/1
    public function destroy(string $id)
    {
        $fila = Fila::findOrFail($id);
        $fila->delete();

        return response()->json([
            'data' => 'Fila eliminada',
            'success' => 1
        ], 200);
    }
}
