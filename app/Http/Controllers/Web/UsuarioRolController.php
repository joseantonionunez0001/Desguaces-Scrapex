<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\AsignarRolRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Asigna, cambia o retira el rol de un usuario con los métodos oficiales de Spatie
 * (syncRoles / removeRole). Las reglas se comprueban en el servidor, no solo en los botones.
 */
class UsuarioRolController extends Controller
{
    // PUT /admin/usuarios/{user}/rol  (asigna o cambia el rol)
    public function update(AsignarRolRequest $request, User $user): RedirectResponse
    {
        $this->impedirModificarseASiMismo($request, $user);

        $rol = $request->validated('rol');

        if ($this->esUltimoAdministrador($user) && $rol !== RoleSeeder::ADMINISTRADOR) {
            return back()->with('error', 'No se puede cambiar el rol del último administrador del sistema.');
        }

        // syncRoles deja al usuario con exactamente ese rol.
        $user->syncRoles([$rol]);

        return redirect()
            ->route('admin.usuarios.show', $user)
            ->with('success', "Rol «{$rol}» asignado a {$user->name}.");
    }

    // DELETE /admin/usuarios/{user}/rol  (retira el rol actual)
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->impedirModificarseASiMismo($request, $user);

        if ($user->getRoleNames()->isEmpty()) {
            return back()->with('warning', 'El usuario no tiene ningún rol que retirar.');
        }

        if ($this->esUltimoAdministrador($user)) {
            return back()->with('error', 'No se puede retirar el rol del último administrador del sistema.');
        }

        foreach ($user->getRoleNames() as $rol) {
            $user->removeRole($rol);
        }

        return redirect()
            ->route('admin.usuarios.show', $user)
            ->with('success', "Se ha retirado el rol a {$user->name}. Su cuenta queda pendiente de autorización.");
    }

    private function impedirModificarseASiMismo(Request $request, User $user): void
    {
        abort_if($user->is($request->user()), 403, 'No puedes modificar tus propios privilegios.');
    }

    // Evita dejar el sistema sin ningún administrador.
    private function esUltimoAdministrador(User $user): bool
    {
        return $user->hasRole(RoleSeeder::ADMINISTRADOR)
            && User::role(RoleSeeder::ADMINISTRADOR)->count() <= 1;
    }
}
