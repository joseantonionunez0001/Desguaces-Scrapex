<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fila;
use Illuminate\Http\Request;

class FilaApiController extends Controller
{
    // GET /api/filas
    public function index()
    {
        $filas = Fila::with('sector')->get();

        return response()->json([
            'data' => $filas,
            'success' => 1
        ], 200);
    }

    // GET /api/filas/1
    public function show(string $id)
    {
        $fila = Fila::with('sector')->findOrFail($id);

        return response()->json([
            'data' => $fila,
            'success' => 1
        ], 200);
    }

    // POST /api/filas
    public function store(Request $request)
    {
        $datos = $request->validate([
            'id_sector' => 'required|exists:sectores,id_sector',
            'numero_fila' => 'required|string|max:50',
        ]);

        $fila = new Fila();
        $fila->id_sector = $datos['id_sector'];
        $fila->numero_fila = $datos['numero_fila'];
        $fila->save();

        return response()->json([
            'data' => $fila,
            'success' => 1
        ], 201);
    }

    // PUT /api/filas/1
    public function update(Request $request, string $id)
    {
        $fila = Fila::findOrFail($id);

        $datos = $request->validate([
            'id_sector' => 'required|exists:sectores,id_sector',
            'numero_fila' => 'required|string|max:50',
        ]);

        $fila->id_sector = $datos['id_sector'];
        $fila->numero_fila = $datos['numero_fila'];
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
