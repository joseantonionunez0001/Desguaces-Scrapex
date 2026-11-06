<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriaApiController extends Controller
{
    // GET /api/categorias
    public function index()
    {
        $categorias = Categoria::all();

        return response()->json([
            'data' => $categorias,
            'success' => 1
        ], 200);
    }

    // GET /api/categorias/1
    public function show(string $id)
    {
        $categoria = Categoria::findOrFail($id);

        return response()->json([
            'data' => $categoria,
            'success' => 1
        ], 200);
    }

    // POST /api/categorias
    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:255'
        ]);

        $categoria = new Categoria();
        $categoria->nombre = $datos['nombre'];
        $categoria->save();

        return response()->json([
            'data' => $categoria,
            'success' => 1
        ], 201);
    }

    // PUT/PATCH /api/categorias/1
    // 'sometimes' permite enviar solo los campos que se quieran cambiar (PATCH).
    public function update(Request $request, string $id)
    {
        $categoria = Categoria::findOrFail($id);

        $datos = $request->validate([
            'nombre' => 'sometimes|required|string|max:255'
        ]);

        $categoria->fill($datos);
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
