<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Desguace;
use App\Models\Modulo;
use App\Models\Vehiculo;
use App\Rules\ExisteEnMiSede;
use Illuminate\Http\Request;

class CategoriaApiController extends Controller
{
    // GET /api/categorias
    public function index()
    {
        $categorias = Categoria::with(['desguace', 'modulo', 'vehiculo'])->get();

        return response()->json([
            'data' => $categorias,
            'success' => 1,
        ], 200);
    }

    // GET /api/categorias/1
    public function show(string $id)
    {
        $categoria = Categoria::with(['desguace', 'modulo', 'vehiculo'])->findOrFail($id);

        return response()->json([
            'data' => $categoria,
            'success' => 1,
        ], 200);
    }

    // POST /api/categorias
    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'id_desguace' => ['required', new ExisteEnMiSede(Desguace::class)],
            'id_modulo' => ['required', new ExisteEnMiSede(Modulo::class)],
            'id_vehiculo' => ['nullable', new ExisteEnMiSede(Vehiculo::class)],
        ]);

        $categoria = new Categoria;
        $categoria->nombre = $datos['nombre'];
        $categoria->id_desguace = $datos['id_desguace'];
        $categoria->id_modulo = $datos['id_modulo'];
        $categoria->id_vehiculo = $datos['id_vehiculo'] ?? null;
        $categoria->save();

        return response()->json([
            'data' => $categoria,
            'success' => 1,
        ], 201);
    }

    // PUT /api/categorias/1
    public function update(Request $request, string $id)
    {
        $categoria = Categoria::findOrFail($id);

        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'id_desguace' => ['required', new ExisteEnMiSede(Desguace::class)],
            'id_modulo' => ['required', new ExisteEnMiSede(Modulo::class)],
            'id_vehiculo' => ['nullable', new ExisteEnMiSede(Vehiculo::class)],
        ]);

        $categoria->nombre = $datos['nombre'];
        $categoria->id_desguace = $datos['id_desguace'];
        $categoria->id_modulo = $datos['id_modulo'];
        $categoria->id_vehiculo = $datos['id_vehiculo'] ?? null;
        $categoria->save();

        return response()->json([
            'data' => $categoria,
            'success' => 1,
        ], 200);
    }

    // DELETE /api/categorias/1
    public function destroy(string $id)
    {
        $categoria = Categoria::findOrFail($id);
        $categoria->delete();

        return response()->json([
            'data' => 'Categoria eliminada',
            'success' => 1,
        ], 200);
    }
}
