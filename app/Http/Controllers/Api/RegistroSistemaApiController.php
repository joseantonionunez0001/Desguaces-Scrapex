<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RegistroSistema;
use Illuminate\Http\Request;

// Registro de auditoria: solo lectura y alta. No se modifica ni se borra.
class RegistroSistemaApiController extends Controller
{
    // GET /api/registro-sistema
    public function index()
    {
        $registros = RegistroSistema::with('usuario')->get();

        return response()->json([
            'data' => $registros,
            'success' => 1
        ], 200);
    }

    // GET /api/registro-sistema/1
    public function show(string $id)
    {
        $registro = RegistroSistema::with('usuario')->findOrFail($id);

        return response()->json([
            'data' => $registro,
            'success' => 1
        ], 200);
    }

    // POST /api/registro-sistema
    public function store(Request $request)
    {
        $datos = $request->validate([
            'fecha_hora' => 'required|date',
        ]);

        // El autor del registro es siempre el usuario autenticado, nunca un valor del cliente.
        $registro = new RegistroSistema();
        $registro->fecha_hora = $datos['fecha_hora'];
        $registro->id_usuario = $request->user()->id;
        $registro->save();

        return response()->json([
            'data' => $registro,
            'success' => 1
        ], 201);
    }
}
