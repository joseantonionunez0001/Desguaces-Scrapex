<?php

namespace App\Http\Controllers;

use App\Models\Movimiento;
use Illuminate\Http\Request;

class MovimientoApiController extends Controller
{
    // GET /api/movimientos
    public function index()
    {
        $movimientos = Movimiento::all();

        return response()->json([
            'data' => $movimientos,
            'success' => 1
        ], 200);
    }

    // GET /api/movimientos/1
    public function show(string $id)
    {
        $movimiento = Movimiento::findOrFail($id);

        return response()->json([
            'data' => $movimiento,
            'success' => 1
        ], 200);
    }

    // POST /api/movimientos
    public function store(Request $request)
    {
        $datos = $request->validate([
            'tipo_movimiento' => 'required|string|max:255',
            'fecha' => 'required|date',
            'cantidad' => 'required|integer'
        ]);

        $movimiento = new Movimiento();
        $movimiento->tipo_movimiento = $datos['tipo_movimiento'];
        $movimiento->fecha = $datos['fecha'];
        $movimiento->cantidad = $datos['cantidad'];
        $movimiento->save();

        return response()->json([
            'data' => $movimiento,
            'success' => 1
        ], 201);
    }

    // PUT/PATCH /api/movimientos/1
    // 'sometimes' permite enviar solo los campos que se quieran cambiar (PATCH).
    public function update(Request $request, string $id)
    {
        $movimiento = Movimiento::findOrFail($id);

        $datos = $request->validate([
            'tipo_movimiento' => 'sometimes|required|string|max:255',
            'fecha' => 'sometimes|required|date',
            'cantidad' => 'sometimes|required|integer'
        ]);

        $movimiento->fill($datos);
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
