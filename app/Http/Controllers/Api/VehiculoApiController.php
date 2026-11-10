<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehiculo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VehiculoApiController extends Controller
{
    // GET /api/vehiculos
    public function index()
    {
        $vehiculos = Vehiculo::with('desguace')->get();

        return response()->json([
            'data' => $vehiculos,
            'success' => 1
        ], 200);
    }

    // GET /api/vehiculos/1
    public function show(string $id)
    {
        $vehiculo = Vehiculo::with('desguace')->findOrFail($id);

        return response()->json([
            'data' => $vehiculo,
            'success' => 1
        ], 200);
    }

    // POST /api/vehiculos
    public function store(Request $request)
    {
        $datos = $request->validate([
            'id_desguace' => 'required|exists:desguaces,id_desguace',
            'matricula' => 'required|string|max:20|unique:vehiculos,matricula',
            'marca' => 'required|string|max:255',
            'modelo' => 'required|string|max:255',
            'fecha_llegada' => 'required|date',
            'numero_bastidor' => 'required|string|size:17|unique:vehiculos,numero_bastidor',
            'estado_tratamiento' => 'required|string|max:100',
        ]);

        $vehiculo = new Vehiculo();
        $vehiculo->id_desguace = $datos['id_desguace'];
        $vehiculo->matricula = $datos['matricula'];
        $vehiculo->marca = $datos['marca'];
        $vehiculo->modelo = $datos['modelo'];
        $vehiculo->fecha_llegada = $datos['fecha_llegada'];
        $vehiculo->numero_bastidor = $datos['numero_bastidor'];
        $vehiculo->estado_tratamiento = $datos['estado_tratamiento'];
        $vehiculo->save();

        return response()->json([
            'data' => $vehiculo,
            'success' => 1
        ], 201);
    }

    // PUT /api/vehiculos/1
    public function update(Request $request, string $id)
    {
        $vehiculo = Vehiculo::findOrFail($id);

        $datos = $request->validate([
            'id_desguace' => 'required|exists:desguaces,id_desguace',
            'matricula' => [
                'required',
                'string',
                'max:20',
                Rule::unique('vehiculos', 'matricula')->ignore($vehiculo->getKey(), $vehiculo->getKeyName()),
            ],
            'marca' => 'required|string|max:255',
            'modelo' => 'required|string|max:255',
            'fecha_llegada' => 'required|date',
            'numero_bastidor' => [
                'required',
                'string',
                'size:17',
                Rule::unique('vehiculos', 'numero_bastidor')->ignore($vehiculo->getKey(), $vehiculo->getKeyName()),
            ],
            'estado_tratamiento' => 'required|string|max:100',
        ]);

        $vehiculo->id_desguace = $datos['id_desguace'];
        $vehiculo->matricula = $datos['matricula'];
        $vehiculo->marca = $datos['marca'];
        $vehiculo->modelo = $datos['modelo'];
        $vehiculo->fecha_llegada = $datos['fecha_llegada'];
        $vehiculo->numero_bastidor = $datos['numero_bastidor'];
        $vehiculo->estado_tratamiento = $datos['estado_tratamiento'];
        $vehiculo->save();

        return response()->json([
            'data' => $vehiculo,
            'success' => 1
        ], 200);
    }

    // DELETE /api/vehiculos/1
    public function destroy(string $id)
    {
        $vehiculo = Vehiculo::findOrFail($id);
        $vehiculo->delete();

        return response()->json([
            'data' => 'Vehiculo eliminado',
            'success' => 1
        ], 200);
    }
}
