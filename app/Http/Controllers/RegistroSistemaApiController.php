<?php

namespace App\Http\Controllers;

use App\Models\RegistroSistema;
use Illuminate\Http\Request;

class RegistroSistemaApiController extends Controller
{
    // GET /api/registros-sistema
    public function index()
    {
        $registros = RegistroSistema::all();

        return response()->json([
            'data' => $registros,
            'success' => 1
        ], 200);
    }

    // GET /api/registros-sistema/1
    public function show(string $id)
    {
        $registro = RegistroSistema::findOrFail($id);

        return response()->json([
            'data' => $registro,
            'success' => 1
        ], 200);
    }

    // POST /api/registros-sistema
    public function store(Request $request)
    {
        $datos = $request->validate([
            'fecha_hora' => 'required|date'
        ]);

        $registro = new RegistroSistema();
        $registro->fecha_hora = $datos['fecha_hora'];
        $registro->save();

        return response()->json([
            'data' => $registro,
            'success' => 1
        ], 201);
    }

    // PUT/PATCH /api/registros-sistema/1
    public function update(Request $request, string $id)
    {
        $registro = RegistroSistema::findOrFail($id);

        $datos = $request->validate([
            'fecha_hora' => 'sometimes|required|date'
        ]);

        $registro->fill($datos);
        $registro->save();

        return response()->json([
            'data' => $registro,
            'success' => 1
        ], 200);
    }

    // DELETE /api/registros-sistema/1
    public function destroy(string $id)
    {
        $registro = RegistroSistema::findOrFail($id);
        $registro->delete();

        return response()->json([
            'data' => 'Registro eliminado',
            'success' => 1
        ], 200);
    }
}
