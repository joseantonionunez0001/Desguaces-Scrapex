<?php

namespace App\Support;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Punto único que decide a qué desguace y a qué cuentas puede acceder un usuario.
 *
 * Se calcula en cada petición a partir del rol y del desguace asignado en la base de datos,
 * de modo que un cambio de sede o de rol se aplica en la siguiente petición.
 */
final class Acceso
{
    public const AVISO_SIN_DESGUACE = 'Tu cuenta no tiene un desguace asignado. Contacta con administración.';

    /**
     * Roles de administración: nadie los concede desde la gestión de equipos.
     */
    public const ROLES_ADMINISTRACION = [
        RoleSeeder::PLATAFORMA,
        RoleSeeder::ADMIN_DESGUACE,
        RoleSeeder::ADMINISTRADOR,
    ];

    /**
     * Id del desguace autorizado, o null si el usuario no puede acceder a datos de ninguna sede
     * (sin rol de desguace, sin sede asignada o con una sede eliminada).
     */
    public static function desguaceId(?User $usuario): ?int
    {
        if (! $usuario || ! $usuario->tieneRolDeDesguace() || ! $usuario->id_desguace) {
            return null;
        }

        $id = (int) $usuario->id_desguace;
        $clave = "acceso.sede.{$usuario->getKey()}.{$id}";
        $peticion = app()->bound('request') ? request() : null;

        // Se memoriza solo durante la petición actual: el scope global consulta la sede en cada query.
        if ($peticion !== null && $peticion->attributes->has($clave)) {
            return $peticion->attributes->get($clave);
        }

        // Consulta directa: no pasa por el scope global de Desguace (evita recursión).
        $existe = DB::table('desguaces')
            ->where('id_desguace', $id)
            ->whereNull('deleted_at')
            ->exists();

        $resultado = $existe ? $id : null;
        $peticion?->attributes->set($clave, $resultado);

        return $resultado;
    }

    /**
     * true si el usuario tiene un rol de desguace pero no una sede válida (acceso bloqueado).
     */
    public static function sinDesguaceAsignado(?User $usuario): bool
    {
        return $usuario !== null
            && $usuario->tieneRolDeDesguace()
            && self::desguaceId($usuario) === null;
    }

    /**
     * Cuentas que el usuario puede consultar: todas para la plataforma, las de su sede para
     * el administrador de desguace y ninguna en cualquier otro caso.
     *
     * @return Builder<User>
     */
    public static function usuariosVisibles(User $actor): Builder
    {
        if ($actor->esAdministradorPlataforma()) {
            return User::query();
        }

        $idDesguace = $actor->esAdministradorDesguace() ? self::desguaceId($actor) : null;

        return $idDesguace === null
            ? User::query()->whereRaw('1 = 0')
            : User::query()->delDesguace($idDesguace);
    }

    /**
     * El administrador de desguace solo gestiona gerentes, operarios y cuentas sin rol de su sede,
     * y nunca su propia cuenta.
     */
    public static function puedeGestionarEquipo(User $actor, User $objetivo): bool
    {
        $idDesguace = $actor->esAdministradorDesguace() ? self::desguaceId($actor) : null;

        return $idDesguace !== null
            && ! $objetivo->is($actor)
            && (int) $objetivo->id_desguace === $idDesguace
            && ! $objetivo->hasAnyRole(self::ROLES_ADMINISTRACION);
    }

    /**
     * Roles que puede conceder el usuario (solo gerente y operario, y solo un administrador de desguace).
     *
     * @return list<string>
     */
    public static function rolesConcedibles(User $actor): array
    {
        return $actor->esAdministradorDesguace() && self::desguaceId($actor) !== null
            ? RoleSeeder::ROLES_GESTIONABLES
            : [];
    }
}
