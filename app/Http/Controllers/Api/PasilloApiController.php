<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pasillo;
use Illuminate\Http\Request;

class PasilloApiController extends Controller
{
    // GET /api/pasillos
    public function index()
    {
        $pasillos = Pasillo::with('modulo')->get();

        return response()->json([
            'data' => $pasillos,
            'success' => 1
        ], 200);
    }

    // GET /api/pasillos/1
    public function show(string $id)
    {
        $pasillo = Pasillo::with('modulo')->findOrFail($id);

        return response()->json([
            'data' => $pasillo,
            'success' => 1
        ], 200);
    }

    // POST /api/pasillos
    public function store(Request $request)
    {
        $datos = $request->validate([
            'id_modulo' => 'required|exists:modulos,id_modulo',
            'numero_pasillo' => 'required|string|max:50',
        ]);

        $pasillo = new Pasillo();
        $pasillo->id_modulo = $datos['id_modulo'];
        $pasillo->numero_pasillo = $datos['numero_pasillo'];
        $pasillo->save();

        return response()->json([
            'data' => $pasillo,
            'success' => 1
        ], 201);
    }

    // PUT /api/pasillos/1
    public function update(Request $request, string $id)
    {
        $pasillo = Pasillo::findOrFail($id);

        $datos = $request->validate([
            'id_modulo' => 'required|exists:modulos,id_modulo',
            'numero_pasillo' => 'required|string|max:50',
        ]);

        $pasillo->id_modulo = $datos['id_modulo'];
        $pasillo->numero_pasillo = $datos['numero_pasillo'];
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
