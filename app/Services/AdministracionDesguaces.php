<?php

namespace App\Services;

use App\Models\Desguace;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Operaciones de administración de sedes y cuentas. Las usan la web, la API y los comandos artisan,
 * de modo que las mismas reglas valen en todos los sitios.
 */
class AdministracionDesguaces
{
    /**
     * Designa (o sustituye) al administrador de una sede con una cuenta existente.
     * El administrador anterior pierde el rol y sus sesiones, pero conserva su cuenta.
     *
     * @throws DomainException
     */
    public function designarAdministrador(Desguace $desguace, User $nuevo): User
    {
        if ($nuevo->esAdministradorPlataforma()) {
            throw new DomainException('Una cuenta de administrador de plataforma no puede administrar un desguace.');
        }

        if ($nuevo->esAdministradorDesguace()
            && $nuevo->id_desguace !== null
            && (int) $nuevo->id_desguace !== (int) $desguace->id_desguace) {
            throw new DomainException('Esa cuenta ya administra otro desguace. Sustitúyela allí antes de reasignarla.');
        }

        return DB::transaction(function () use ($desguace, $nuevo): User {
            $anteriores = User::role(RoleSeeder::ADMIN_DESGUACE)
                ->delDesguace($desguace->id_desguace)
                ->whereKeyNot($nuevo->getKey())
                ->get();

            foreach ($anteriores as $anterior) {
                $anterior->removeRole(RoleSeeder::ADMIN_DESGUACE);
                $this->revocarAccesos($anterior);
            }

            $cambiaDeSede = (int) $nuevo->id_desguace !== (int) $desguace->id_desguace;
            $nuevo->id_desguace = $desguace->id_desguace;
            $nuevo->save();
            $nuevo->syncRoles([RoleSeeder::ADMIN_DESGUACE]);

            if ($cambiaDeSede) {
                $this->revocarAccesos($nuevo);
            }

            return $nuevo->refresh();
        });
    }

    /**
     * Da de alta una cuenta nueva y la designa administradora de la sede.
     *
     * @param  array{name: string, email: string, password: string}  $datos
     */
    public function altaAdministrador(Desguace $desguace, array $datos): User
    {
        return DB::transaction(function () use ($desguace, $datos): User {
            $usuario = new User;
            $usuario->name = $datos['name'];
            $usuario->email = $datos['email'];
            // El cast "hashed" del modelo cifra la contraseña.
            $usuario->password = $datos['password'];
            $usuario->save();

            return $this->designarAdministrador($desguace, $usuario);
        });
    }

    /**
     * Cambia la sede de una cuenta de gerente, operario o sin rol.
     * El acceso a la sede anterior se revoca: el alcance se calcula en cada petición y además
     * se cierran sus sesiones y tokens.
     *
     * @throws DomainException
     */
    public function asignarDesguace(User $usuario, Desguace $desguace): User
    {
        if ($usuario->esAdministradorPlataforma()) {
            throw new DomainException('Las cuentas de plataforma no pertenecen a ningún desguace.');
        }

        if ($usuario->esAdministradorDesguace()) {
            throw new DomainException('Para cambiar al administrador de un desguace usa la designación de administrador.');
        }

        $usuario->id_desguace = $desguace->id_desguace;
        $usuario->save();
        $this->revocarAccesos($usuario);

        return $usuario->refresh();
    }

    /**
     * Habilita una cuenta existente como administrador de plataforma.
     *
     * @throws DomainException
     */
    public function habilitarPlataforma(User $usuario): User
    {
        if ($usuario->id_desguace !== null || $usuario->tieneRolDeDesguace()) {
            throw new DomainException('La cuenta pertenece a un desguace. Usa una cuenta independiente para la plataforma.');
        }

        $usuario->assignRole(RoleSeeder::PLATAFORMA);
        $this->revocarAccesos($usuario);

        return $usuario->refresh();
    }

    /**
     * Cierra las sesiones guardadas en base de datos y elimina los tokens de API:
     * el siguiente acceso exige iniciar sesión de nuevo.
     */
    public function revocarAccesos(User $usuario): void
    {
        $usuario->tokens()->delete();

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $usuario->getKey())->delete();
        }
    }
}
