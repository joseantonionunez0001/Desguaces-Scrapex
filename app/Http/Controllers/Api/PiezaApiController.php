<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pieza;
use Illuminate\Http\Request;

class PiezaApiController extends Controller
{
    // GET /api/piezas
    public function index()
    {
        $piezas = Pieza::with('tipoPieza')->get();

        return response()->json([
            'data' => $piezas,
            'success' => 1
        ], 200);
    }

    // GET /api/piezas/1
    public function show(string $id)
    {
        $pieza = Pieza::with('tipoPieza')->findOrFail($id);

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
            'estado' => 'required|string|max:50',
            'stock' => 'required|integer|min:0',
            'stock_minimo' => 'required|integer|min:0',
            'id_usuario' => 'required|exists:users,id',
            'id_tipo_pieza' => 'required|exists:tipo_piezas,id_pieza',
        ]);

        $pieza = new Pieza();
        $pieza->nombre = $datos['nombre'];
        $pieza->precio = $datos['precio'];
        $pieza->estado = $datos['estado'];
        $pieza->stock = $datos['stock'];
        $pieza->stock_minimo = $datos['stock_minimo'];
        $pieza->id_usuario = $datos['id_usuario'];
        $pieza->id_tipo_pieza = $datos['id_tipo_pieza'];
        $pieza->save();

        return response()->json([
            'data' => $pieza,
            'success' => 1
        ], 201);
    }

    // PUT /api/piezas/1
    public function update(Request $request, string $id)
    {
        $pieza = Pieza::findOrFail($id);

        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'precio' => 'required|numeric|min:0',
            'estado' => 'required|string|max:50',
            'stock' => 'required|integer|min:0',
            'stock_minimo' => 'required|integer|min:0',
            'id_usuario' => 'required|exists:users,id',
            'id_tipo_pieza' => 'required|exists:tipo_piezas,id_pieza',
        ]);

        $pieza->nombre = $datos['nombre'];
        $pieza->precio = $datos['precio'];
        $pieza->estado = $datos['estado'];
        $pieza->stock = $datos['stock'];
        $pieza->stock_minimo = $datos['stock_minimo'];
        $pieza->id_usuario = $datos['id_usuario'];
        $pieza->id_tipo_pieza = $datos['id_tipo_pieza'];
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
