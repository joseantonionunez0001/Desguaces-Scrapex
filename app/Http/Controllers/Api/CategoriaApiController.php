<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriaApiController extends Controller
{
    // GET /api/categorias
    public function index()
    {
        $categorias = Categoria::with(['desguace', 'modulo', 'vehiculo'])->get();

        return response()->json([
            'data' => $categorias,
            'success' => 1
        ], 200);
    }

    // GET /api/categorias/1
    public function show(string $id)
    {
        $categoria = Categoria::with(['desguace', 'modulo', 'vehiculo'])->findOrFail($id);

        return response()->json([
            'data' => $categoria,
            'success' => 1
        ], 200);
    }

    // POST /api/categorias
    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'id_desguace' => 'required|exists:desguaces,id_desguace',
            'id_modulo' => 'required|exists:modulos,id_modulo',
            'id_vehiculo' => 'nullable|exists:vehiculos,id_vehiculo',
        ]);

        $categoria = new Categoria();
        $categoria->nombre = $datos['nombre'];
        $categoria->id_desguace = $datos['id_desguace'];
        $categoria->id_modulo = $datos['id_modulo'];
        $categoria->id_vehiculo = $datos['id_vehiculo'] ?? null;
        $categoria->save();

        return response()->json([
            'data' => $categoria,
            'success' => 1
        ], 201);
    }

    // PUT /api/categorias/1
    public function update(Request $request, string $id)
    {
        $categoria = Categoria::findOrFail($id);

        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'id_desguace' => 'required|exists:desguaces,id_desguace',
            'id_modulo' => 'required|exists:modulos,id_modulo',
            'id_vehiculo' => 'nullable|exists:vehiculos,id_vehiculo',
        ]);

        $categoria->nombre = $datos['nombre'];
        $categoria->id_desguace = $datos['id_desguace'];
        $categoria->id_modulo = $datos['id_modulo'];
        $categoria->id_vehiculo = $datos['id_vehiculo'] ?? null;
        $categoria->save();

        return response()->json([
            'data' => $categoria,
            'success' => 1
        ], 200);
    }

    // DELETE /api/categorias/1
    public function destroy(string $id)
    {
        $categoria = Categoria::findOrFail($id);
        $categoria->delete();

        return response()->json([
            'data' => 'Categoria eliminada',
            'success' => 1
        ], 200);
    }
}
