<?php

namespace App\Http\Controllers;

use App\Models\Pasillo;
use Illuminate\Http\Request;

class PasilloApiController extends Controller
{
    // GET /api/pasillos
    public function index()
    {
        $pasillos = Pasillo::all();

        return response()->json([
            'data' => $pasillos,
            'success' => 1
        ], 200);
    }

    // GET /api/pasillos/1
    public function show(string $id)
    {
        $pasillo = Pasillo::findOrFail($id);

        return response()->json([
            'data' => $pasillo,
            'success' => 1
        ], 200);
    }

    // POST /api/pasillos
    public function store(Request $request)
    {
        $datos = $request->validate([
            'numero_pasillo' => 'required|string|max:20'
        ]);

        $pasillo = new Pasillo();
        $pasillo->numero_pasillo = $datos['numero_pasillo'];
        $pasillo->save();

        return response()->json([
            'data' => $pasillo,
            'success' => 1
        ], 201);
    }

    // PUT/PATCH /api/pasillos/1
    // 'sometimes' permite enviar solo los campos que se quieran cambiar (PATCH).
    public function update(Request $request, string $id)
    {
        $pasillo = Pasillo::findOrFail($id);

        $datos = $request->validate([
            'numero_pasillo' => 'sometimes|required|string|max:20'
        ]);

        $pasillo->fill($datos);
        $pasillo->save();

        return response()->json([
            'data' => $pasillo,
            'success' => 1
        ], 200);
    }

    // DELETE /api/pasillos/1
    public function destroy(string $id)
    {
        $pasillo = Pasillo::findOrFail($id);
        $pasillo->delete();

        return response()->json([
            'data' => 'Pasillo eliminado',
            'success' => 1
        ], 200);
    }
}
