<?php

namespace App\Http\Controllers;

use App\Models\Etiqueta;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EtiquetaApiController extends Controller
{
    // GET /api/etiquetas
    public function index()
    {
        $etiquetas = Etiqueta::all();

        return response()->json([
            'data' => $etiquetas,
            'success' => 1
        ], 200);
    }

    // GET /api/etiquetas/1
    public function show(string $id)
    {
        $etiqueta = Etiqueta::findOrFail($id);

        return response()->json([
            'data' => $etiqueta,
            'success' => 1
        ], 200);
    }

    // POST /api/etiquetas
    public function store(Request $request)
    {
        $datos = $request->validate([
            'estanteria_id' => 'required|integer|exists:estanterias,id',
            'numero_etiqueta' => [
                'required',
                'integer',
                'min:1',
                // Mismo número no repetido dentro de la misma estantería
                Rule::unique('etiquetas', 'numero_etiqueta')
                    ->where('estanteria_id', $request->input('estanteria_id'))
            ]
        ]);

        $etiqueta = new Etiqueta();
        $etiqueta->estanteria_id = $datos['estanteria_id'];
        $etiqueta->numero_etiqueta = $datos['numero_etiqueta'];
        $etiqueta->save();

        return response()->json([
            'data' => $etiqueta,
            'success' => 1
        ], 201);
    }

    // PUT/PATCH /api/etiquetas/1
    public function update(Request $request, string $id)
    {
        $etiqueta = Etiqueta::findOrFail($id);

        $estanteriaId = $request->input('estanteria_id', $etiqueta->estanteria_id);

        $datos = $request->validate([
            'estanteria_id' => 'sometimes|required|integer|exists:estanterias,id',
            'numero_etiqueta' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
                Rule::unique('etiquetas', 'numero_etiqueta')
                    ->ignore($etiqueta->id)
                    ->where('estanteria_id', $estanteriaId)
            ]
        ]);

        $etiqueta->fill($datos);
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
