<?php

namespace App\Http\Controllers;

use App\Models\Pieza;
use Illuminate\Http\Request;

class PiezaApiController extends Controller
{
    public function index()
    {
        $piezas = Pieza::with('vehiculos')->get();

        return response()->json([
            'data' => $piezas,
            'success' => 1
        ], 200);
    }

    public function show(string $id)
    {
        $pieza = Pieza::with('vehiculos')->findOrFail($id);

        return response()->json([
            'data' => $pieza,
            'success' => 1
        ], 200);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'referencia' => 'required|string|max:255|unique:piezas,referencia',
            'precio' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0'
        ]);

        $pieza = new Pieza();
        $pieza->nombre = $datos['nombre'];
        $pieza->referencia = $datos['referencia'];
        $pieza->precio = $datos['precio'];
        $pieza->stock = $datos['stock'];
        $pieza->save();

        return response()->json([
            'data' => $pieza,
            'success' => 1
        ], 201);
    }

    public function update(Request $request, string $id)
    {
        $pieza = Pieza::findOrFail($id);

        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'referencia' => 'required|string|max:255|unique:piezas,referencia,'.$id,
            'precio' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0'
        ]);

        $pieza->nombre = $datos['nombre'];
        $pieza->referencia = $datos['referencia'];
        $pieza->precio = $datos['precio'];
        $pieza->stock = $datos['stock'];
        $pieza->save();

        return response()->json([
            'data' => $pieza,
            'success' => 1
        ], 200);
    }

    public function destroy(string $id)
    {
        $pieza = Pieza::findOrFail($id);
        $pieza->delete();

        return response()->json([
            'data' => 'Pieza eliminada',
            'success' => 1
        ], 200);
    }
}