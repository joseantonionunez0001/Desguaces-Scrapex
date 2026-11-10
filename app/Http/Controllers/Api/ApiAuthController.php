<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ApiAuthController extends Controller
{
    // POST /api/login
    public function login(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $usuario = User::where('email', $datos['email'])->first();

        // Mismo mensaje tanto si el correo no existe como si la contraseña es incorrecta.
        if (! $usuario || ! Hash::check($datos['password'], $usuario->password)) {
            return response()->json([
                'data' => 'Credenciales no válidas',
                'success' => 0
            ], 401);
        }

        $token = $usuario->createToken('api-token')->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'usuario' => $usuario->load('roles'),
            ],
            'success' => 1
        ], 200);
    }

    // POST /api/logout
    public function logout(Request $request): JsonResponse
    {
        // Revoca solo el token usado en esta petición.
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'data' => 'Sesión cerrada',
            'success' => 1
        ], 200);
    }

    // GET /api/user
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user()->load('roles'),
            'success' => 1
        ], 200);
    }
}
