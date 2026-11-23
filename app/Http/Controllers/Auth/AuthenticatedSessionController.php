<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    // GET /login
    public function create(): View
    {
        return view('auth.login');
    }

    // POST /login
    public function store(Request $request): RedirectResponse
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credenciales, $request->boolean('remember'))) {
            // Mensaje genérico: no revela si el correo existe.
            return back()
                ->withErrors(['email' => 'Las credenciales no son correctas.'])
                ->onlyInput('email');
        }

        // Evita la fijación de sesión.
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    // POST /logout (solo POST, nunca GET)
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
