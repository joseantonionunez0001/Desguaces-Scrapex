<?php

namespace App\Http\Controllers;

use App\Models\Desguace;
use Illuminate\Http\Request;

class DesguaceApiController extends Controller
{
    // GET /api/desguaces
    public function index()
    {
        $desguaces = Desguace::all();

        return response()->json([
            'data' => $desguaces,
            'success' => 1
        ], 200);
    }

    // GET /api/desguaces/1
    public function show(string $id)
    {
        $desguace = Desguace::findOrFail($id);

        return response()->json([
            'data' => $desguace,
            'success' => 1
        ], 200);
    }

    // POST /api/desguaces
    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'required|string|max:255',
            'numero_telefono' => 'required|string|max:20',
            'ubicacion' => 'required|string|max:255'
        ]);

        $desguace = new Desguace();
        $desguace->nombre = $datos['nombre'];
        $desguace->direccion = $datos['direccion'];
        $desguace->numero_telefono = $datos['numero_telefono'];
        $desguace->ubicacion = $datos['ubicacion'];
        $desguace->save();

        return response()->json([
            'data' => $desguace,
            'success' => 1
        ], 201);
    }

    // PUT/PATCH /api/desguaces/1
    // 'sometimes' permite enviar solo los campos que se quieran cambiar (PATCH).
    public function update(Request $request, string $id)
    {
        $desguace = Desguace::findOrFail($id);

        $datos = $request->validate([
            'nombre' => 'sometimes|required|string|max:255',
            'direccion' => 'sometimes|required|string|max:255',
            'numero_telefono' => 'sometimes|required|string|max:20',
            'ubicacion' => 'sometimes|required|string|max:255'
        ]);

        $desguace->fill($datos);
        $desguace->save();

        return response()->json([
            'data' => $desguace,
            'success' => 1
        ], 200);
    }

    // DELETE /api/desguaces/1
    public function destroy(string $id)
    {
        $desguace = Desguace::findOrFail($id);
        $desguace->delete();

        return response()->json([
            'data' => 'Desguace eliminado',
            'success' => 1
        ], 200);
    }
}
