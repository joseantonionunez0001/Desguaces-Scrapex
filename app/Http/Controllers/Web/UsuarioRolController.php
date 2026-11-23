<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\AsignarRolRequest;
use App\Models\User;
use App\Support\Acceso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Asigna, cambia o retira el rol de un usuario con los métodos oficiales de Spatie
 * (syncRoles / removeRole). Las reglas se comprueban en el servidor, no solo en los botones:
 *
 * - solo cuentas de la sede del administrador (las demás responden 404);
 * - nunca cuentas de administración ni la propia;
 * - solo los roles de gerente y operario.
 */
class UsuarioRolController extends Controller
{
    /**
     * PUT /admin/usuarios/{user}/rol  (asigna o cambia el rol)
     */
    public function update(AsignarRolRequest $request, User $user): RedirectResponse
    {
        $user = $this->gestionable($request, $user);
        $rol = $request->validated('rol');

        // syncRoles deja al usuario con exactamente ese rol.
        $user->syncRoles([$rol]);

        return redirect()
            ->route('admin.usuarios.show', $user)
            ->with('success', "Rol «{$rol}» asignado a {$user->name}.");
    }

    /**
     * DELETE /admin/usuarios/{user}/rol  (retira el rol actual)
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $user = $this->gestionable($request, $user);

        if ($user->getRoleNames()->isEmpty()) {
            return back()->with('warning', 'El usuario no tiene ningún rol que retirar.');
        }

        foreach ($user->getRoleNames() as $rol) {
            $user->removeRole($rol);
        }

        return redirect()
            ->route('admin.usuarios.show', $user)
            ->with('success', "Se ha retirado el rol a {$user->name}. Su cuenta queda pendiente de autorización.");
    }

    private function gestionable(Request $request, User $user): User
    {
        $actor = $request->user();

        abort_if($user->is($actor), 403, 'No puedes modificar tus propios privilegios.');

        $user = Acceso::usuariosVisibles($actor)->findOrFail($user->getKey());

        abort_unless(Acceso::puedeGestionarEquipo($actor, $user), 403, 'No puedes gestionar esta cuenta.');

        return $user;
    }
}
