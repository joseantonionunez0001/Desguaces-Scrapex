<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TipoPieza;
use Illuminate\Http\Request;

class TipoPiezaApiController extends Controller
{
    // GET /api/tipo-piezas
    public function index()
    {
        $tiposPieza = TipoPieza::with('categoriaRelacionada')->get();

        return response()->json([
            'data' => $tiposPieza,
            'success' => 1
        ], 200);
    }

    // GET /api/tipo-piezas/1
    public function show(string $id)
    {
        $tipoPieza = TipoPieza::with('categoriaRelacionada')->findOrFail($id);

        return response()->json([
            'data' => $tipoPieza,
            'success' => 1
        ], 200);
    }

    // POST /api/tipo-piezas
    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'fabricante' => 'required|string|max:255',
            'categoria' => 'required|string|max:255',
            'id_categoria' => 'required|exists:categorias,categoria_id',
        ]);

        $tipoPieza = new TipoPieza();
        $tipoPieza->nombre = $datos['nombre'];
        $tipoPieza->fabricante = $datos['fabricante'];
        $tipoPieza->categoria = $datos['categoria'];
        $tipoPieza->id_categoria = $datos['id_categoria'];
        $tipoPieza->save();

        return response()->json([
            'data' => $tipoPieza,
            'success' => 1
        ], 201);
    }

    // PUT /api/tipo-piezas/1
    public function update(Request $request, string $id)
    {
        $tipoPieza = TipoPieza::findOrFail($id);

        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'fabricante' => 'required|string|max:255',
            'categoria' => 'required|string|max:255',
            'id_categoria' => 'required|exists:categorias,categoria_id',
        ]);

        $tipoPieza->nombre = $datos['nombre'];
        $tipoPieza->fabricante = $datos['fabricante'];
        $tipoPieza->categoria = $datos['categoria'];
        $tipoPieza->id_categoria = $datos['id_categoria'];
        $tipoPieza->save();

        return response()->json([
            'data' => $tipoPieza,
            'success' => 1
        ], 200);
    }

    // DELETE /api/tipo-piezas/1
    public function destroy(string $id)
    {
        $tipoPieza = TipoPieza::findOrFail($id);
        $tipoPieza->delete();

        return response()->json([
            'data' => 'TipoPieza eliminado',
            'success' => 1
        ], 200);
    }
}
