<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    // GET /register
    public function create(): View
    {
        return view('auth.register');
    }

    // POST /register
    public function store(Request $request): RedirectResponse
    {
        // Solo se piden nombre, correo y contraseña. El rol NUNCA se acepta desde el formulario.
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // El cast 'hashed' del modelo cifra la contraseña.
        $usuario = User::create([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'password' => $datos['password'],
        ]);

        // Sin roles asignados: la cuenta queda pendiente de autorización por un administrador.
        Auth::login($usuario);

        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
