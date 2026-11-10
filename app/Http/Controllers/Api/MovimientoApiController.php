<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movimiento;
use Illuminate\Http\Request;

class MovimientoApiController extends Controller
{
    // GET /api/movimientos
    public function index()
    {
        $movimientos = Movimiento::with('categoria')->get();

        return response()->json([
            'data' => $movimientos,
            'success' => 1
        ], 200);
    }

    // GET /api/movimientos/1
    public function show(string $id)
    {
        $movimiento = Movimiento::with('categoria')->findOrFail($id);

        return response()->json([
            'data' => $movimiento,
            'success' => 1
        ], 200);
    }

    // POST /api/movimientos
    public function store(Request $request)
    {
        $datos = $request->validate([
            'tipo_movimiento' => 'required|string|max:50',
            'fecha' => 'required|date',
            'cantidad' => 'required|integer|min:1',
            'id_categoria' => 'required|exists:categorias,categoria_id',
        ]);

        $movimiento = new Movimiento();
        $movimiento->tipo_movimiento = $datos['tipo_movimiento'];
        $movimiento->fecha = $datos['fecha'];
        $movimiento->cantidad = $datos['cantidad'];
        $movimiento->id_categoria = $datos['id_categoria'];
        $movimiento->save();

        return response()->json([
            'data' => $movimiento,
            'success' => 1
        ], 201);
    }

    // PUT /api/movimientos/1
    public function update(Request $request, string $id)
    {
        $movimiento = Movimiento::findOrFail($id);

        $datos = $request->validate([
            'tipo_movimiento' => 'required|string|max:50',
            'fecha' => 'required|date',
            'cantidad' => 'required|integer|min:1',
            'id_categoria' => 'required|exists:categorias,categoria_id',
        ]);

        $movimiento->tipo_movimiento = $datos['tipo_movimiento'];
        $movimiento->fecha = $datos['fecha'];
        $movimiento->cantidad = $datos['cantidad'];
        $movimiento->id_categoria = $datos['id_categoria'];
        $movimiento->save();

        return response()->json([
            'data' => $movimiento,
            'success' => 1
        ], 200);
    }

    // DELETE /api/movimientos/1
    public function destroy(string $id)
    {
        $movimiento = Movimiento::findOrFail($id);
        $movimiento->delete();

        return response()->json([
            'data' => 'Movimiento eliminado',
            'success' => 1
        ], 200);
    }
}
