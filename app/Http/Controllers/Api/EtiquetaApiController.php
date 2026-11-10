<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Etiqueta;
use Illuminate\Http\Request;

class EtiquetaApiController extends Controller
{
    // GET /api/etiquetas
    public function index()
    {
        $etiquetas = Etiqueta::with('estanteria')->get();

        return response()->json([
            'data' => $etiquetas,
            'success' => 1
        ], 200);
    }

    // GET /api/etiquetas/1
    public function show(string $id)
    {
        $etiqueta = Etiqueta::with('estanteria')->findOrFail($id);

        return response()->json([
            'data' => $etiqueta,
            'success' => 1
        ], 200);
    }

    // POST /api/etiquetas
    public function store(Request $request)
    {
        $datos = $request->validate([
            'id_estanteria' => 'required|exists:estanterias,id_estanteria',
            'numero_etiqueta' => 'required|string|max:50',
        ]);

        $etiqueta = new Etiqueta();
        $etiqueta->id_estanteria = $datos['id_estanteria'];
        $etiqueta->numero_etiqueta = $datos['numero_etiqueta'];
        $etiqueta->save();

        return response()->json([
            'data' => $etiqueta,
            'success' => 1
        ], 201);
    }

    // PUT /api/etiquetas/1
    public function update(Request $request, string $id)
    {
        $etiqueta = Etiqueta::findOrFail($id);

        $datos = $request->validate([
            'id_estanteria' => 'required|exists:estanterias,id_estanteria',
            'numero_etiqueta' => 'required|string|max:50',
        ]);

        $etiqueta->id_estanteria = $datos['id_estanteria'];
        $etiqueta->numero_etiqueta = $datos['numero_etiqueta'];
        $etiqueta->save();

        return response()->json([
            'data' => $etiqueta,
            'success' => 1
        ], 200);
    }

    // DELETE /api/etiquetas/1
    public function destroy(string $id)
    {
        $etiqueta = Etiqueta::findOrFail($id);
        $etiqueta->delete();

        return response()->json([
            'data' => 'Etiqueta eliminada',
            'success' => 1
        ], 200);
    }
}
