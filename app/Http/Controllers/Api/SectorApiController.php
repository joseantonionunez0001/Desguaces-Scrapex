<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sector;
use Illuminate\Http\Request;

class SectorApiController extends Controller
{
    // GET /api/sectores
    public function index()
    {
        $sectores = Sector::with('patio')->get();

        return response()->json([
            'data' => $sectores,
            'success' => 1
        ], 200);
    }

    // GET /api/sectores/1
    public function show(string $id)
    {
        $sector = Sector::with('patio')->findOrFail($id);

        return response()->json([
            'data' => $sector,
            'success' => 1
        ], 200);
    }

    // POST /api/sectores
    public function store(Request $request)
    {
        $datos = $request->validate([
            'id_patio' => 'required|exists:patios,id_patio',
            'codigo' => 'required|string|max:50',
            'tipo_zona' => 'required|string|max:100',
        ]);

        $sector = new Sector();
        $sector->id_patio = $datos['id_patio'];
        $sector->codigo = $datos['codigo'];
        $sector->tipo_zona = $datos['tipo_zona'];
        $sector->save();

        return response()->json([
            'data' => $sector,
            'success' => 1
        ], 201);
    }

    // PUT /api/sectores/1
    public function update(Request $request, string $id)
    {
        $sector = Sector::findOrFail($id);

        $datos = $request->validate([
            'id_patio' => 'required|exists:patios,id_patio',
            'codigo' => 'required|string|max:50',
            'tipo_zona' => 'required|string|max:100',
        ]);

        $sector->id_patio = $datos['id_patio'];
        $sector->codigo = $datos['codigo'];
        $sector->tipo_zona = $datos['tipo_zona'];
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
