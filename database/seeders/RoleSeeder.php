<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Roles del sistema. Se crean con Spatie Laravel Permission, no con una tabla propia.
     *
     * - PLATAFORMA: configura la plataforma (desguaces y sus administradores). Sin acceso al inventario.
     * - ADMIN_DESGUACE: administra el equipo y el inventario de UNA sede.
     * - GERENTE: gestiona vehiculos, piezas y stock de su sede.
     * - OPERARIO: solo consulta vehiculos y piezas de su sede.
     * - ADMINISTRADOR: rol anterior. Se conserva SIN permisos para no perder la asignacion
     *   existente ni promover a nadie por suposicion. Debe sustituirse a mano (ver README).
     */
    public const PLATAFORMA = 'administrador_plataforma';

    public const ADMIN_DESGUACE = 'administrador_desguace';

    public const GERENTE = 'gerente';

    public const OPERARIO = 'operario';

    public const ADMINISTRADOR = 'administrador';

    public const ROLES = [
        self::PLATAFORMA,
        self::ADMIN_DESGUACE,
        self::GERENTE,
        self::OPERARIO,
        self::ADMINISTRADOR,
    ];

    // Roles ligados a una sede: requieren id_desguace para acceder a datos.
    public const ROLES_DESGUACE = [
        self::ADMIN_DESGUACE,
        self::GERENTE,
        self::OPERARIO,
    ];

    // Roles que un administrador de desguace puede conceder (nunca roles de administracion).
    public const ROLES_GESTIONABLES = [
        self::GERENTE,
        self::OPERARIO,
    ];

    public function run(): void
    {
        foreach (self::ROLES as $nombre) {
            Role::firstOrCreate([
                'name' => $nombre,
                'guard_name' => 'web',
            ]);
        }
    }
}
