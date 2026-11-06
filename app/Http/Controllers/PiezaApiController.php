<?php

namespace App\Http\Controllers;

use App\Models\Pieza;
use Illuminate\Http\Request;

class PiezaApiController extends Controller
{
    // GET /api/piezas
    public function index()
    {
        $piezas = Pieza::all();

        return response()->json([
            'data' => $piezas,
            'success' => 1
        ], 200);
    }

    // GET /api/piezas/1
    public function show(string $id)
    {
        $pieza = Pieza::findOrFail($id);

        return response()->json([
            'data' => $pieza,
            'success' => 1
        ], 200);
    }

    // POST /api/piezas
    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'precio' => 'required|numeric|min:0',
            'estado' => 'required|string|max:255',
            'stock' => 'required|integer|min:0',
            'stock_minimo' => 'required|integer|min:0'
        ]);

        $pieza = new Pieza();
        $pieza->nombre = $datos['nombre'];
        $pieza->precio = $datos['precio'];
        $pieza->estado = $datos['estado'];
        $pieza->stock = $datos['stock'];
        $pieza->stock_minimo = $datos['stock_minimo'];
        $pieza->save();

        return response()->json([
            'data' => $pieza,
            'success' => 1
        ], 201);
    }

    // PUT/PATCH /api/piezas/1
    // 'sometimes' permite enviar solo los campos que se quieran cambiar (PATCH).
    public function update(Request $request, string $id)
    {
        $pieza = Pieza::findOrFail($id);

        $datos = $request->validate([
            'nombre' => 'sometimes|required|string|max:255',
            'precio' => 'sometimes|required|numeric|min:0',
            'estado' => 'sometimes|required|string|max:255',
            'stock' => 'sometimes|required|integer|min:0',
            'stock_minimo' => 'sometimes|required|integer|min:0'
        ]);

        $pieza->fill($datos);
        $pieza->save();

        return response()->json([
            'data' => $pieza,
            'success' => 1
        ], 200);
    }

    // DELETE /api/piezas/1
    public function destroy(string $id)
    {
        $pieza = Pieza::findOrFail($id);
        $pieza->delete();

        return response()->json([
            'data' => 'Pieza eliminada',
            'success' => 1
        ], 200);
    }
}
