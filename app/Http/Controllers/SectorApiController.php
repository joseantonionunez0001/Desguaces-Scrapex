<?php

namespace App\Http\Controllers;

use App\Models\Sector;
use Illuminate\Http\Request;

class SectorApiController extends Controller
{
    // GET /api/sectores
    public function index()
    {
        $sectores = Sector::all();

        return response()->json([
            'data' => $sectores,
            'success' => 1
        ], 200);
    }

    // GET /api/sectores/1
    public function show(string $id)
    {
        $sector = Sector::findOrFail($id);

        return response()->json([
            'data' => $sector,
            'success' => 1
        ], 200);
    }

    // POST /api/sectores
    public function store(Request $request)
    {
        $datos = $request->validate([
            'patio_id' => 'required|integer|exists:patios,id',
            'codigo' => 'required|string|max:50',
            'tipo_zona' => 'required|string|max:255'
        ]);

        $sector = new Sector($datos);
        $sector->save();

        return response()->json([
            'data' => $sector,
            'success' => 1
        ], 201);
    }

    // PUT/PATCH /api/sectores/1
    // 'sometimes' permite enviar solo los campos que se quieran cambiar (PATCH).
    public function update(Request $request, string $id)
    {
        $sector = Sector::findOrFail($id);

        $datos = $request->validate([
            'patio_id' => 'sometimes|required|integer|exists:patios,id',
            'codigo' => 'sometimes|required|string|max:50',
            'tipo_zona' => 'sometimes|required|string|max:255'
        ]);

        $sector->fill($datos);
        $sector->save();

        return response()->json([
            'data' => $sector,
            'success' => 1
        ], 200);
    }

    // DELETE /api/sectores/1
    public function destroy(string $id)
    {
        $sector = Sector::findOrFail($id);
        $sector->delete();

        return response()->json([
            'data' => 'Sector eliminado',
            'success' => 1
        ], 200);
    }
}
