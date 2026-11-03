<?php

namespace App\Http\Controllers;

use App\Models\Vehiculo;
use Illuminate\Http\Request;

class VehiculoApiController extends Controller
{
    // GET /api/vehiculos
    public function index()
    {
        $vehiculos = Vehiculo::all();

        return response()->json([
            'data' => $vehiculos,
            'success' => 1
        ], 200);
    }

    // GET /api/vehiculos/1
    public function show(string $id)
    {
        $vehiculo = Vehiculo::findOrFail($id);

        return response()->json([
            'data' => $vehiculo,
            'success' => 1
        ], 200);
    }

    // POST /api/vehiculos
    public function store(Request $request)
    {
        $datos = $request->validate([
            'matricula' => 'required|string|max:255',
            'marca' => 'required|string|max:255',
            'modelo' => 'required|string|max:255',
            'fecha_llegada' => 'required|date',
            'numero_bastidor' => 'required|string|max:255',
            'estado_tratamiento' => 'required|string|max:255'
        ]);

        $vehiculo = new Vehiculo();
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

    // PUT/PATCH /api/vehiculos/1
    // 'sometimes' permite enviar solo los campos que se quieran cambiar (PATCH).
    public function update(Request $request, string $id)
    {
        $vehiculo = Vehiculo::findOrFail($id);

        $datos = $request->validate([
            'matricula' => 'sometimes|required|string|max:255',
            'marca' => 'sometimes|required|string|max:255',
            'modelo' => 'sometimes|required|string|max:255',
            'fecha_llegada' => 'sometimes|required|date',
            'numero_bastidor' => 'sometimes|required|string|max:255',
            'estado_tratamiento' => 'sometimes|required|string|max:255'
        ]);

        $vehiculo->fill($datos);
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
